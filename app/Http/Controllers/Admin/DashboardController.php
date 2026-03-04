<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\User;
use App\Services\OnlineUsersService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, OnlineUsersService $onlineUsersService): View
    {
        $windowMinutes = max(1, (int) config('streaming.online.window_minutes', 5));

        return view('admin.dashboard', [
            'movieCount' => Movie::count(),
            'publishedMovieCount' => Movie::where('status', 'published')->count(),
            'userCount' => User::where('role', User::ROLE_USER)->count(),
            'adminCount' => User::where('role', User::ROLE_ADMIN)->count(),
            'contentTeamCount' => User::whereIn('role', [User::ROLE_CONTENT_MANAGER, User::ROLE_CONTRIBUTOR])->count(),
            'financeManagerCount' => User::where('role', User::ROLE_FINANCE_MANAGER)->count(),
            'onlineUsers' => $onlineUsersService->count($windowMinutes, $request),
        ]);
    }
}
