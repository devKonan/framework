<?php
namespace Briko\Payment\Drivers;

use Briko\Payment\PaymentDriverInterface;
use Briko\Payment\PaymentMessage;
use Briko\Payment\PaymentResult;

/**
 * PayDunya — Sénégal, Mali, Burkina, Côte d'Ivoire
 * Docs : https://paydunya.com/developers
 *
 * .env requis :
 *   PAYDUNYA_MASTER_KEY=
 *   PAYDUNYA_PRIVATE_KEY=
 *   PAYDUNYA_TOKEN=
 *   PAYDUNYA_STORE_NAME=MonApp
 *   PAYDUNYA_ENV=test            test | live
 *   PAYDUNYA_CANCEL_URL=https://ton-site.ci/paiement/annule
 *   PAYDUNYA_RETURN_URL=https://ton-site.ci/paiement/retour
 *   PAYDUNYA_CALLBACK_URL=https://ton-site.ci/webhooks/payment
 */
class PayDunyaDriver extends AbstractDriver implements PaymentDriverInterface
{
    private function baseUrl(): string
    {
        return env('PAYDUNYA_ENV', 'test') === 'live'
            ? 'https://app.paydunya.com/api/v1'
            : 'https://app.paydunya.com/sandbox-api/v1';
    }

    private function headers(): array
    {
        return [
            'PAYDUNYA-MASTER-KEY: '  . env('PAYDUNYA_MASTER_KEY', ''),
            'PAYDUNYA-PRIVATE-KEY: ' . env('PAYDUNYA_PRIVATE_KEY', ''),
            'PAYDUNYA-TOKEN: '       . env('PAYDUNYA_TOKEN', ''),
            'Content-Type: application/json',
        ];
    }

    public function initiate(PaymentMessage $message): PaymentResult
    {
        if (!env('PAYDUNYA_MASTER_KEY') || !env('PAYDUNYA_PRIVATE_KEY') || !env('PAYDUNYA_TOKEN')) {
            return PaymentResult::fail('Clés PayDunya manquantes dans .env (PAYDUNYA_MASTER_KEY, PAYDUNYA_PRIVATE_KEY, PAYDUNYA_TOKEN)');
        }

        $response = $this->post($this->baseUrl() . '/checkout-invoice/create', [
            'invoice' => [
                'total_amount' => $message->amount,
                'description'  => $message->description ?: 'Paiement',
            ],
            'store' => [
                'name'        => env('PAYDUNYA_STORE_NAME', env('APP_NAME', 'Brikocode')),
                'website_url' => env('APP_URL', ''),
            ],
            'actions' => [
                'cancel_url'   => $message->cancelUrl   ?? env('PAYDUNYA_CANCEL_URL', ''),
                'return_url'   => $message->returnUrl   ?? env('PAYDUNYA_RETURN_URL', ''),
                'callback_url' => $message->callbackUrl ?? env('PAYDUNYA_CALLBACK_URL', ''),
            ],
            'custom_data' => array_merge(
                ['reference' => $message->reference],
                $message->metadata
            ),
        ], $this->headers());

        $body = $response['body'];

        if (($body['response_code'] ?? '') !== '00') {
            return PaymentResult::fail(
                $body['response_text'] ?? 'Erreur PayDunya',
                $body
            );
        }

        return PaymentResult::pending(
            $body['token'] ?? '',
            $message->amount,
            $message->currency,
            $body['invoice_url'] ?? null,
            'Redirige l\'utilisateur vers invoice_url.',
            $body
        );
    }

    public function verify(string $transactionId): PaymentResult
    {
        $response = $this->get(
            $this->baseUrl() . '/checkout-invoice/confirm/' . $transactionId,
            $this->headers()
        );

        $body   = $response['body'];
        $status = strtolower($body['status'] ?? '');

        if ($status === 'completed') {
            return PaymentResult::ok(
                $transactionId,
                (int) ($body['invoice']['total_amount'] ?? 0),
                'XOF',
                null,
                'Paiement PayDunya confirmé',
                $body
            );
        }

        if ($status === 'pending') {
            return PaymentResult::pending($transactionId, 0, 'XOF', null, 'En attente', $body);
        }

        return PaymentResult::fail($body['response_text'] ?? 'Paiement échoué', $body);
    }
}
