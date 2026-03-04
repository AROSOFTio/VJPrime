<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Download;
use App\Models\Movie;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Models\View;
use Illuminate\Contracts\View\View as ViewContract;
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
        $validated = $request->validate([
            'period' => ['nullable', 'in:daily,weekly,monthly,quarterly,biannual,yearly'],
        ]);

        $period = $validated['period'] ?? 'weekly';
        [$from, $to, $periodLabel] = $this->resolvePeriod($period);
        $range = [$from, $to];

        $salesAmount = (float) SubscriptionPayment::query()
            ->where('status', 'paid')
            ->whereBetween('created_at', $range)
            ->sum('amount');

        $salesCount = SubscriptionPayment::query()
            ->where('status', 'paid')
            ->whereBetween('created_at', $range)
            ->count();

        $downloadsCount = Download::query()
            ->whereBetween('created_at', $range)
            ->count();

        $streamsCount = View::query()
            ->whereBetween('created_at', $range)
            ->count();

        $watchSeconds = (int) View::query()
            ->whereBetween('created_at', $range)
            ->sum('seconds_watched');

        $activeStreamUsers = (int) View::query()
            ->whereBetween('created_at', $range)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        $publishedMoviesCount = Movie::query()
            ->where('status', 'published')
            ->where('content_type', 'movie')
            ->count();

        $publishedSeriesCount = Movie::query()
            ->where('status', 'published')
            ->where('content_type', 'series')
            ->count();

        $topStreamed = Movie::query()
            ->select(['id', 'title', 'slug', 'content_type'])
            ->withCount([
                'views as streams_count' => fn ($builder) => $builder->whereBetween('created_at', $range),
            ])
            ->orderByDesc('streams_count')
            ->take(10)
            ->get();

        $topDownloaded = Movie::query()
            ->select(['id', 'title', 'slug', 'content_type'])
            ->withCount([
                'downloads as downloads_count' => fn ($builder) => $builder->whereBetween('created_at', $range),
            ])
            ->orderByDesc('downloads_count')
            ->take(10)
            ->get();

        $latestPublished = Movie::query()
            ->with(['creator:id,name,email'])
            ->where('status', 'published')
            ->latest('published_at')
            ->take(20)
            ->get(['id', 'title', 'content_type', 'created_by', 'published_at']);

        $publishedByCreator = User::query()
            ->select(['users.id', 'users.name', 'users.role'])
            ->selectRaw('COUNT(movies.id) as published_count')
            ->join('movies', 'movies.created_by', '=', 'users.id')
            ->where('movies.status', 'published')
            ->groupBy('users.id', 'users.name', 'users.role')
            ->orderByDesc('published_count')
            ->limit(10)
            ->get();

        $periodicSnapshots = $this->periodicSnapshots();

        return view('admin.reports.index', [
            'period' => $period,
            'periodOptions' => self::PERIODS,
            'periodLabel' => $periodLabel,
            'from' => $from,
            'to' => $to,
            'metrics' => [
                'sales_amount' => $salesAmount,
                'sales_count' => $salesCount,
                'downloads_count' => $downloadsCount,
                'streams_count' => $streamsCount,
                'watch_seconds' => $watchSeconds,
                'active_stream_users' => $activeStreamUsers,
                'published_movies_count' => $publishedMoviesCount,
                'published_series_count' => $publishedSeriesCount,
            ],
            'periodicSnapshots' => $periodicSnapshots,
            'topStreamed' => $topStreamed,
            'topDownloaded' => $topDownloaded,
            'latestPublished' => $latestPublished,
            'publishedByCreator' => $publishedByCreator,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'kind' => ['required', 'in:sales,downloads,usage,content'],
            'period' => ['nullable', 'in:daily,weekly,monthly,quarterly,biannual,yearly'],
        ]);

        $kind = $validated['kind'];
        $period = $validated['period'] ?? 'weekly';
        [$from, $to] = $this->resolvePeriod($period);
        $filename = "vjprime-{$kind}-{$period}-".now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($kind, $from, $to): void {
            $out = fopen('php://output', 'w');

            switch ($kind) {
                case 'sales':
                    fputcsv($out, ['paid_at', 'user', 'email', 'amount', 'currency', 'plan', 'reference']);
                    SubscriptionPayment::query()
                        ->with('user:id,name,email')
                        ->where('status', 'paid')
                        ->whereBetween('created_at', [$from, $to])
                        ->orderByDesc('created_at')
                        ->cursor()
                        ->each(function (SubscriptionPayment $payment) use ($out): void {
                            fputcsv($out, [
                                optional($payment->created_at)?->format('Y-m-d H:i:s'),
                                $payment->user?->name,
                                $payment->user?->email,
                                $payment->amount,
                                $payment->currency,
                                $payment->plan_name,
                                $payment->reference,
                            ]);
                        });
                    break;

                case 'downloads':
                    fputcsv($out, ['downloaded_at', 'movie', 'user', 'email', 'file_path', 'ip']);
                    Download::query()
                        ->with(['movie:id,title', 'user:id,name,email'])
                        ->whereBetween('created_at', [$from, $to])
                        ->orderByDesc('created_at')
                        ->cursor()
                        ->each(function (Download $download) use ($out): void {
                            fputcsv($out, [
                                optional($download->created_at)?->format('Y-m-d H:i:s'),
                                $download->movie?->title,
                                $download->user?->name,
                                $download->user?->email,
                                $download->file_path,
                                $download->ip,
                            ]);
                        });
                    break;

                case 'usage':
                    fputcsv($out, ['viewed_at', 'movie', 'user', 'email', 'seconds_watched', 'device_hash', 'ip']);
                    View::query()
                        ->with(['movie:id,title', 'user:id,name,email'])
                        ->whereBetween('created_at', [$from, $to])
                        ->orderByDesc('created_at')
                        ->cursor()
                        ->each(function (View $view) use ($out): void {
                            fputcsv($out, [
                                optional($view->created_at)?->format('Y-m-d H:i:s'),
                                $view->movie?->title,
                                $view->user?->name,
                                $view->user?->email,
                                $view->seconds_watched,
                                $view->device_hash,
                                $view->ip,
                            ]);
                        });
                    break;

                default:
                    fputcsv($out, ['published_at', 'content_type', 'title', 'created_by', 'creator_email']);
                    Movie::query()
                        ->with('creator:id,name,email')
                        ->where('status', 'published')
                        ->whereBetween('published_at', [$from, $to])
                        ->orderByDesc('published_at')
                        ->cursor()
                        ->each(function (Movie $movie) use ($out): void {
                            fputcsv($out, [
                                optional($movie->published_at)?->format('Y-m-d H:i:s'),
                                $movie->content_type,
                                $movie->title,
                                $movie->creator?->name,
                                $movie->creator?->email,
                            ]);
                        });
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function periodicSnapshots(): array
    {
        $snapshots = [];

        foreach (self::PERIODS as $key => $label) {
            [$from, $to] = $this->resolvePeriod($key);
            $range = [$from, $to];

            $snapshots[$key] = [
                'label' => $label,
                'from' => $from,
                'to' => $to,
                'sales_count' => SubscriptionPayment::query()
                    ->where('status', 'paid')
                    ->whereBetween('created_at', $range)
                    ->count(),
                'sales_amount' => (float) SubscriptionPayment::query()
                    ->where('status', 'paid')
                    ->whereBetween('created_at', $range)
                    ->sum('amount'),
                'downloads_count' => Download::query()
                    ->whereBetween('created_at', $range)
                    ->count(),
                'streams_count' => View::query()
                    ->whereBetween('created_at', $range)
                    ->count(),
            ];
        }

        return $snapshots;
    }

    private function resolvePeriod(string $period): array
    {
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

        $label = self::PERIODS[$period] ?? self::PERIODS['weekly'];

        return [$from, $to, $label];
    }
}
