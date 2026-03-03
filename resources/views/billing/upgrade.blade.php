<x-layouts.stream :title="'Upgrade - AroStream'" :wallpaper-posters="[]">
    <section class="grid gap-5 lg:grid-cols-[1fr,340px]">
        <div class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
            <h1 class="text-2xl font-semibold">Upgrade to Premium</h1>
            <p class="mt-2 text-sm text-slate-300">
                Premium unlocks unlimited streaming and download access.
            </p>

            <div class="mt-5 rounded-md border border-white/10 bg-slate-950/60 p-4">
                <p class="text-xs text-slate-400">Plan</p>
                <p class="text-lg font-semibold">VJPrime Premium</p>
                <p class="mt-1 text-sm text-slate-300">{{ number_format($amount, 2) }} {{ $currency }}</p>
            </div>

            @if ($isPesapalEnabled)
                <form action="{{ route('billing.pesapal.checkout') }}" method="POST" class="mt-5">
                    @csrf
                    <button class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white">
                        Pay with Pesapal
                    </button>
                </form>
            @else
                <p class="mt-5 rounded-md border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-200">
                    Pesapal is not configured. Set Pesapal keys in `.env` first.
                </p>
            @endif
        </div>

        <aside class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
            <h2 class="text-lg font-semibold">Recent Payments</h2>
            <div class="mt-3 space-y-2">
                @forelse ($payments as $payment)
                    <div class="rounded-md border border-white/10 bg-slate-950/60 px-3 py-2">
                        <p class="text-xs text-slate-300">{{ $payment->reference }}</p>
                        <p class="text-xs text-slate-400">
                            {{ strtoupper($payment->status) }} -
                            {{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}
                        </p>
                    </div>
                @empty
                    <p class="rounded-md border border-white/10 bg-slate-950/60 px-3 py-2 text-xs text-slate-400">No payment history yet.</p>
                @endforelse
            </div>
        </aside>
    </section>
</x-layouts.stream>
