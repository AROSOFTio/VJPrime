<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Download;
use App\Models\Movie;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Models\View;
use Carbon\Carbon;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const PERIODS = [
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'biannual' => 'Biannual',
        'yearly' => 'Yearly',
    ];

    public function index(Request $request): ViewContract
    {
        return $this->revenue($request);
    }

    public function revenue(Request $request): ViewContract
    {
        $validated = $request->validate([
            'period' => ['nullable', 'in:daily,weekly,monthly,quarterly,biannual,yearly'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'status' => ['nullable', 'in:paid,pending,failed,cancelled,processing'],
            'provider' => ['nullable', 'string', 'max:50'],
            'currency' => ['nullable', 'string', 'max:10'],
            'plan' => ['nullable', 'string', 'max:60'],
            'search' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', 'in:newest,oldest,amount_high,amount_low'],
        ]);

        [$from, $to, $periodKey, $periodLabel] = $this->resolveRange(
            $validated['period'] ?? 'weekly',
            $validated['from_date'] ?? null,
            $validated['to_date'] ?? null,
        );

        $filters = [
            'period' => $periodKey,
            'from_date' => $this->normalizeInputDate($validated['from_date'] ?? null),
            'to_date' => $this->normalizeInputDate($validated['to_date'] ?? null),
            'status' => $this->cleanString($validated['status'] ?? null),
            'provider' => $this->cleanString($validated['provider'] ?? null),
            'currency' => $this->cleanString($validated['currency'] ?? null),
            'plan' => $this->cleanString($validated['plan'] ?? null),
            'search' => $this->cleanString($validated['search'] ?? null),
            'sort' => $this->cleanString($validated['sort'] ?? null) ?: 'newest',
        ];

        $baseQuery = SubscriptionPayment::query()
            ->whereBetween('created_at', [$from, $to])
            ->when($filters['status'], fn (Builder $builder, string $status) => $builder->where('status', $status))
            ->when($filters['provider'], fn (Builder $builder, string $provider) => $builder->where('provider', $provider))
            ->when($filters['currency'], fn (Builder $builder, string $currency) => $builder->where('currency', $currency))
            ->when($filters['plan'], function (Builder $builder, string $plan): void {
                $builder->where(function (Builder $nested) use ($plan): void {
                    $nested
                        ->where('plan_code', $plan)
                        ->orWhere('plan_name', 'like', '%'.$plan.'%');
                });
            })
            ->when($filters['search'], function (Builder $builder, string $search): void {
                $builder->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('reference', 'like', '%'.$search.'%')
                        ->orWhere('merchant_reference', 'like', '%'.$search.'%')
                        ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                            $userQuery
                                ->where('name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%');
                        });
                });
            });

        $query = (clone $baseQuery)->with('user:id,name,email');

        match ($filters['sort']) {
            'oldest' => $query->oldest('created_at'),
            'amount_high' => $query->orderByDesc('amount'),
            'amount_low' => $query->orderBy('amount'),
            default => $query->latest('created_at'),
        };

        $payments = (clone $query)->paginate(25)->withQueryString();

        $planBreakdown = (clone $baseQuery)
            ->reorder()
            ->selectRaw("COALESCE(plan_name, plan_code, 'Unknown') as plan_label")
            ->selectRaw('COUNT(*) as tx_count')
            ->selectRaw('SUM(amount) as amount_total')
            ->groupByRaw("COALESCE(plan_name, plan_code, 'Unknown')")
            ->orderByDesc('amount_total')
            ->limit(12)
            ->get();

        return view('admin.reports.revenue', [
            'periodOptions' => self::PERIODS,
            'periodLabel' => $periodLabel,
            'from' => $from,
            'to' => $to,
            'filters' => $filters,
            'payments' => $payments,
            'planBreakdown' => $planBreakdown,
            'metrics' => [
                'total_transactions' => (clone $baseQuery)->count(),
                'gross_amount' => (float) (clone $baseQuery)->sum('amount'),
                'paid_transactions' => (clone $baseQuery)->where('status', 'paid')->count(),
                'paid_amount' => (float) (clone $baseQuery)->where('status', 'paid')->sum('amount'),
                'unique_payers' => (clone $baseQuery)->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
            ],
        ]);
    }

    public function content(Request $request): ViewContract
    {
        $validated = $request->validate([
            'period' => ['nullable', 'in:daily,weekly,monthly,quarterly,biannual,yearly'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'status' => ['nullable', 'in:draft,published'],
            'content_type' => ['nullable', 'in:movie,series'],
            'creator_id' => ['nullable', 'integer', 'exists:users,id'],
            'search' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', 'in:newest,oldest,title,streams,downloads'],
        ]);

        [$from, $to, $periodKey, $periodLabel] = $this->resolveRange(
            $validated['period'] ?? 'weekly',
            $validated['from_date'] ?? null,
            $validated['to_date'] ?? null,
        );

        $filters = [
            'period' => $periodKey,
            'from_date' => $this->normalizeInputDate($validated['from_date'] ?? null),
            'to_date' => $this->normalizeInputDate($validated['to_date'] ?? null),
            'status' => $this->cleanString($validated['status'] ?? null),
            'content_type' => $this->cleanString($validated['content_type'] ?? null),
            'creator_id' => isset($validated['creator_id']) ? (int) $validated['creator_id'] : null,
            'search' => $this->cleanString($validated['search'] ?? null),
            'sort' => $this->cleanString($validated['sort'] ?? null) ?: 'newest',
        ];

        $baseQuery = Movie::query()
            ->when($filters['status'], fn (Builder $builder, string $status) => $builder->where('status', $status))
            ->when($filters['content_type'], fn (Builder $builder, string $type) => $builder->where('content_type', $type))
            ->when($filters['creator_id'], fn (Builder $builder, int $creatorId) => $builder->where('created_by', $creatorId))
            ->when($filters['search'], function (Builder $builder, string $search): void {
                $builder->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('title', 'like', '%'.$search.'%')
                        ->orWhere('series_title', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%');
                });
            });

        $query = (clone $baseQuery)
            ->with(['creator:id,name,email', 'language:id,name', 'vj:id,name'])
            ->withCount([
                'views as streams_count' => fn (Builder $builder) => $builder->whereBetween('created_at', [$from, $to]),
                'downloads as downloads_count' => fn (Builder $builder) => $builder->whereBetween('created_at', [$from, $to]),
            ]);

        match ($filters['sort']) {
            'oldest' => $query->oldest('created_at'),
            'title' => $query->orderBy('title'),
            'streams' => $query->orderByDesc('streams_count'),
            'downloads' => $query->orderByDesc('downloads_count'),
            default => $query->latest('created_at'),
        };

        $movies = $query->paginate(25)->withQueryString();

        $movieIdSubQuery = (clone $baseQuery)->select('movies.id');

        $topStreamed = Movie::query()
            ->whereIn('id', $movieIdSubQuery)
            ->withCount([
                'views as streams_count' => fn (Builder $builder) => $builder->whereBetween('created_at', [$from, $to]),
            ])
            ->orderByDesc('streams_count')
            ->take(10)
            ->get(['id', 'title', 'content_type']);

        $topDownloaded = Movie::query()
            ->whereIn('id', (clone $baseQuery)->select('movies.id'))
            ->withCount([
                'downloads as downloads_count' => fn (Builder $builder) => $builder->whereBetween('created_at', [$from, $to]),
            ])
            ->orderByDesc('downloads_count')
            ->take(10)
            ->get(['id', 'title', 'content_type']);

        $currentPublishedByCreator = User::query()
            ->select(['users.id', 'users.name', 'users.role'])
            ->selectRaw('COUNT(movies.id) as published_count')
            ->join('movies', 'movies.created_by', '=', 'users.id')
            ->where('movies.status', 'published')
            ->groupBy('users.id', 'users.name', 'users.role')
            ->orderByDesc('published_count')
            ->limit(10)
            ->get();

        return view('admin.reports.content', [
            'periodOptions' => self::PERIODS,
            'periodLabel' => $periodLabel,
            'from' => $from,
            'to' => $to,
            'filters' => $filters,
            'creators' => User::query()
                ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_CONTENT_MANAGER, User::ROLE_CONTRIBUTOR])
                ->orderBy('name')
                ->get(['id', 'name', 'role']),
            'movies' => $movies,
            'topStreamed' => $topStreamed,
            'topDownloaded' => $topDownloaded,
            'currentPublishedByCreator' => $currentPublishedByCreator,
            'metrics' => [
                'content_count' => (clone $baseQuery)->count(),
                'movie_count' => (clone $baseQuery)->where('content_type', 'movie')->count(),
                'series_count' => (clone $baseQuery)->where('content_type', 'series')->count(),
                'stream_count' => View::query()
                    ->whereBetween('created_at', [$from, $to])
                    ->whereIn('movie_id', (clone $baseQuery)->select('movies.id'))
                    ->count(),
                'download_count' => Download::query()
                    ->whereBetween('created_at', [$from, $to])
                    ->whereIn('movie_id', (clone $baseQuery)->select('movies.id'))
                    ->count(),
            ],
        ]);
    }

    public function users(Request $request): ViewContract
    {
        $validated = $request->validate([
            'period' => ['nullable', 'in:daily,weekly,monthly,quarterly,biannual,yearly'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'role' => ['nullable', \Illuminate\Validation\Rule::in(User::ROLES)],
            'subscription_status' => ['nullable', 'in:free,premium'],
            'activity' => ['nullable', 'in:any,streamed,downloaded,both'],
            'search' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', 'in:newest,oldest,name,streams,downloads,watch'],
        ]);

        [$from, $to, $periodKey, $periodLabel] = $this->resolveRange(
            $validated['period'] ?? 'weekly',
            $validated['from_date'] ?? null,
            $validated['to_date'] ?? null,
        );

        $filters = [
            'period' => $periodKey,
            'from_date' => $this->normalizeInputDate($validated['from_date'] ?? null),
            'to_date' => $this->normalizeInputDate($validated['to_date'] ?? null),
            'role' => $this->cleanString($validated['role'] ?? null),
            'subscription_status' => $this->cleanString($validated['subscription_status'] ?? null),
            'activity' => $this->cleanString($validated['activity'] ?? null) ?: 'any',
            'search' => $this->cleanString($validated['search'] ?? null),
            'sort' => $this->cleanString($validated['sort'] ?? null) ?: 'newest',
        ];

        $query = User::query()
            ->with('profile')
            ->withCount([
                'views as streams_count' => fn (Builder $builder) => $builder->whereBetween('created_at', [$from, $to]),
                'downloads as downloads_count' => fn (Builder $builder) => $builder->whereBetween('created_at', [$from, $to]),
            ])
            ->withSum([
                'views as watch_seconds_sum' => fn (Builder $builder) => $builder->whereBetween('created_at', [$from, $to]),
            ], 'seconds_watched')
            ->when($filters['role'], fn (Builder $builder, string $role) => $builder->where('role', $role))
            ->when($filters['subscription_status'], fn (Builder $builder, string $status) => $builder->where('subscription_status', $status))
            ->when($filters['search'], function (Builder $builder, string $search): void {
                $builder->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhereHas('profile', fn (Builder $profileQuery) => $profileQuery->where('display_name', 'like', '%'.$search.'%'));
                });
            });

        match ($filters['activity']) {
            'streamed' => $query->whereHas('views', fn (Builder $builder) => $builder->whereBetween('created_at', [$from, $to])),
            'downloaded' => $query->whereHas('downloads', fn (Builder $builder) => $builder->whereBetween('created_at', [$from, $to])),
            'both' => $query
                ->whereHas('views', fn (Builder $builder) => $builder->whereBetween('created_at', [$from, $to]))
                ->whereHas('downloads', fn (Builder $builder) => $builder->whereBetween('created_at', [$from, $to])),
            default => null,
        };

        match ($filters['sort']) {
            'oldest' => $query->oldest('created_at'),
            'name' => $query->orderBy('name'),
            'streams' => $query->orderByDesc('streams_count'),
            'downloads' => $query->orderByDesc('downloads_count'),
            'watch' => $query->orderByDesc('watch_seconds_sum'),
            default => $query->latest('created_at'),
        };

        $users = $query->paginate(25)->withQueryString();

        $streamedUserIds = View::query()
            ->whereNotNull('user_id')
            ->whereBetween('created_at', [$from, $to])
            ->distinct()
            ->pluck('user_id')
            ->all();

        $downloadedUserIds = Download::query()
            ->whereNotNull('user_id')
            ->whereBetween('created_at', [$from, $to])
            ->distinct()
            ->pluck('user_id')
            ->all();

        $activeUserCount = count(array_unique(array_merge($streamedUserIds, $downloadedUserIds)));

        return view('admin.reports.users', [
            'periodOptions' => self::PERIODS,
            'periodLabel' => $periodLabel,
            'from' => $from,
            'to' => $to,
            'filters' => $filters,
            'users' => $users,
            'roleOptions' => User::ROLES,
            'roleLabels' => $this->roleLabels(),
            'metrics' => [
                'new_users' => User::query()->whereBetween('created_at', [$from, $to])->count(),
                'active_users' => $activeUserCount,
                'streamed_users' => count($streamedUserIds),
                'downloaded_users' => count($downloadedUserIds),
                'premium_users' => User::query()->where('subscription_status', 'premium')->count(),
            ],
        ]);
    }

    public function export(Request $request, string $section): StreamedResponse
    {
        abort_unless(in_array($section, ['revenue', 'content', 'users'], true), 404);

        return match ($section) {
            'revenue' => $this->exportRevenue($request),
            'content' => $this->exportContent($request),
            default => $this->exportUsers($request),
        };
    }

    private function exportRevenue(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'period' => ['nullable', 'in:daily,weekly,monthly,quarterly,biannual,yearly'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'status' => ['nullable', 'in:paid,pending,failed,cancelled,processing'],
            'provider' => ['nullable', 'string', 'max:50'],
            'currency' => ['nullable', 'string', 'max:10'],
            'plan' => ['nullable', 'string', 'max:60'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        [$from, $to, $period] = $this->resolveRange(
            $validated['period'] ?? 'weekly',
            $validated['from_date'] ?? null,
            $validated['to_date'] ?? null,
        );

        $query = SubscriptionPayment::query()
            ->with('user:id,name,email')
            ->whereBetween('created_at', [$from, $to])
            ->when($validated['status'] ?? null, fn (Builder $builder, string $status) => $builder->where('status', $status))
            ->when($validated['provider'] ?? null, fn (Builder $builder, string $provider) => $builder->where('provider', $provider))
            ->when($validated['currency'] ?? null, fn (Builder $builder, string $currency) => $builder->where('currency', $currency))
            ->when($validated['plan'] ?? null, function (Builder $builder, string $plan): void {
                $builder->where(function (Builder $nested) use ($plan): void {
                    $nested->where('plan_code', $plan)->orWhere('plan_name', 'like', '%'.$plan.'%');
                });
            })
            ->when($validated['search'] ?? null, function (Builder $builder, string $search): void {
                $builder->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('reference', 'like', '%'.$search.'%')
                        ->orWhere('merchant_reference', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%'));
                });
            })
            ->latest('created_at');

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['paid_at', 'status', 'user', 'email', 'amount', 'currency', 'provider', 'plan', 'reference']);

            $query->cursor()->each(function (SubscriptionPayment $payment) use ($out): void {
                fputcsv($out, [
                    optional($payment->created_at)->format('Y-m-d H:i:s'),
                    $payment->status,
                    $payment->user?->name,
                    $payment->user?->email,
                    $payment->amount,
                    $payment->currency,
                    $payment->provider,
                    $payment->plan_name ?: $payment->plan_code,
                    $payment->reference,
                ]);
            });

            fclose($out);
        }, 'vjprime-revenue-'.$period.'-'.now()->format('Ymd_His').'.csv');
    }

    private function exportContent(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'period' => ['nullable', 'in:daily,weekly,monthly,quarterly,biannual,yearly'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'status' => ['nullable', 'in:draft,published'],
            'content_type' => ['nullable', 'in:movie,series'],
            'creator_id' => ['nullable', 'integer', 'exists:users,id'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        [$from, $to, $period] = $this->resolveRange(
            $validated['period'] ?? 'weekly',
            $validated['from_date'] ?? null,
            $validated['to_date'] ?? null,
        );

        $query = Movie::query()
            ->with(['creator:id,name,email'])
            ->withCount([
                'views as streams_count' => fn (Builder $builder) => $builder->whereBetween('created_at', [$from, $to]),
                'downloads as downloads_count' => fn (Builder $builder) => $builder->whereBetween('created_at', [$from, $to]),
            ])
            ->when($validated['status'] ?? null, fn (Builder $builder, string $status) => $builder->where('status', $status))
            ->when($validated['content_type'] ?? null, fn (Builder $builder, string $type) => $builder->where('content_type', $type))
            ->when($validated['creator_id'] ?? null, fn (Builder $builder, int $creatorId) => $builder->where('created_by', $creatorId))
            ->when($validated['search'] ?? null, function (Builder $builder, string $search): void {
                $builder->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('title', 'like', '%'.$search.'%')
                        ->orWhere('series_title', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%');
                });
            })
            ->latest('created_at');

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['created_at', 'published_at', 'type', 'status', 'title', 'creator', 'creator_email', 'streams', 'downloads']);

            $query->cursor()->each(function (Movie $movie) use ($out): void {
                fputcsv($out, [
                    optional($movie->created_at)->format('Y-m-d H:i:s'),
                    optional($movie->published_at)->format('Y-m-d H:i:s'),
                    $movie->content_type,
                    $movie->status,
                    $movie->title,
                    $movie->creator?->name,
                    $movie->creator?->email,
                    $movie->streams_count ?? 0,
                    $movie->downloads_count ?? 0,
                ]);
            });

            fclose($out);
        }, 'vjprime-content-'.$period.'-'.now()->format('Ymd_His').'.csv');
    }

    private function exportUsers(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'period' => ['nullable', 'in:daily,weekly,monthly,quarterly,biannual,yearly'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'role' => ['nullable', \Illuminate\Validation\Rule::in(User::ROLES)],
            'subscription_status' => ['nullable', 'in:free,premium'],
            'activity' => ['nullable', 'in:any,streamed,downloaded,both'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        [$from, $to, $period] = $this->resolveRange(
            $validated['period'] ?? 'weekly',
            $validated['from_date'] ?? null,
            $validated['to_date'] ?? null,
        );

        $query = User::query()
            ->with('profile')
            ->withCount([
                'views as streams_count' => fn (Builder $builder) => $builder->whereBetween('created_at', [$from, $to]),
                'downloads as downloads_count' => fn (Builder $builder) => $builder->whereBetween('created_at', [$from, $to]),
            ])
            ->withSum([
                'views as watch_seconds_sum' => fn (Builder $builder) => $builder->whereBetween('created_at', [$from, $to]),
            ], 'seconds_watched')
            ->when($validated['role'] ?? null, fn (Builder $builder, string $role) => $builder->where('role', $role))
            ->when($validated['subscription_status'] ?? null, fn (Builder $builder, string $status) => $builder->where('subscription_status', $status))
            ->when($validated['search'] ?? null, function (Builder $builder, string $search): void {
                $builder->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhereHas('profile', fn (Builder $profileQuery) => $profileQuery->where('display_name', 'like', '%'.$search.'%'));
                });
            })
            ->latest('created_at');

        match ($validated['activity'] ?? 'any') {
            'streamed' => $query->whereHas('views', fn (Builder $builder) => $builder->whereBetween('created_at', [$from, $to])),
            'downloaded' => $query->whereHas('downloads', fn (Builder $builder) => $builder->whereBetween('created_at', [$from, $to])),
            'both' => $query
                ->whereHas('views', fn (Builder $builder) => $builder->whereBetween('created_at', [$from, $to]))
                ->whereHas('downloads', fn (Builder $builder) => $builder->whereBetween('created_at', [$from, $to])),
            default => null,
        };

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['name', 'display_name', 'email', 'phone', 'role', 'subscription', 'streams', 'downloads', 'watch_seconds', 'created_at']);

            $query->cursor()->each(function (User $user) use ($out): void {
                fputcsv($out, [
                    $user->name,
                    $user->profile?->display_name,
                    $user->email,
                    $user->phone,
                    $user->roleLabel(),
                    $user->subscription_status,
                    $user->streams_count ?? 0,
                    $user->downloads_count ?? 0,
                    (int) ($user->watch_seconds_sum ?? 0),
                    optional($user->created_at)->format('Y-m-d H:i:s'),
                ]);
            });

            fclose($out);
        }, 'vjprime-users-'.$period.'-'.now()->format('Ymd_His').'.csv');
    }

    private function resolveRange(string $period, ?string $fromDate, ?string $toDate): array
    {
        if ($fromDate || $toDate) {
            $from = $fromDate ? Carbon::parse($fromDate)->startOfDay() : Carbon::parse($toDate)->startOfDay();
            $to = $toDate ? Carbon::parse($toDate)->endOfDay() : Carbon::parse($fromDate)->endOfDay();

            if ($from->gt($to)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }

            return [$from, $to, 'custom', 'Custom Range'];
        }

        $to = now();
        $from = match ($period) {
            'daily' => $to->copy()->startOfDay(),
            'weekly' => $to->copy()->subDays(6)->startOfDay(),
            'monthly' => $to->copy()->subDays(29)->startOfDay(),
            'quarterly' => $to->copy()->subDays(89)->startOfDay(),
            'biannual' => $to->copy()->subMonthsNoOverflow(6)->startOfDay(),
            'yearly' => $to->copy()->subYear()->startOfDay(),
            default => $to->copy()->subDays(6)->startOfDay(),
        };

        return [$from, $to, $period, self::PERIODS[$period] ?? self::PERIODS['weekly']];
    }

    private function normalizeInputDate(?string $date): ?string
    {
        if (! $date) {
            return null;
        }

        return Carbon::parse($date)->format('Y-m-d');
    }

    private function cleanString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $cleaned = trim($value);

        return $cleaned !== '' ? $cleaned : null;
    }

    private function roleLabels(): array
    {
        return [
            User::ROLE_USER => 'Viewer / Customer',
            User::ROLE_ADMIN => 'Admin',
            User::ROLE_CONTENT_MANAGER => 'Content Manager',
            User::ROLE_CONTRIBUTOR => 'Contributor',
            User::ROLE_FINANCE_MANAGER => 'Finance Manager',
        ];
    }
}
