<?php
namespace Briko\Payment\Drivers;

use Briko\Payment\PaymentDriverInterface;
use Briko\Payment\PaymentMessage;
use Briko\Payment\PaymentResult;

/**
 * MTN Mobile Money — Collection API (toute l'Afrique MTN)
 * Docs : https://momodeveloper.mtn.com/docs/services/collection
 *
 * .env requis :
 *   MTN_MOMO_SUBSCRIPTION_KEY=    Clé d'abonnement depuis le portail MTN
 *   MTN_MOMO_API_USER=            UUID créé via le portail (sandbox) ou fourni (prod)
 *   MTN_MOMO_API_KEY=             Clé API associée à l'API User
 *   MTN_MOMO_ENVIRONMENT=sandbox  sandbox | production
 *   MTN_MOMO_CURRENCY=XOF         Devise selon le pays
 *   MTN_MOMO_CALLBACK_URL=https://ton-site.ci/webhooks/payment
 */
class MtnMomoDriver extends AbstractDriver implements PaymentDriverInterface
{
    private function baseUrl(): string
    {
        $env = env('MTN_MOMO_ENVIRONMENT', 'sandbox');
        return $env === 'production'
            ? 'https://proxy.momoapi.mtn.com'
            : 'https://sandbox.momodeveloper.mtn.com';
    }

    public function initiate(PaymentMessage $message): PaymentResult
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return PaymentResult::fail('Impossible d\'obtenir le token MTN MoMo.');
        }

        $referenceId = $this->uuid();
        $currency    = env('MTN_MOMO_CURRENCY', $message->currency);
        $env         = env('MTN_MOMO_ENVIRONMENT', 'sandbox');

        $response = $this->post(
            $this->baseUrl() . '/collection/v1_0/requesttopay',
            [
                'amount'       => (string) $message->amount,
                'currency'     => $currency,
                'externalId'   => $message->reference ?: $referenceId,
                'payer'        => [
                    'partyIdType' => 'MSISDN',
                    'partyId'     => ltrim($message->phone, '+'),
                ],
                'payerMessage' => $message->description ?: 'Paiement',
                'payeeNote'    => env('APP_NAME', 'Brikocode'),
            ],
            [
                'Authorization: Bearer ' . $token,
                'X-Reference-Id: ' . $referenceId,
                'X-Target-Environment: ' . $env,
                'X-Callback-Url: ' . ($message->callbackUrl ?? env('MTN_MOMO_CALLBACK_URL', '')),
                'Ocp-Apim-Subscription-Key: ' . env('MTN_MOMO_SUBSCRIPTION_KEY', ''),
            ]
        );

        if ($response['code'] !== 202) {
            return PaymentResult::fail(
                $response['body']['message'] ?? 'Erreur MTN MoMo (code ' . $response['code'] . ')',
                $response['body']
            );
        }

        // MoMo renvoie 202 Accepted — le paiement est asynchrone (push vers le téléphone)
        return PaymentResult::pending(
            $referenceId,
            $message->amount,
            $currency,
            null,
            'Demande de paiement envoyée sur le téléphone ' . $message->phone . '. En attente de confirmation.',
            $response['body']
        );
    }

    public function verify(string $transactionId): PaymentResult
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return PaymentResult::fail('Impossible d\'obtenir le token MTN MoMo.');
        }

        $env      = env('MTN_MOMO_ENVIRONMENT', 'sandbox');
        $response = $this->get(
            $this->baseUrl() . '/collection/v1_0/requesttopay/' . $transactionId,
            [
                'Authorization: Bearer ' . $token,
                'X-Target-Environment: ' . $env,
                'Ocp-Apim-Subscription-Key: ' . env('MTN_MOMO_SUBSCRIPTION_KEY', ''),
            ]
        );

        $body   = $response['body'];
        $status = strtoupper($body['status'] ?? '');

        if ($status === 'SUCCESSFUL') {
            return PaymentResult::ok(
                $transactionId,
                (int) ($body['amount'] ?? 0),
                $body['currency'] ?? 'XOF',
                null,
                'Paiement MTN MoMo confirmé',
                $body
            );
        }

        if ($status === 'PENDING') {
            return PaymentResult::pending($transactionId, 0, 'XOF', null, 'En attente', $body);
        }

        return PaymentResult::fail($body['reason'] ?? $body['message'] ?? 'Paiement refusé ou expiré', $body);
    }

    private function getAccessToken(): ?string
    {
        $user = env('MTN_MOMO_API_USER', '');
        $key  = env('MTN_MOMO_API_KEY', '');
        $sub  = env('MTN_MOMO_SUBSCRIPTION_KEY', '');
        $env  = env('MTN_MOMO_ENVIRONMENT', 'sandbox');

        $credentials = base64_encode("{$user}:{$key}");

        $response = $this->post(
            $this->baseUrl() . '/collection/token/',
            [],
            [
                'Authorization: Basic ' . $credentials,
                'Ocp-Apim-Subscription-Key: ' . $sub,
                'X-Target-Environment: ' . $env,
            ]
        );

        return $response['body']['access_token'] ?? null;
    }
}
