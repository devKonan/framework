<?php
namespace Briko\Sms\Drivers;

use Briko\Sms\SmsResult;
use RuntimeException;

/**
 * Africa's Talking — provider SMS le plus utilisé en Afrique subsaharienne.
 * Sandbox gratuit sur africastalking.com
 *
 * .env requis : AT_USERNAME, AT_API_KEY
 * Optionnel  : AT_SANDBOX=true (utilise le endpoint sandbox)
 */
class AfricasTalkingDriver extends AbstractDriver
{
    private string $username;
    private string $apiKey;
    private string $endpoint;

    public function __construct()
    {
        $this->username = env('AT_USERNAME', 'sandbox');
        $this->apiKey   = env('AT_API_KEY', '');
        $sandbox        = env('AT_SANDBOX', 'true') === 'true';

        $this->endpoint = $sandbox
            ? 'https://api.sandbox.africastalking.com/version1/messaging'
            : 'https://api.africastalking.com/version1/messaging';
    }

    public function send(string $to, string $message, ?string $from = null): SmsResult
    {
        if (!$this->apiKey) {
            throw new RuntimeException('Africa\'s Talking : AT_API_KEY manquant dans .env');
        }

        $fields = [
            'username' => $this->username,
            'to'       => $to,
            'message'  => $message,
        ];

        if ($from) $fields['from'] = $from;

        $res = $this->post($this->endpoint, $fields, [
            'apiKey: ' . $this->apiKey,
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
        ]);

        $body = json_decode($res['body'], true);

        $recipients = $body['SMSMessageData']['Recipients'] ?? [];
        $first      = $recipients[0] ?? [];
        $status     = $first['status']    ?? '';
        $msgId      = $first['messageId'] ?? '';
        $ok         = $res['code'] === 201 && in_array($status, ['Success', 'sent']);

        return new SmsResult(
            success:   $ok,
            messageId: $msgId,
            info:      $status ?: ($body['SMSMessageData']['Message'] ?? ''),
            raw:       $body ?? []
        );
    }
}
