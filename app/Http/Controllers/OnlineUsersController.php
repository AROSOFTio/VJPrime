<?php

namespace App\Http\Controllers;

use App\Services\OnlineUsersService;
use Illuminate\Http\JsonResponse;

class OnlineUsersController extends Controller
{
    public function count(OnlineUsersService $onlineUsersService): JsonResponse
    {
        return response()->json([
            'online_users' => $onlineUsersService->count(5),
        ]);
    }
}
