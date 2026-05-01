<?php
namespace Briko\Payment;

class Payment
{
    /**
     * Crée un message de paiement fluent.
     *
     * pay(2500)->to('+2250701234567')->description('Commande #42')->send()
     */
    public static function amount(int $amount): PaymentMessage
    {
        return new PaymentMessage($amount);
    }

    /**
     * Vérifie l'état d'une transaction.
     */
    public static function verify(string $transactionId): PaymentResult
    {
        $msg = new PaymentMessage(0);
        return $msg->verify($transactionId);
    }

    /**
     * Retourne le nom du driver actif.
     */
    public static function driver(): string
    {
        return env('PAYMENT_DRIVER', 'log');
    }
}
