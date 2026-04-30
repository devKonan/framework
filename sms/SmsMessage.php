<?php
namespace Briko\Sms;

use Briko\Foundation\Logger;
use Briko\Sms\Drivers\AfricasTalkingDriver;
use Briko\Sms\Drivers\TwilioDriver;
use Briko\Sms\Drivers\HttpDriver;
use Briko\Sms\Drivers\LogDriver;
use Briko\Sms\Drivers\SmsDriverInterface;

class SmsMessage
{
    private array   $to   = [];
    private ?string $from = null;

    public function __construct(string|array $to)
    {
        $this->to = is_array($to) ? array_values($to) : [$to];
    }

    public function from(string $sender): static
    {
        $this->from = $sender;
        return $this;
    }

    public function send(string $message): SmsResult
    {
        $driver  = $this->makeDriver();
        $sender  = $this->from ?? env('SMS_FROM', 'Brikocode');
        $results = [];

        foreach ($this->to as $number) {
            $results[] = $driver->send($number, $message, $sender);
        }

        Logger::channel('sms')->info('Envoi SMS', [
            'driver'  => env('SMS_DRIVER', 'log'),
            'to'      => $this->to,
            'from'    => $sender,
            'preview' => mb_substr($message, 0, 60) . (mb_strlen($message) > 60 ? '…' : ''),
            'count'   => count($results),
            'ok'      => count(array_filter($results, fn ($r) => $r->success)),
        ]);

        if (count($results) === 1) return $results[0];

        $allOk = !in_array(false, array_map(fn ($r) => $r->success, $results));
        return new SmsResult(
            success:   $allOk,
            messageId: implode(',', array_filter(array_map(fn ($r) => $r->messageId, $results))),
            info:      $allOk ? 'Tous envoyés' : 'Certains ont échoué',
        );
    }

    private function makeDriver(): SmsDriverInterface
    {
        return match (env('SMS_DRIVER', 'log')) {
            'africastalking' => new AfricasTalkingDriver(),
            'twilio'         => new TwilioDriver(),
            'http'           => new HttpDriver(),
            default          => new LogDriver(),
        };
    }
}
