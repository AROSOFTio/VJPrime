<?php

namespace App\Services;

use Illuminate\Http\Request;

class DeviceFingerprintService
{
    public function fromRequest(Request $request): string
    {
        $deviceId = trim((string) $request->header('X-Device-Id', ''));

        if ($deviceId === '') {
            $deviceId = implode('|', [
                $request->ip() ?? 'unknown-ip',
                substr((string) $request->userAgent(), 0, 240),
            ]);
        }

        return hash('sha256', $deviceId);
    }
}
