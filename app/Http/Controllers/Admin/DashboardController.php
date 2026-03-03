<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\User;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'movieCount' => Movie::count(),
            'publishedMovieCount' => Movie::where('status', 'published')->count(),
            'userCount' => User::where('role', 'user')->count(),
        ]);
    }
}
