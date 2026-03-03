<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PesapalService
{
    public function enabled(): bool
    {
        return (bool) config('pesapal.enabled')
            && filled(config('pesapal.consumer_key'))
            && filled(config('pesapal.consumer_secret'));
    }

    public function getCurrency(): string
    {
        return (string) config('pesapal.currency', 'UGX');
    }

    public function getPlans(): array
    {
        $plans = config('pesapal.plans', []);

        if (! is_array($plans)) {
            return [];
        }

        return collect($plans)
            ->filter(fn ($plan) => is_array($plan))
            ->map(function (array $plan, string $key) {
                return [
                    'code' => (string) ($plan['code'] ?? $key),
                    'name' => (string) ($plan['name'] ?? strtoupper($key)),
                    'amount' => (float) ($plan['amount'] ?? 0),
                    'days' => max((int) ($plan['days'] ?? 1), 1),
                ];
            })
            ->filter(fn (array $plan) => $plan['amount'] > 0)
            ->values()
            ->all();
    }

    public function getPlan(string $code): ?array
    {
        return collect($this->getPlans())
            ->first(fn (array $plan) => $plan['code'] === $code);
    }

    public function getDefaultPlan(): ?array
    {
        $defaultCode = (string) config('pesapal.default_plan', 'daily');
        $defaultPlan = $this->getPlan($defaultCode);

        if ($defaultPlan) {
            return $defaultPlan;
        }

        return $this->getPlans()[0] ?? null;
    }

    public function getDefaultAmount(): float
    {
        return (float) ($this->getDefaultPlan()['amount'] ?? 10000);
    }

    public function getCallbackUrl(): string
    {
        return (string) config('pesapal.callback_url', route('billing.pesapal.callback'));
    }

    public function getIpnUrl(): string
    {
        return (string) config('pesapal.ipn_url', route('billing.pesapal.ipn'));
    }

    public function requestToken(): string
    {
        $response = $this->client()
            ->post('/api/Auth/RequestToken', [
                'consumer_key' => config('pesapal.consumer_key'),
                'consumer_secret' => config('pesapal.consumer_secret'),
            ]);

        $response->throw();

        $token = $response->json('token');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Pesapal token missing in auth response.');
        }

        return $token;
    }

    public function ensureIpnId(string $token, string $ipnUrl): string
    {
        if ($configured = config('pesapal.notification_id')) {
            return (string) $configured;
        }

        return Cache::remember('pesapal:ipn_id', now()->addDay(), function () use ($token, $ipnUrl) {
            $register = $this->authorized($token)->post('/api/URLSetup/RegisterIPN', [
                'url' => $ipnUrl,
                'ipn_notification_type' => config('pesapal.notification_type', 'GET'),
            ]);

            if ($register->successful() && $register->json('ipn_id')) {
                return (string) $register->json('ipn_id');
            }

            $list = $this->authorized($token)->get('/api/URLSetup/GetIpnList');
            if (! $list->successful()) {
                $register->throw();
            }

            $match = collect($list->json())
                ->first(fn ($item) => ($item['url'] ?? null) === $ipnUrl);

            if (! $match || ! isset($match['ipn_id'])) {
                throw new RuntimeException('Unable to register or resolve Pesapal IPN ID.');
            }

            return (string) $match['ipn_id'];
        });
    }

    public function submitOrder(string $token, array $payload): array
    {
        $response = $this->authorized($token)
            ->post('/api/Transactions/SubmitOrderRequest', $payload);

        $response->throw();

        return $response->json();
    }

    public function getTransactionStatus(string $token, string $orderTrackingId): array
    {
        $response = $this->authorized($token)
            ->get('/api/Transactions/GetTransactionStatus', [
                'orderTrackingId' => $orderTrackingId,
            ]);

        $response->throw();

        return $response->json();
    }

    public function isPaid(array $statusPayload): bool
    {
        $status = strtoupper((string) ($statusPayload['status'] ?? ''));
        $paymentStatus = strtoupper((string) ($statusPayload['payment_status_description'] ?? $statusPayload['payment_status'] ?? ''));

        return in_array($status, ['COMPLETED', 'PAID', 'SUCCESS'], true)
            || in_array($paymentStatus, ['COMPLETED', 'PAID', 'SUCCESS'], true);
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl((string) config('pesapal.base_url'))
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }

    private function authorized(string $token): PendingRequest
    {
        return $this->client()->withToken($token);
    }
}
