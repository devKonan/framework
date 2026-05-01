<?php
namespace Briko\Payment;

use Briko\Foundation\Logger;

/**
 * Gestionnaire de webhooks pour les callbacks asynchrones des providers de paiement.
 *
 * Usage dans app/routes.php :
 *   $router->post('/webhooks/payment', function ($req) {
 *       $result = WebhookHandler::handle($req->body(), $req->input('provider'));
 *       // Ton code métier : mettre à jour la commande, envoyer un SMS de confirmation…
 *       return ['ok' => true];
 *   });
 */
class WebhookHandler
{
    /**
     * Traite un webhook entrant et retourne un PaymentResult normalisé.
     *
     * @param array  $payload  Corps brut de la requête (json_decode)
     * @param string $provider Force le provider ; sinon auto-détecté depuis le payload
     */
    public static function handle(array $payload, string $provider = ''): PaymentResult
    {
        $driver = $provider ?: self::detect($payload);

        Logger::channel('payment')->info('Webhook reçu', [
            'driver'  => $driver,
            'payload' => $payload,
        ]);

        return match ($driver) {
            'orangemoney' => self::parseOrangeMoney($payload),
            'mtnmomo'     => self::parseMtnMomo($payload),
            'wave'        => self::parseWave($payload),
            'cinetpay'    => self::parseCinetPay($payload),
            'paydunya'    => self::parsePayDunya($payload),
            'stripe'      => self::parseStripe($payload),
            default       => self::parseGeneric($payload),
        };
    }

    /**
     * Vérifie la signature d'un webhook Stripe.
     */
    public static function verifyStripeSignature(string $rawBody, string $sigHeader): bool
    {
        $secret = env('STRIPE_WEBHOOK_SECRET', '');
        if (!$secret) return true;

        $parts     = [];
        foreach (explode(',', $sigHeader) as $part) {
            [$k, $v]    = explode('=', $part, 2);
            $parts[$k][] = $v;
        }

        $timestamp    = $parts['t'][0] ?? 0;
        $expectedSig  = hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret);
        $receivedSigs = $parts['v1'] ?? [];

        foreach ($receivedSigs as $sig) {
            if (hash_equals($expectedSig, $sig)) return true;
        }

        return false;
    }

    // ─── Parsers par provider ────────────────────────────────────────────────

    private static function parseOrangeMoney(array $p): PaymentResult
    {
        $status = strtoupper($p['status'] ?? '');

        if ($status === 'SUCCESSFUL') {
            return PaymentResult::ok(
                $p['pay_token'] ?? $p['order_id'] ?? '',
                (int) ($p['amount'] ?? 0),
                'XOF', null, 'Orange Money — paiement confirmé', $p
            );
        }

        return PaymentResult::fail('Orange Money — ' . ($p['message'] ?? 'Échec'), $p);
    }

    private static function parseMtnMomo(array $p): PaymentResult
    {
        $status = strtoupper($p['status'] ?? '');

        if ($status === 'SUCCESSFUL') {
            return PaymentResult::ok(
                $p['financialTransactionId'] ?? $p['referenceId'] ?? '',
                (int) ($p['amount'] ?? 0),
                $p['currency'] ?? 'XOF', null, 'MTN MoMo — paiement confirmé', $p
            );
        }

        if ($status === 'PENDING') {
            return PaymentResult::pending(
                $p['referenceId'] ?? '', 0, 'XOF', null, 'MTN MoMo — en attente', $p
            );
        }

        return PaymentResult::fail('MTN MoMo — ' . ($p['reason'] ?? 'Échec'), $p);
    }

    private static function parseWave(array $p): PaymentResult
    {
        $status = strtolower($p['payment_status'] ?? $p['status'] ?? '');

        if ($status === 'succeeded' || $status === 'complete') {
            return PaymentResult::ok(
                $p['id'] ?? '', (int) ($p['amount'] ?? 0),
                $p['currency'] ?? 'XOF', null, 'Wave — paiement confirmé', $p
            );
        }

        return PaymentResult::fail('Wave — ' . ($p['message'] ?? 'Échec'), $p);
    }

    private static function parseCinetPay(array $p): PaymentResult
    {
        $status = strtoupper($p['cpm_result'] ?? $p['status'] ?? '');

        if ($status === '00' || $status === 'ACCEPTED') {
            return PaymentResult::ok(
                $p['cpm_trans_id'] ?? '', (int) ($p['cpm_amount'] ?? 0),
                $p['cpm_currency'] ?? 'XOF', null, 'CinetPay — paiement confirmé', $p
            );
        }

        return PaymentResult::fail('CinetPay — ' . ($p['cpm_error_message'] ?? 'Échec'), $p);
    }

    private static function parsePayDunya(array $p): PaymentResult
    {
        $status = strtolower($p['status'] ?? '');

        if ($status === 'completed') {
            return PaymentResult::ok(
                $p['invoice']['token'] ?? '', (int) ($p['invoice']['total_amount'] ?? 0),
                'XOF', null, 'PayDunya — paiement confirmé', $p
            );
        }

        return PaymentResult::fail('PayDunya — ' . ($p['response_text'] ?? 'Échec'), $p);
    }

    private static function parseStripe(array $p): PaymentResult
    {
        $type   = $p['type'] ?? '';
        $object = $p['data']['object'] ?? [];

        if ($type === 'checkout.session.completed'
            || ($type === 'payment_intent.succeeded')) {
            return PaymentResult::ok(
                $object['id'] ?? '',
                (int) (($object['amount_total'] ?? $object['amount'] ?? 0) / 100),
                strtoupper($object['currency'] ?? 'USD'),
                null, 'Stripe — paiement confirmé', $p
            );
        }

        return PaymentResult::fail('Stripe — événement : ' . $type, $p);
    }

    private static function parseGeneric(array $p): PaymentResult
    {
        return PaymentResult::pending('unknown', 0, 'XOF', null, 'Webhook reçu — provider inconnu', $p);
    }

    // ─── Auto-détection du provider ──────────────────────────────────────────

    private static function detect(array $payload): string
    {
        // Stripe a toujours un champ 'type' comme 'checkout.session.completed'
        if (isset($payload['type']) && str_contains($payload['type'], '.')) {
            return 'stripe';
        }

        // MTN MoMo a 'financialTransactionId' ou 'referenceId'
        if (isset($payload['financialTransactionId']) || isset($payload['referenceId'])) {
            return 'mtnmomo';
        }

        // Wave a 'wave_launch_url' ou 'payment_status'
        if (isset($payload['wave_launch_url']) || isset($payload['wave_client_id'])) {
            return 'wave';
        }

        // CinetPay a 'cpm_trans_id'
        if (isset($payload['cpm_trans_id'])) {
            return 'cinetpay';
        }

        // Orange Money a 'pay_token' ou 'merchant_key'
        if (isset($payload['pay_token']) || isset($payload['merchant_key'])) {
            return 'orangemoney';
        }

        // PayDunya a 'invoice' objet
        if (isset($payload['invoice']) && is_array($payload['invoice'])) {
            return 'paydunya';
        }

        return env('PAYMENT_DRIVER', 'log');
    }
}
