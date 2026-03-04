<x-layouts.admin :title="'Revenue Reports - VJPrime'">

    <section class="mt-5 rounded-xl border border-white/10 bg-slate-900/70 p-4">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold">Revenue Reports</h1>
                <p class="text-xs text-slate-400">
                    {{ $periodLabel }}: {{ $from->format('Y-m-d H:i') }} to {{ $to->format('Y-m-d H:i') }}
                </p>
            </div>
            <a href="{{ route('admin.reports.export', ['section' => 'revenue'] + request()->query()) }}" class="rounded-md border border-emerald-500/40 px-3 py-2 text-xs text-emerald-200">Export CSV</a>
        </div>

        <form method="GET" action="{{ route('admin.reports.revenue') }}" class="mb-4 grid gap-3 rounded-lg border border-white/10 bg-slate-950/40 p-3 sm:grid-cols-2 lg:grid-cols-5">
            <select name="period" class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm">
                @foreach ($periodOptions as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['period'] ?? 'weekly') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm">
            <input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}" class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm">
            <select name="status" class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                @foreach (['paid', 'pending', 'failed', 'cancelled', 'processing'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <select name="sort" class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm">
                <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Newest</option>
                <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>Oldest</option>
                <option value="amount_high" @selected(($filters['sort'] ?? '') === 'amount_high')>Amount High-Low</option>
                <option value="amount_low" @selected(($filters['sort'] ?? '') === 'amount_low')>Amount Low-High</option>
            </select>
            <input type="text" name="provider" value="{{ $filters['provider'] ?? '' }}" placeholder="Provider" class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm">
            <input type="text" name="currency" value="{{ $filters['currency'] ?? '' }}" placeholder="Currency" class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm">
            <input type="text" name="plan" value="{{ $filters['plan'] ?? '' }}" placeholder="Plan code/name" class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search ref, user..." class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm">
            <div class="flex gap-2">
                <button class="rounded-md bg-white/10 px-4 py-2 text-sm text-slate-100 hover:bg-white/20">Run</button>
                <a href="{{ route('admin.reports.revenue') }}" class="rounded-md border border-white/20 px-4 py-2 text-sm text-slate-200">Reset</a>
            </div>
        </form>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 p-3">
                <p class="text-xs uppercase tracking-wide text-emerald-200">Gross Amount</p>
                <p class="mt-1 text-xl font-semibold text-emerald-100">{{ number_format($metrics['gross_amount'], 2) }}</p>
            </div>
            <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 p-3">
                <p class="text-xs uppercase tracking-wide text-emerald-200">Paid Amount</p>
                <p class="mt-1 text-xl font-semibold text-emerald-100">{{ number_format($metrics['paid_amount'], 2) }}</p>
            </div>
            <div class="rounded-lg border border-sky-500/30 bg-sky-500/10 p-3">
                <p class="text-xs uppercase tracking-wide text-sky-200">Total Transactions</p>
                <p class="mt-1 text-xl font-semibold text-sky-100">{{ number_format($metrics['total_transactions']) }}</p>
            </div>
            <div class="rounded-lg border border-sky-500/30 bg-sky-500/10 p-3">
                <p class="text-xs uppercase tracking-wide text-sky-200">Paid Transactions</p>
                <p class="mt-1 text-xl font-semibold text-sky-100">{{ number_format($metrics['paid_transactions']) }}</p>
            </div>
            <div class="rounded-lg border border-violet-500/30 bg-violet-500/10 p-3">
                <p class="text-xs uppercase tracking-wide text-violet-200">Unique Payers</p>
                <p class="mt-1 text-xl font-semibold text-violet-100">{{ number_format($metrics['unique_payers']) }}</p>
            </div>
        </div>
    </section>

    <section class="mt-4 grid gap-4 xl:grid-cols-2">
        <div class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-200">Revenue By Plan</h2>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[420px] text-left text-sm">
                    <thead class="text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-2 py-2">Plan</th>
                            <th class="px-2 py-2">Transactions</th>
                            <th class="px-2 py-2">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($planBreakdown as $row)
                            <tr class="border-t border-white/10 text-slate-200">
                                <td class="px-2 py-2">{{ $row->plan_name }}</td>
                                <td class="px-2 py-2">{{ number_format((int) $row->tx_count) }}</td>
                                <td class="px-2 py-2">{{ number_format((float) $row->amount_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-2 py-3 text-sm text-slate-400">No plan revenue in selected range.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-200">Transactions</h2>
            <div class="space-y-2">
                @forelse ($payments as $payment)
                    <div class="rounded-md border border-white/10 bg-slate-950/60 px-3 py-2">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm">{{ $payment->user?->name ?? 'Unknown User' }}</p>
                            <span class="rounded bg-white/10 px-1.5 py-0.5 text-[11px]">{{ strtoupper($payment->status) }}</span>
                        </div>
                        <p class="text-xs text-slate-300">
                            {{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }} |
                            {{ $payment->provider }} |
                            {{ $payment->plan_name ?: $payment->plan_code ?: 'N/A' }}
                        </p>
                        <p class="text-xs text-slate-400">
                            {{ optional($payment->created_at)->format('Y-m-d H:i') }} |
                            Ref: {{ $payment->reference }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No transactions found.</p>
                @endforelse
            </div>
            <div class="mt-4">{{ $payments->links() }}</div>
        </div>
    </section>
</x-layouts.admin>
