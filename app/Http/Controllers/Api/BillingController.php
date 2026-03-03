<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPayment;
use App\Services\PesapalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BillingController extends Controller
{
    public function __construct(private readonly PesapalService $pesapalService) {}

    public function checkout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isPremium()) {
            return response()->json([
                'message' => 'Account is already premium.',
                'subscription_status' => $user->subscription_status,
            ], 200);
        }

        if (! $this->pesapalService->enabled()) {
            return response()->json([
                'message' => 'Pesapal is not configured.',
            ], 422);
        }

        $token = $this->pesapalService->requestToken();
        $ipnUrl = route('billing.pesapal.ipn');
        $callbackUrl = route('billing.pesapal.callback');
        $ipnId = $this->pesapalService->ensureIpnId($token, $ipnUrl);

        $reference = 'VJP-'.strtoupper(Str::random(10));
        $amount = $this->pesapalService->getDefaultAmount();
        $currency = $this->pesapalService->getCurrency();

        $payload = [
            'id' => $reference,
            'currency' => $currency,
            'amount' => $amount,
            'description' => 'VJPrime Premium Subscription',
            'callback_url' => $callbackUrl,
            'notification_id' => $ipnId,
            'billing_address' => [
                'email_address' => $user->email,
                'phone_number' => null,
                'country_code' => 'UG',
                'first_name' => $user->name,
                'last_name' => '',
                'line_1' => 'VJPrime',
                'line_2' => '',
                'city' => 'Kampala',
                'state' => 'Kampala',
                'postal_code' => '00000',
                'zip_code' => '00000',
            ],
        ];

        $response = $this->pesapalService->submitOrder($token, $payload);

        $payment = SubscriptionPayment::create([
            'user_id' => $user->id,
            'provider' => 'pesapal',
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'reference' => $reference,
            'merchant_reference' => $response['merchant_reference'] ?? $reference,
            'order_tracking_id' => $response['order_tracking_id'] ?? null,
            'pesapal_tracking_id' => $response['tracking_id'] ?? null,
            'redirect_url' => $response['redirect_url'] ?? null,
            'callback_url' => $callbackUrl,
            'ipn_id' => $ipnId,
            'response_payload' => $response,
        ]);

        return response()->json([
            'message' => 'Checkout created.',
            'reference' => $payment->reference,
            'order_tracking_id' => $payment->order_tracking_id,
            'redirect_url' => $payment->redirect_url,
            'status' => $payment->status,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $payments = $request->user()
            ->subscriptionPayments()
            ->latest()
            ->paginate(20);

        return response()->json($payments);
    }
}
