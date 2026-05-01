<?php
namespace Briko\Payment;

use Briko\Foundation\Logger;

class PaymentMessage
{
    public int     $amount      = 0;
    public string  $currency    = 'XOF';
    public string  $phone       = '';
    public string  $email       = '';
    public string  $name        = '';
    public string  $description = '';
    public string  $reference   = '';
    public ?string $callbackUrl = null;
    public ?string $returnUrl   = null;
    public ?string $cancelUrl   = null;
    public array   $metadata    = [];

    public function __construct(int $amount)
    {
        $this->amount   = $amount;
        $this->currency = env('PAYMENT_CURRENCY', 'XOF');
    }

    public function to(string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function email(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function description(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function reference(string $reference): static
    {
        $this->reference = $reference;
        return $this;
    }

    public function currency(string $currency): static
    {
        $this->currency = strtoupper($currency);
        return $this;
    }

    public function callbackUrl(string $url): static
    {
        $this->callbackUrl = $url;
        return $this;
    }

    public function returnUrl(string $url): static
    {
        $this->returnUrl = $url;
        return $this;
    }

    public function cancelUrl(string $url): static
    {
        $this->cancelUrl = $url;
        return $this;
    }

    public function meta(array $data): static
    {
        $this->metadata = array_merge($this->metadata, $data);
        return $this;
    }

    public function send(): PaymentResult
    {
        if ($this->reference === '') {
            $this->reference = uniqid('TXN-', true);
        }

        $result = $this->makeDriver()->initiate($this);

        Logger::channel('payment')->info('Paiement initié', [
            'driver'    => env('PAYMENT_DRIVER', 'log'),
            'amount'    => $this->amount,
            'currency'  => $this->currency,
            'phone'     => $this->phone,
            'ref'       => $this->reference,
            'status'    => $result->status,
            'txId'      => $result->transactionId,
        ]);

        return $result;
    }

    public function verify(string $transactionId): PaymentResult
    {
        return $this->makeDriver()->verify($transactionId);
    }

    private function makeDriver(): PaymentDriverInterface
    {
        return match (env('PAYMENT_DRIVER', 'log')) {
            'orangemoney' => new Drivers\OrangeMoneyDriver(),
            'mtnmomo'     => new Drivers\MtnMomoDriver(),
            'wave'        => new Drivers\WaveDriver(),
            'cinetpay'    => new Drivers\CinetPayDriver(),
            'paydunya'    => new Drivers\PayDunyaDriver(),
            'stripe'      => new Drivers\StripeDriver(),
            default       => new Drivers\LogDriver(),
        };
    }
}
