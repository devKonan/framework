<?php
namespace Briko\Payment\Drivers;

use Briko\Payment\PaymentDriverInterface;
use Briko\Payment\PaymentMessage;
use Briko\Payment\PaymentResult;
use Briko\Foundation\Logger;

class LogDriver extends AbstractDriver implements PaymentDriverInterface
{
    public function initiate(PaymentMessage $message): PaymentResult
    {
        $txId = 'LOG-' . strtoupper(substr(md5(uniqid()), 0, 12));

        Logger::channel('payment')->info('[LOG DRIVER] Paiement simulé', [
            'txId'        => $txId,
            'amount'      => $message->amount,
            'currency'    => $message->currency,
            'phone'       => $message->phone,
            'description' => $message->description,
            'reference'   => $message->reference,
        ]);

        return PaymentResult::pending(
            $txId,
            $message->amount,
            $message->currency,
            null,
            "[LOG] Paiement simulé — aucun vrai débit effectué. Utilisez un driver réel en production."
        );
    }

    public function verify(string $transactionId): PaymentResult
    {
        Logger::channel('payment')->info('[LOG DRIVER] Vérification simulée', [
            'txId' => $transactionId,
        ]);

        return PaymentResult::ok(
            $transactionId,
            0,
            env('PAYMENT_CURRENCY', 'XOF'),
            null,
            "[LOG] Vérification simulée — transaction considérée comme réussie."
        );
    }
}
