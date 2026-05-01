<?php
namespace Briko\Payment\Drivers;

use Briko\Payment\PaymentDriverInterface;
use Briko\Payment\PaymentMessage;
use Briko\Payment\PaymentResult;

/**
 * Orange Money Web Payment — Côte d'Ivoire & zone UEMOA
 * Docs : https://developer.orange.com/apis/orange-money-webpay-ci
 *
 * .env requis :
 *   ORANGE_MONEY_CLIENT_ID=
 *   ORANGE_MONEY_CLIENT_SECRET=
 *   ORANGE_MONEY_MERCHANT_KEY=
 *   ORANGE_MONEY_RETURN_URL=https://ton-site.ci/paiement/retour
 *   ORANGE_MONEY_CANCEL_URL=https://ton-site.ci/paiement/annule
 *   ORANGE_MONEY_NOTIF_URL=https://ton-site.ci/webhooks/payment
 */
class OrangeMoneyDriver extends AbstractDriver implements PaymentDriverInterface
{
    private const TOKEN_URL  = 'https://api.orange.com/oauth/v3/token';
    private const PAY_URL    = 'https://api.orange.com/orange-money-webpay/dev/v1/webpayment';
    private const STATUS_URL = 'https://api.orange.com/orange-money-webpay/dev/v1/transactionstatus';

    public function initiate(PaymentMessage $message): PaymentResult
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return PaymentResult::fail('Impossible d\'obtenir le token Orange Money.');
        }

        $orderId = $message->reference ?: uniqid('OM-');

        $response = $this->post(self::PAY_URL, [
            'merchant_key' => env('ORANGE_MONEY_MERCHANT_KEY'),
            'currency'     => 'OIC',
            'order_id'     => $orderId,
            'amount'       => $message->amount,
            'return_url'   => $message->returnUrl   ?? env('ORANGE_MONEY_RETURN_URL', ''),
            'cancel_url'   => $message->cancelUrl   ?? env('ORANGE_MONEY_CANCEL_URL', ''),
            'notif_url'    => $message->callbackUrl ?? env('ORANGE_MONEY_NOTIF_URL', ''),
            'lang'         => 'fr',
        ], [
            'Authorization: Bearer ' . $token,
        ]);

        $body = $response['body'];

        if (($response['code'] !== 200 && $response['code'] !== 201)
            || ($body['status'] ?? '') !== 'SUCCESS') {
            return PaymentResult::fail(
                $body['message'] ?? 'Erreur Orange Money',
                $body
            );
        }

        return PaymentResult::pending(
            $body['pay_token']   ?? $orderId,
            $message->amount,
            $message->currency,
            $body['payment_url'] ?? null,
            'Redirige l\'utilisateur vers payment_url pour finaliser le paiement.',
            $body
        );
    }

    public function verify(string $transactionId): PaymentResult
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return PaymentResult::fail('Impossible d\'obtenir le token Orange Money.');
        }

        $response = $this->post(self::STATUS_URL, [
            'order_id'     => $transactionId,
            'merchant_key' => env('ORANGE_MONEY_MERCHANT_KEY'),
        ], [
            'Authorization: Bearer ' . $token,
        ]);

        $body   = $response['body'];
        $status = strtolower($body['status'] ?? '');

        if ($status === 'successful' || $status === 'success') {
            return PaymentResult::ok(
                $transactionId,
                (int) ($body['amount'] ?? 0),
                $message->currency ?? 'XOF',
                null,
                'Paiement confirmé',
                $body
            );
        }

        if ($status === 'initiated' || $status === 'pending') {
            return PaymentResult::pending($transactionId, 0, 'XOF', null, 'En attente', $body);
        }

        return PaymentResult::fail($body['message'] ?? 'Paiement échoué', $body);
    }

    private function getAccessToken(): ?string
    {
        $clientId     = env('ORANGE_MONEY_CLIENT_ID', '');
        $clientSecret = env('ORANGE_MONEY_CLIENT_SECRET', '');
        $credentials  = base64_encode("{$clientId}:{$clientSecret}");

        $response = $this->postForm(self::TOKEN_URL, [
            'grant_type' => 'client_credentials',
        ], [
            'Authorization: Basic ' . $credentials,
        ]);

        return $response['body']['access_token'] ?? null;
    }
}
