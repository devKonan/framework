<?php
namespace Briko\courrier\Drivers;

use Briko\courrier\MailMessage;
use Briko\courrier\MailResult;
use RuntimeException;

/**
 * Mailgun — API HTTP.
 * .env requis : MAILGUN_API_KEY, MAILGUN_DOMAIN
 * Optionnel   : MAILGUN_REGION = us | eu  (défaut: us)
 */
class MailgunDriver implements MailDriverInterface
{
    public function send(MailMessage $message): MailResult
    {
        $key    = env('MAILGUN_API_KEY', '');
        $domain = env('MAILGUN_DOMAIN', '');
        if (!$key || !$domain) throw new RuntimeException('MAILGUN_API_KEY et MAILGUN_DOMAIN requis dans .env');

        $region   = env('MAILGUN_REGION', 'us') === 'eu' ? 'api.eu.mailgun.net' : 'api.mailgun.net';
        $url      = "https://$region/v3/$domain/messages";
        $from     = $message->from     ?? env('MAIL_FROM_ADDRESS', 'noreply@brikocode.ci');
        $fromName = $message->fromName ?? env('MAIL_FROM_NAME', env('APP_NAME', 'Brikocode'));

        $fields = [
            'from'    => "$fromName <$from>",
            'to'      => implode(',', $message->to),
            'subject' => $message->subject ?? '(sans objet)',
        ];

        if (!empty($message->cc))  $fields['cc']  = implode(',', $message->cc);
        if (!empty($message->bcc)) $fields['bcc'] = implode(',', $message->bcc);
        if ($message->text) $fields['text'] = $message->text;

        $fields['html'] = $message->html
            ?? ($message->text ? nl2br(htmlspecialchars($message->text)) : '<p></p>');

        $res  = $this->post($url, $fields, $key);
        $body = json_decode($res['body'], true) ?? [];
        $ok   = $res['code'] === 200;

        return new MailResult(
            success:   $ok,
            messageId: $body['id'] ?? '',
            error:     $ok ? '' : ($body['message'] ?? "HTTP {$res['code']}"),
            raw:       $body
        );
    }

    private function post(string $url, array $fields, string $apiKey): array
    {
        if (extension_loaded('curl')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $fields,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_USERPWD        => "api:$apiKey",
            ]);
            $body = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return ['code' => $code, 'body' => $body ?: ''];
        }

        $ctx  = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Authorization: Basic " . base64_encode("api:$apiKey") . "\r\nContent-Type: application/x-www-form-urlencoded",
            'content' => http_build_query($fields),
            'timeout' => 20,
        ]]);
        $body = @file_get_contents($url, false, $ctx);
        $code = isset($http_response_header) ? (int) explode(' ', $http_response_header[0])[1] : 0;
        return ['code' => $code, 'body' => $body ?: ''];
    }
}
