<?php
namespace Briko\Sms\Drivers;

use Briko\Sms\SmsResult;
use RuntimeException;

/**
 * Twilio — provider international, fiable en Côte d'Ivoire et dans toute l'Afrique.
 *
 * .env requis : TWILIO_SID, TWILIO_TOKEN, TWILIO_FROM
 */
class TwilioDriver extends AbstractDriver
{
    private string $sid;
    private string $token;
    private string $from;

    public function __construct()
    {
        $this->sid   = env('TWILIO_SID', '');
        $this->token = env('TWILIO_TOKEN', '');
        $this->from  = env('TWILIO_FROM', '');
    }

    public function send(string $to, string $message, ?string $from = null): SmsResult
    {
        foreach (['TWILIO_SID', 'TWILIO_TOKEN', 'TWILIO_FROM'] as $key) {
            if (!env($key)) throw new RuntimeException("Twilio : $key manquant dans .env");
        }

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->sid}/Messages.json";

        $res = $this->post($url, [
            'To'   => $to,
            'From' => $from ?? $this->from,
            'Body' => $message,
        ], [], "{$this->sid}:{$this->token}");

        $body = json_decode($res['body'], true);
        $ok   = $res['code'] === 201 && ($body['status'] ?? '') !== 'failed';

        return new SmsResult(
            success:   $ok,
            messageId: $body['sid'] ?? '',
            info:      $body['status'] ?? ($body['message'] ?? ''),
            raw:       $body ?? []
        );
    }
}
