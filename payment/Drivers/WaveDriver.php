<?php
namespace Briko\Payment\Drivers;

use Briko\Payment\PaymentDriverInterface;
use Briko\Payment\PaymentMessage;
use Briko\Payment\PaymentResult;

/**
 * Wave — Côte d'Ivoire & Sénégal
 * Docs : https://docs.wave.com/api
 *
 * .env requis :
 *   WAVE_API_KEY=           Clé secrète depuis le portail Wave Business
 *   WAVE_SUCCESS_URL=https://ton-site.ci/paiement/succes
 *   WAVE_ERROR_URL=https://ton-site.ci/paiement/erreur
 *   WAVE_CALLBACK_URL=https://ton-site.ci/webhooks/payment
 */
class WaveDriver extends AbstractDriver implements PaymentDriverInterface
{
    private const API_URL = 'https://api.wave.com/v1/checkout/sessions';

    public function initiate(PaymentMessage $message): PaymentResult
    {
        $apiKey = env('WAVE_API_KEY', '');
        if (!$apiKey) {
            return PaymentResult::fail('WAVE_API_KEY manquant dans .env');
        }

        $response = $this->post(self::API_URL, [
            'amount'           => (string) $message->amount,
            'currency'         => $message->currency,
            'client_reference' => $message->reference ?: uniqid('WAVE-'),
            'success_url'      => $message->returnUrl   ?? env('WAVE_SUCCESS_URL', ''),
            'error_url'        => $message->cancelUrl   ?? env('WAVE_ERROR_URL', ''),
            'webhook'          => $message->callbackUrl ?? env('WAVE_CALLBACK_URL', ''),
        ], [
            'Authorization: Bearer ' . $apiKey,
        ]);

        $body = $response['body'];

        if ($response['code'] !== 200 && $response['code'] !== 201) {
            return PaymentResult::fail(
                $body['message'] ?? $body['code'] ?? 'Erreur Wave',
                $body
            );
        }

        return PaymentResult::pending(
            $body['id'] ?? ($message->reference ?: uniqid('WAVE-')),
            $message->amount,
            $message->currency,
            $body['wave_launch_url'] ?? null,
            'Redirige l\'utilisateur vers wave_launch_url pour finaliser le paiement.',
            $body
        );
    }

    public function verify(string $transactionId): PaymentResult
    {
        $apiKey = env('WAVE_API_KEY', '');

        $response = $this->get(
            'https://api.wave.com/v1/checkout/sessions/' . $transactionId,
            ['Authorization: Bearer ' . $apiKey]
        );

        $body   = $response['body'];
        $status = strtolower($body['payment_status'] ?? '');

        if ($status === 'succeeded' || $status === 'complete') {
            return PaymentResult::ok(
                $transactionId,
                (int) ($body['amount'] ?? 0),
                $body['currency'] ?? 'XOF',
                null,
                'Paiement Wave confirmé',
                $body
            );
        }

        if ($status === 'processing' || $status === 'pending') {
            return PaymentResult::pending($transactionId, 0, 'XOF', null, 'En attente', $body);
        }

        return PaymentResult::fail($body['message'] ?? 'Paiement Wave échoué', $body);
    }
}
