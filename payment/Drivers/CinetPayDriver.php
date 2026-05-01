<?php
namespace Briko\Payment\Drivers;

use Briko\Payment\PaymentDriverInterface;
use Briko\Payment\PaymentMessage;
use Briko\Payment\PaymentResult;

/**
 * CinetPay — Multi-pays : CI, SN, CM, BF, ML, TG, GN, MG
 * Docs : https://docs.cinetpay.com/
 *
 * .env requis :
 *   CINETPAY_API_KEY=
 *   CINETPAY_SITE_ID=
 *   CINETPAY_NOTIFY_URL=https://ton-site.ci/webhooks/payment
 *   CINETPAY_RETURN_URL=https://ton-site.ci/paiement/retour
 */
class CinetPayDriver extends AbstractDriver implements PaymentDriverInterface
{
    private const INIT_URL   = 'https://api-checkout.cinetpay.com/v2/payment';
    private const VERIFY_URL = 'https://api-checkout.cinetpay.com/v2/payment/check';

    public function initiate(PaymentMessage $message): PaymentResult
    {
        $apiKey = env('CINETPAY_API_KEY', '');
        $siteId = env('CINETPAY_SITE_ID', '');

        if (!$apiKey || !$siteId) {
            return PaymentResult::fail('CINETPAY_API_KEY ou CINETPAY_SITE_ID manquant dans .env');
        }

        $txId = $message->reference ?: 'CP-' . strtoupper(substr(md5(uniqid()), 0, 10));

        $response = $this->post(self::INIT_URL, [
            'apikey'                  => $apiKey,
            'site_id'                 => $siteId,
            'transaction_id'          => $txId,
            'amount'                  => $message->amount,
            'currency'                => $message->currency,
            'description'             => $message->description ?: 'Paiement ' . env('APP_NAME', 'Brikocode'),
            'return_url'              => $message->returnUrl   ?? env('CINETPAY_RETURN_URL', ''),
            'notify_url'              => $message->callbackUrl ?? env('CINETPAY_NOTIFY_URL', ''),
            'customer_phone_number'   => $message->phone,
            'customer_name'           => strtok($message->name, ' ') ?: 'Client',
            'customer_surname'        => substr(strstr($message->name, ' '), 1) ?: '',
            'customer_email'          => $message->email ?: '',
            'channels'                => 'ALL',
            'lang'                    => 'fr',
        ]);

        $body = $response['body'];
        $code = $body['code'] ?? '';

        if ($code !== '201') {
            return PaymentResult::fail(
                $body['message'] ?? 'Erreur CinetPay (code ' . ($response['code']) . ')',
                $body
            );
        }

        return PaymentResult::pending(
            $txId,
            $message->amount,
            $message->currency,
            $body['data']['payment_url'] ?? null,
            'Redirige l\'utilisateur vers payment_url pour finaliser le paiement.',
            $body
        );
    }

    public function verify(string $transactionId): PaymentResult
    {
        $response = $this->post(self::VERIFY_URL, [
            'apikey'         => env('CINETPAY_API_KEY', ''),
            'site_id'        => env('CINETPAY_SITE_ID', ''),
            'transaction_id' => $transactionId,
        ]);

        $body   = $response['body'];
        $status = strtoupper($body['data']['status'] ?? '');

        if ($status === 'ACCEPTED') {
            return PaymentResult::ok(
                $transactionId,
                (int) ($body['data']['amount'] ?? 0),
                $body['data']['currency'] ?? 'XOF',
                null,
                'Paiement CinetPay confirmé',
                $body
            );
        }

        if ($status === 'CREATED' || $status === 'PENDING') {
            return PaymentResult::pending($transactionId, 0, 'XOF', null, 'En attente', $body);
        }

        return PaymentResult::fail($body['message'] ?? 'Paiement CinetPay refusé', $body);
    }
}
