<?php
namespace Briko\Payment;

interface PaymentDriverInterface
{
    /**
     * Initie un paiement et retourne le résultat.
     * Pour les providers à redirection (Orange, Wave, CinetPay…), le résultat
     * contient un paymentUrl vers lequel rediriger l'utilisateur.
     * Pour les providers push (MTN MoMo), le statut sera 'pending'.
     */
    public function initiate(PaymentMessage $message): PaymentResult;

    /**
     * Vérifie l'état d'une transaction par son ID.
     */
    public function verify(string $transactionId): PaymentResult;
}
