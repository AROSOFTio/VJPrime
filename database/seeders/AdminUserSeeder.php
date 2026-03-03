<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(['email' => 'admin@arostream.local'], [
            'name' => 'AroStream Admin',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'subscription_status' => 'premium',
            'daily_free_seconds_used' => 0,
            'last_reset_at' => now(),
        ]);

        Profile::updateOrCreate(['user_id' => $admin->id], [
            'display_name' => 'Admin',
            'avatar_url' => null,
        ]);

        $freeUser = User::updateOrCreate(['email' => 'free@arostream.local'], [
            'name' => 'Free Viewer',
            'password' => Hash::make('password'),
            'role' => 'user',
            'subscription_status' => 'free',
            'daily_free_seconds_used' => 600,
            'last_reset_at' => now(),
        ]);

        Profile::updateOrCreate(['user_id' => $freeUser->id], [
            'display_name' => 'Free Viewer',
            'avatar_url' => null,
        ]);

        $premiumUser = User::updateOrCreate(['email' => 'premium@arostream.local'], [
            'name' => 'Premium Viewer',
            'password' => Hash::make('password'),
            'role' => 'user',
            'subscription_status' => 'premium',
            'daily_free_seconds_used' => 0,
            'last_reset_at' => now(),
        ]);

        Profile::updateOrCreate(['user_id' => $premiumUser->id], [
            'display_name' => 'Premium Viewer',
            'avatar_url' => null,
        ]);
    }
}
