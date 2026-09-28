<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Webhook;
use App\Models\Payment;
use App\Services\PaymentConfirmationService;
use Exception;

class StripeWebhookController extends Controller
{
    public function handle(Request $request, PaymentConfirmationService $confirmation, ?string $managementKey = null)
    {
        $configuration = app(\App\Services\PaymentConfiguration::class);
        $management = $configuration->webhookManagement($managementKey);
        $settings = $configuration->forManagement($management);
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        if (blank($settings['stripe']['webhook_secret'] ?? null)) {
            return response()->json(['error' => 'Webhook non configurato.'], 503);
        }
        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                ($settings['stripe']['webhook_secret'] ?? '')
            );
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            $payment = Payment::whereHas('reservation.parking', fn ($query) => $query->where('management_id', $management->id))->where('provider', 'stripe')
                ->where('provider_session_id', $session->id)
                ->first();

            if ($payment) {
                $payment->update([
                    'provider_payment_id' => $session->payment_intent ?? null,
                ]);

                // amount_total is already in cents
                $actualPaidAmountInCents = (int) ($session->amount_total ?? 0);
                $currency = $session->currency ?? 'EUR';

                try {
                    $confirmation->confirm($payment, $actualPaidAmountInCents, $currency, $session->toArray());
                } catch (\LogicException $e) {
                    \Illuminate\Support\Facades\Log::warning('Stripe Webhook LogicException: ' . $e->getMessage());
                } catch (Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Stripe Webhook Error: ' . $e->getMessage());
                    return response()->json(['error' => 'Internal Server Error'], 500); // force retry
                }
            }
        } elseif ($event->type === 'payment_intent.succeeded') {
            $intent = $event->data->object;

            $payment = Payment::whereHas('reservation.parking', fn ($query) => $query->where('management_id', $management->id))->where('provider', 'stripe')
                ->where('provider_payment_id', $intent->id)
                ->first();

            if (!$payment && isset($intent->metadata->payment_id)) {
                $payment = Payment::whereHas('reservation.parking', fn ($query) => $query->where('management_id', $management->id))->where('provider', 'stripe')
                    ->where('id', $intent->metadata->payment_id)
                    ->first();
            }

            if ($payment) {
                $actualPaidAmountInCents = (int) ($intent->amount_received ?? 0);
                $currency = $intent->currency ?? 'EUR';

                try {
                    $confirmation->confirm($payment, $actualPaidAmountInCents, $currency, $intent->toArray());
                } catch (\LogicException $e) {
                    \Illuminate\Support\Facades\Log::warning('Stripe Webhook LogicException: ' . $e->getMessage());
                } catch (Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Stripe Webhook Error: ' . $e->getMessage());
                    return response()->json(['error' => 'Internal Server Error'], 500); // force retry
                }
            }
        }

        return response()->json(['received' => true]);
    }
}
