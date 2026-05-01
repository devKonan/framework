<?php
namespace Briko\Payment\Drivers;

use Briko\Payment\PaymentDriverInterface;
use Briko\Payment\PaymentMessage;
use Briko\Payment\PaymentResult;

/**
 * Stripe — International (cartes, SEPA, etc.)
 * Docs : https://stripe.com/docs/api/payment_intents
 *
 * .env requis :
 *   STRIPE_SECRET_KEY=sk_test_...
 *   STRIPE_WEBHOOK_SECRET=whsec_...  (optionnel, pour vérifier les webhooks)
 *   STRIPE_SUCCESS_URL=https://ton-site.ci/paiement/succes
 *   STRIPE_CANCEL_URL=https://ton-site.ci/paiement/annule
 */
class StripeDriver extends AbstractDriver implements PaymentDriverInterface
{
    private const INTENT_URL  = 'https://api.stripe.com/v1/payment_intents';
    private const SESSION_URL = 'https://api.stripe.com/v1/checkout/sessions';

    public function initiate(PaymentMessage $message): PaymentResult
    {
        $secretKey = env('STRIPE_SECRET_KEY', '');
        if (!$secretKey) {
            return PaymentResult::fail('STRIPE_SECRET_KEY manquant dans .env');
        }

        // Stripe Checkout Session (redirection avec page hébergée par Stripe)
        $response = $this->postForm(self::SESSION_URL, [
            'payment_method_types[]' => 'card',
            'line_items[0][price_data][currency]'                   => strtolower($message->currency),
            'line_items[0][price_data][unit_amount]'                => $message->amount * 100, // centimes
            'line_items[0][price_data][product_data][name]'        => $message->description ?: 'Paiement',
            'line_items[0][quantity]'                               => 1,
            'mode'                   => 'payment',
            'success_url'            => $message->returnUrl ?? env('STRIPE_SUCCESS_URL', ''),
            'cancel_url'             => $message->cancelUrl ?? env('STRIPE_CANCEL_URL', ''),
            'customer_email'         => $message->email ?: null,
            'client_reference_id'    => $message->reference ?: null,
        ], [
            'Authorization: Bearer ' . $secretKey,
        ]);

        $body = $response['body'];

        if ($response['code'] !== 200) {
            return PaymentResult::fail(
                $body['error']['message'] ?? 'Erreur Stripe',
                $body
            );
        }

        return PaymentResult::pending(
            $body['id'] ?? '',
            $message->amount,
            $message->currency,
            $body['url'] ?? null,
            'Redirige l\'utilisateur vers url (page Stripe).',
            $body
        );
    }

    public function verify(string $transactionId): PaymentResult
    {
        $secretKey = env('STRIPE_SECRET_KEY', '');

        $response = $this->get(
            self::SESSION_URL . '/' . $transactionId,
            ['Authorization: Bearer ' . $secretKey]
        );

        $body   = $response['body'];
        $status = strtolower($body['payment_status'] ?? '');

        if ($status === 'paid') {
            return PaymentResult::ok(
                $transactionId,
                (int) (($body['amount_total'] ?? 0) / 100), // de centimes → unité
                strtoupper($body['currency'] ?? 'USD'),
                null,
                'Paiement Stripe confirmé',
                $body
            );
        }

        if ($status === 'unpaid' || $status === 'no_payment_required') {
            return PaymentResult::pending($transactionId, 0, 'USD', null, 'En attente', $body);
        }

        return PaymentResult::fail($body['error']['message'] ?? 'Paiement Stripe échoué', $body);
    }
}
