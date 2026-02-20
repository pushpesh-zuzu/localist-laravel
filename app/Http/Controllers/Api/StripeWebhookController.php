<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Stripe\Webhook;
use App\Models\PurchaseHistory;
use App\Models\PlanHistory;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Helpers\CustomHelper;
use App\Helpers\Zoho\ZohoFinance;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        Stripe::setApiKey(CustomHelper::setting_value('stripe_secret'));

        // webhook secret alag use hoga
        $secret = CustomHelper::setting_value('stripe_webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Exception $e) {
            //  Log::error('Stripe webhook signature failed: ' . $e->getMessage());
            return response('Invalid signature', 400);
        }

        switch ($event->type) {

            case 'charge.refunded':

                $charge = $event->data->object;
                $paymentIntentId = $charge->payment_intent;

                $this->handleRefund($paymentIntentId, $charge);

                break;

            case 'refund.created':
                Log::info('Refund created event received');
                break;
        }

        return response()->json(['status' => 'success']);
    }

    private function handleRefund($paymentIntentId, $charge)
    {
        try {

            $plan = PlanHistory::where('stripe_payment_intent', $paymentIntentId)->first();

            if (!$plan) {
                Log::warning('Purchase not found for refund: ' . $paymentIntentId);
                return;
            }

            // full vs partial refund
            $isFullRefund = ($charge->amount_refunded == $charge->amount);

            $user = User::find($plan->user_id);

            $refundType = $isFullRefund ? 'Full refund' : 'Partial refund';

            $purchaseTypeFormatted = $plan->purchase_type == 'manual'  ? '' : ucwords(str_replace('_', ' ', $plan->purchase_type));

            $details = trim("{$plan->plan_name} {$plan->credits} {$purchaseTypeFormatted} credits purchased | {$refundType} processed on " . now()->toDateTimeString());
           
            if ($isFullRefund) {
                $user->decrement('total_credit', $plan->credits);
                $creditsToReverse = $plan->credits;
            } else {
                $creditsToReverse = 0; // Partial refund me credits unchanged
            }

            $transactionId =  PurchaseHistory::insertGetId([
                'user_id'        => $user->id,
                'purchase_date'  => now()->toDateString(),
                'price' => number_format($charge->amount_refunded / 100, 2, '.', ''),
                'credits'        => $creditsToReverse,
                'details'        => $details,
                'payment_type'   => 2,
                'error_response' => '',
                'status'         => 1,
                'created_at'     => now(),
                'updated_at'   => now(),
            ]);

            $userId = $user->id;

            if ($transactionId) {
                CustomHelper::runInBackground(function () use ($userId, $transactionId) {
                    app(ZohoFinance::class)->integratePurchaseHistory($userId, $transactionId);
                });
            }


            // Log::info('Refund processed successfully for user: ' . $user->id);
        } catch (\Exception $e) {
            Log::error('Refund handling error: ' . $e->getMessage());
        }
    }
}
