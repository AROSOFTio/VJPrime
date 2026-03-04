<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold tracking-tight text-white">Welcome Back To VJPrime</h1>
        <p class="mt-1 text-sm text-slate-300">Sign in to continue watching your favorite translated movies.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Username / Email / Phone -->
        <div>
            <x-input-label for="login" :value="__('Email, Username, or Phone')" class="text-slate-200" />
            <x-text-input id="login" class="mt-1 block w-full border-white/15 bg-white/95 text-slate-900 placeholder:text-slate-500 focus:border-red-400 focus:ring-red-400" type="text" name="login" :value="old('login')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('login')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" class="text-slate-200" />

            <x-text-input id="password" class="mt-1 block w-full border-white/15 bg-white/95 text-slate-900 placeholder:text-slate-500 focus:border-red-400 focus:ring-red-400"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-slate-400 text-red-600 shadow-sm focus:ring-red-500" name="remember">
                <span class="ms-2 text-sm text-slate-300">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-slate-300 hover:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3 bg-red-600 hover:bg-red-500 focus:bg-red-500 active:bg-red-700 focus:ring-red-400">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>

    <div class="my-4 flex items-center">
        <div class="h-px flex-1 bg-white/25"></div>
        <span class="px-3 text-xs uppercase tracking-wide text-slate-300">{{ __('or') }}</span>
        <div class="h-px flex-1 bg-white/25"></div>
    </div>

    <a
        href="{{ route('auth.google.redirect') }}"
        class="inline-flex w-full items-center justify-center rounded-md border border-white/20 bg-white/95 px-4 py-2 text-sm font-medium text-slate-800 shadow-sm transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2 focus:ring-offset-slate-950"
    >
        {{ __('Continue with Google') }}
    </a>
</x-guest-layout>
