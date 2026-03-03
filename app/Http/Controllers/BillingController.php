<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPayment;
use App\Services\PesapalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class BillingController extends Controller
{
    public function __construct(private readonly PesapalService $pesapalService) {}

    public function upgrade(Request $request)
    {
        $payments = $request->user()
            ->subscriptionPayments()
            ->latest()
            ->take(10)
            ->get();

        return view('billing.upgrade', [
            'payments' => $payments,
            'isPesapalEnabled' => $this->pesapalService->enabled(),
            'currency' => $this->pesapalService->getCurrency(),
            'amount' => $this->pesapalService->getDefaultAmount(),
        ]);
    }

    public function checkout(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isPremium()) {
            return redirect()->route('account.index')->with('status', 'Account is already premium.');
        }

        if (! $this->pesapalService->enabled()) {
            return redirect()->route('billing.upgrade')->with('error', 'Pesapal is not configured yet.');
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

        if (! $payment->redirect_url) {
            return redirect()->route('billing.upgrade')->with('error', 'Pesapal did not return a redirect URL.');
        }

        return redirect()->away($payment->redirect_url);
    }

    public function callback(Request $request): RedirectResponse
    {
        $payment = $this->resolvePayment($request);

        if (! $payment) {
            return redirect()->route('account.index')->with('error', 'Payment reference not found.');
        }

        $this->syncPaymentStatus($payment);

        if ($payment->status === 'paid') {
            return redirect()->route('account.index')->with('status', 'Payment confirmed. Premium activated.');
        }

        return redirect()->route('account.index')->with('error', 'Payment is not completed yet.');
    }

    public function ipn(Request $request): Response
    {
        $payment = $this->resolvePayment($request);
        if ($payment) {
            $this->syncPaymentStatus($payment);
        }

        return response('IPN RECEIVED', 200);
    }

    private function resolvePayment(Request $request): ?SubscriptionPayment
    {
        $trackingId = $request->query('OrderTrackingId', $request->input('OrderTrackingId'));
        $merchantReference = $request->query('OrderMerchantReference', $request->input('OrderMerchantReference'));

        if (! $trackingId && ! $merchantReference) {
            return null;
        }

        $query = SubscriptionPayment::query()->where(function ($builder) use ($trackingId, $merchantReference) {
            if ($trackingId) {
                $builder->where('order_tracking_id', $trackingId);
            }

            if ($merchantReference) {
                $builder->orWhere('merchant_reference', $merchantReference)
                    ->orWhere('reference', $merchantReference);
            }
        });

        return $query->latest()->first();
    }

    private function syncPaymentStatus(SubscriptionPayment $payment): void
    {
        if (! $payment->order_tracking_id) {
            return;
        }

        try {
            $token = $this->pesapalService->requestToken();
            $statusPayload = $this->pesapalService->getTransactionStatus($token, $payment->order_tracking_id);

            $payment->status_payload = $statusPayload;

            if ($this->pesapalService->isPaid($statusPayload)) {
                $payment->status = 'paid';
                $payment->confirmed_at = now();
                $payment->user()->update(['subscription_status' => 'premium']);
            } else {
                $payment->status = strtolower((string) ($statusPayload['status'] ?? 'pending'));
            }

            $payment->save();
        } catch (\Throwable $exception) {
            Log::warning('Pesapal status sync failed', [
                'payment_id' => $payment->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
