<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): SymfonyRedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()
                ->route('register')
                ->withErrors(['google' => 'Google sign up failed. Please try again.']);
        }

        $email = $googleUser->getEmail();

        if (! $email) {
            return redirect()
                ->route('register')
                ->withErrors(['google' => 'Google account must have an email address.']);
        }

        $user = User::query()
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $email)
            ->first();

        if (! $user) {
            $name = $googleUser->getName() ?: Str::before($email, '@');

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'phone' => null,
                'google_id' => $googleUser->getId(),
                'password' => Hash::make(Str::random(40)),
                'last_reset_at' => now(),
            ]);

            $user->forceFill([
                'email_verified_at' => now(),
            ])->save();

            Profile::create([
                'user_id' => $user->id,
                'display_name' => $user->name,
            ]);
        } elseif (! $user->google_id) {
            $user->forceFill([
                'google_id' => $googleUser->getId(),
                'email_verified_at' => $user->email_verified_at ?: now(),
            ])->save();
        }

        Auth::login($user, true);
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
