<?php

namespace App\Http\Controllers;

use App\Services\OnlineUsersService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnlineUsersController extends Controller
{
    public function count(Request $request, OnlineUsersService $onlineUsersService): JsonResponse
    {
        $windowMinutes = max(1, (int) config('streaming.online.window_minutes', 5));

        return response()->json([
            'online_users' => $onlineUsersService->count($windowMinutes, $request),
        ], 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }
}
