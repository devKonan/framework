<?php
namespace Briko\courrier\Drivers;

use Briko\courrier\MailMessage;
use Briko\courrier\MailResult;
use RuntimeException;

/**
 * Driver SMTP natif — zéro dépendance, PHP sockets purs.
 * Compatible Gmail, Outlook, Mailgun SMTP, OVH, Amazon SES SMTP…
 *
 * .env requis :
 *   MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD
 *   MAIL_ENCRYPTION = tls | ssl | none
 *   MAIL_FROM_ADDRESS, MAIL_FROM_NAME
 */
class SmtpDriver implements MailDriverInterface
{
    /** @var resource|false */
    private mixed $socket = false;

    public function send(MailMessage $message): MailResult
    {
        $host       = env('MAIL_HOST', 'smtp.gmail.com');
        $port       = (int) env('MAIL_PORT', 587);
        $username   = env('MAIL_USERNAME', '');
        $password   = env('MAIL_PASSWORD', '');
        $encryption = strtolower(env('MAIL_ENCRYPTION', 'tls'));

        try {
            $this->connect($host, $port, $encryption);
            if ($username) $this->authenticate($username, $password);
            $id = $this->deliver($message);
            $this->quit();
            return new MailResult(success: true, messageId: $id);
        } catch (\Throwable $e) {
            $this->tryClose();
            return new MailResult(success: false, error: $e->getMessage());
        }
    }

    // ─── Connexion ────────────────────────────────────────────────────────────

    private function connect(string $host, int $port, string $encryption): void
    {
        $remote = ($encryption === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
        $ctx    = stream_context_create(['ssl' => [
            'verify_peer'       => true,
            'verify_peer_name'  => true,
            'allow_self_signed' => false,
        ]]);

        $this->socket = stream_socket_client($remote, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $ctx);

        if (!$this->socket) {
            throw new RuntimeException("SMTP connexion impossible : $errstr ($errno)");
        }

        stream_set_timeout($this->socket, 30);
        $this->expect(220);

        $this->cmd('EHLO ' . (gethostname() ?: 'localhost'));

        if ($encryption === 'tls') {
            $this->cmd('STARTTLS');
            stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->cmd('EHLO ' . (gethostname() ?: 'localhost'));
        }
    }

    private function authenticate(string $username, string $password): void
    {
        $this->cmd('AUTH LOGIN');
        $this->cmd(base64_encode($username));
        $this->cmd(base64_encode($password), 235);
    }

    // ─── Envoi ────────────────────────────────────────────────────────────────

    private function deliver(MailMessage $message): string
    {
        $from = $message->from ?? env('MAIL_FROM_ADDRESS', 'noreply@brikocode.ci');
        $id   = '<' . md5(uniqid()) . '@brikocode>';

        $this->cmd("MAIL FROM:<$from>");

        $recipients = array_merge(
            $message->to,
            $message->cc,
            $message->bcc
        );
        foreach ($recipients as $r) {
            $this->cmd("RCPT TO:<$r>");
        }

        $this->cmd('DATA', 354);
        $this->write($this->buildMime($message, $id) . "\r\n.");
        $this->expect(250);

        return $id;
    }

    private function quit(): void
    {
        $this->write("QUIT\r\n");
        fclose($this->socket);
        $this->socket = false;
    }

    private function tryClose(): void
    {
        if ($this->socket) {
            @fclose($this->socket);
            $this->socket = false;
        }
    }

    // ─── MIME builder ─────────────────────────────────────────────────────────

    private function buildMime(MailMessage $message, string $msgId): string
    {
        $from     = $message->from     ?? env('MAIL_FROM_ADDRESS', 'noreply@brikocode.ci');
        $fromName = $message->fromName ?? env('MAIL_FROM_NAME', env('APP_NAME', 'Brikocode'));
        $hasFiles = !empty($message->attachments);
        $mixedB   = 'mix_' . md5(uniqid());
        $altB     = 'alt_' . md5(uniqid());

        $toLine  = implode(', ', $message->to);
        $subject = '=?UTF-8?B?' . base64_encode($message->subject ?? '(sans objet)') . '?=';

        $h  = "Message-ID: $msgId\r\n";
        $h .= "Date: " . date('r') . "\r\n";
        $h .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$from>\r\n";
        $h .= "To: $toLine\r\n";
        if ($message->replyTo) $h .= "Reply-To: {$message->replyTo}\r\n";
        if (!empty($message->cc))  $h .= "Cc: "  . implode(', ', $message->cc)  . "\r\n";
        $h .= "Subject: $subject\r\n";
        $h .= "MIME-Version: 1.0\r\n";
        $h .= "X-Mailer: Brikocode Framework\r\n";

        if ($hasFiles) {
            $h .= "Content-Type: multipart/mixed; boundary=\"$mixedB\"\r\n\r\n";
            $h .= "--$mixedB\r\n";
        }

        $h .= "Content-Type: multipart/alternative; boundary=\"$altB\"\r\n\r\n";

        if ($message->text) {
            $h .= "--$altB\r\n";
            $h .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $h .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $h .= chunk_split(base64_encode($message->text)) . "\r\n";
        }

        $html = $message->html ?? ($message->text ? nl2br(htmlspecialchars($message->text)) : '<p>(vide)</p>');
        $h .= "--$altB\r\n";
        $h .= "Content-Type: text/html; charset=UTF-8\r\n";
        $h .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $h .= chunk_split(base64_encode($html)) . "\r\n";
        $h .= "--$altB--\r\n";

        foreach ($message->attachments as $att) {
            $name    = $att['name'] ?: basename($att['path']);
            $content = base64_encode(file_get_contents($att['path']));
            $mime    = $att['mime'] ?? mime_content_type($att['path']) ?: 'application/octet-stream';

            $h .= "\r\n--$mixedB\r\n";
            $h .= "Content-Type: $mime; name=\"$name\"\r\n";
            $h .= "Content-Transfer-Encoding: base64\r\n";
            $h .= "Content-Disposition: attachment; filename=\"$name\"\r\n\r\n";
            $h .= chunk_split($content) . "\r\n";
        }

        if ($hasFiles) $h .= "--$mixedB--\r\n";

        return $h;
    }

    // ─── Helpers socket ───────────────────────────────────────────────────────

    private function cmd(string $command, int $expect = 250): string
    {
        $this->write($command . "\r\n");
        return $this->expect($expect);
    }

    private function write(string $data): void
    {
        fwrite($this->socket, $data);
    }

    private function expect(int $code): string
    {
        $response = '';
        while ($line = fgets($this->socket, 512)) {
            $response .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') break;
        }

        $actual = (int) substr($response, 0, 3);
        if ($actual !== $code) {
            throw new RuntimeException("SMTP attendait $code, reçu $actual : " . trim($response));
        }

        return $response;
    }
}
