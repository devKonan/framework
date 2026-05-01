<?php
namespace Briko\Payment;

class PaymentResult
{
    public bool    $success       = false;
    public string  $transactionId = '';
    public string  $status        = 'failed';   // pending | success | failed | cancelled
    public int     $amount        = 0;
    public string  $currency      = 'XOF';
    public string  $message       = '';
    public ?string $paymentUrl    = null;        // URL de redirection (Orange, Wave, CinetPay…)
    public array   $raw           = [];

    public function isOk(): bool      { return $this->success; }
    public function isPending(): bool { return $this->status === 'pending'; }
    public function isFailed(): bool  { return $this->status === 'failed'; }

    // ─── Factory methods ──────────────────────────────────────────────────────

    public static function ok(
        string  $transactionId,
        int     $amount,
        string  $currency    = 'XOF',
        ?string $paymentUrl  = null,
        string  $message     = 'Paiement initié',
        array   $raw         = []
    ): static {
        $r                = new static();
        $r->success       = true;
        $r->transactionId = $transactionId;
        $r->status        = 'success';
        $r->amount        = $amount;
        $r->currency      = $currency;
        $r->paymentUrl    = $paymentUrl;
        $r->message       = $message;
        $r->raw           = $raw;
        return $r;
    }

    public static function pending(
        string  $transactionId,
        int     $amount,
        string  $currency   = 'XOF',
        ?string $paymentUrl = null,
        string  $message    = 'En attente de confirmation',
        array   $raw        = []
    ): static {
        $r                = new static();
        $r->success       = true;
        $r->transactionId = $transactionId;
        $r->status        = 'pending';
        $r->amount        = $amount;
        $r->currency      = $currency;
        $r->paymentUrl    = $paymentUrl;
        $r->message       = $message;
        $r->raw           = $raw;
        return $r;
    }

    public static function fail(string $message, array $raw = []): static
    {
        $r          = new static();
        $r->success = false;
        $r->status  = 'failed';
        $r->message = $message;
        $r->raw     = $raw;
        return $r;
    }
}
