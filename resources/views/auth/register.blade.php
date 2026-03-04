<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold tracking-tight text-white">Create Your VJPrime Account</h1>
        <p class="mt-1 text-sm text-slate-300">Join now and start streaming Luganda and Ateso translated content.</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" class="text-slate-200" />
            <x-text-input id="name" class="mt-1 block w-full border-white/15 bg-white/95 text-slate-900 placeholder:text-slate-500 focus:border-red-400 focus:ring-red-400" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email (optional)')" class="text-slate-200" />
            <x-text-input id="email" class="mt-1 block w-full border-white/15 bg-white/95 text-slate-900 placeholder:text-slate-500 focus:border-red-400 focus:ring-red-400" type="email" name="email" :value="old('email')" autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Phone Number -->
        <div class="mt-4">
            <x-input-label for="phone" :value="__('Phone Number (Uganda)')" class="text-slate-200" />
            <x-text-input id="phone" class="mt-1 block w-full border-white/15 bg-white/95 text-slate-900 placeholder:text-slate-500 focus:border-red-400 focus:ring-red-400" type="tel" name="phone" :value="old('phone', '+256')" required autocomplete="tel" placeholder="+256700123456" pattern="\+256\d{9}" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" class="text-slate-200" />

            <x-text-input id="password" class="mt-1 block w-full border-white/15 bg-white/95 text-slate-900 placeholder:text-slate-500 focus:border-red-400 focus:ring-red-400"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="text-slate-200" />

            <x-text-input id="password_confirmation" class="mt-1 block w-full border-white/15 bg-white/95 text-slate-900 placeholder:text-slate-500 focus:border-red-400 focus:ring-red-400"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-slate-300 hover:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4 bg-red-600 hover:bg-red-500 focus:bg-red-500 active:bg-red-700 focus:ring-red-400">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>

    @if ($errors->has('google'))
        <div class="mt-4 text-sm text-red-600">
            {{ $errors->first('google') }}
        </div>
    @endif

    <div class="my-4 flex items-center">
        <div class="h-px flex-1 bg-white/25"></div>
        <span class="px-3 text-xs uppercase tracking-wide text-slate-300">{{ __('or') }}</span>
        <div class="h-px flex-1 bg-white/25"></div>
    </div>

    <a
        href="{{ route('auth.google.redirect') }}"
        class="inline-flex w-full items-center justify-center rounded-md border border-white/20 bg-white/95 px-4 py-2 text-sm font-medium text-slate-800 shadow-sm transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2 focus:ring-offset-slate-950"
    >
        {{ __('Sign up with Google') }}
    </a>
</x-guest-layout>
