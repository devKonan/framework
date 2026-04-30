<?php
namespace Briko\Mail\Drivers;

use Briko\Mail\MailMessage;
use Briko\Mail\MailResult;
use RuntimeException;

/**
 * Sendgrid — API HTTP v3.
 * .env requis : SENDGRID_API_KEY
 */
class SendgridDriver implements MailDriverInterface
{
    public function send(MailMessage $message): MailResult
    {
        $key = env('SENDGRID_API_KEY', '');
        if (!$key) throw new RuntimeException('SENDGRID_API_KEY manquant dans .env');

        $from     = $message->from     ?? env('MAIL_FROM_ADDRESS', 'noreply@brikocode.ci');
        $fromName = $message->fromName ?? env('MAIL_FROM_NAME', env('APP_NAME', 'Brikocode'));

        $payload = [
            'personalizations' => [[
                'to'  => array_map(fn ($e) => ['email' => $e], $message->to),
                'subject' => $message->subject ?? '(sans objet)',
            ]],
            'from'    => ['email' => $from, 'name' => $fromName],
            'content' => [],
        ];

        if (!empty($message->cc)) {
            $payload['personalizations'][0]['cc'] = array_map(fn ($e) => ['email' => $e], $message->cc);
        }
        if (!empty($message->bcc)) {
            $payload['personalizations'][0]['bcc'] = array_map(fn ($e) => ['email' => $e], $message->bcc);
        }
        if ($message->replyTo) {
            $payload['reply_to'] = ['email' => $message->replyTo];
        }
        if ($message->text) {
            $payload['content'][] = ['type' => 'text/plain', 'value' => $message->text];
        }
        $html = $message->html ?? ($message->text ? nl2br(htmlspecialchars($message->text)) : '<p></p>');
        $payload['content'][] = ['type' => 'text/html', 'value' => $html];

        foreach ($message->attachments as $att) {
            $payload['attachments'][] = [
                'content'     => base64_encode(file_get_contents($att['path'])),
                'filename'    => $att['name'] ?: basename($att['path']),
                'type'        => $att['mime'] ?? 'application/octet-stream',
                'disposition' => 'attachment',
            ];
        }

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $res  = $this->post('https://api.sendgrid.com/v3/mail/send', $body, [
            'Authorization: Bearer ' . $key,
            'Content-Type: application/json',
        ]);

        $ok = $res['code'] === 202;
        return new MailResult(
            success:   $ok,
            messageId: $res['headers']['x-message-id'] ?? '',
            error:     $ok ? '' : ($res['body'] ?: "HTTP {$res['code']}"),
            raw:       json_decode($res['body'], true) ?? []
        );
    }

    private function post(string $url, string $body, array $headers): array
    {
        if (extension_loaded('curl')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_HEADER         => true,
            ]);
            $raw  = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $hlen = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            curl_close($ch);
            return ['code' => $code, 'body' => substr($raw, $hlen), 'headers' => []];
        }

        $ctx  = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => implode("\r\n", $headers),
            'content' => $body,
            'timeout' => 20,
        ]]);
        $resp = @file_get_contents($url, false, $ctx);
        $code = isset($http_response_header) ? (int) explode(' ', $http_response_header[0])[1] : 0;
        return ['code' => $code, 'body' => $resp ?: '', 'headers' => []];
    }
}
