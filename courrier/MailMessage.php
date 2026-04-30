<?php
namespace Briko\courrier;

use Briko\core\Logger;
use Briko\courrier\Drivers\SmtpDriver;
use Briko\courrier\Drivers\SendgridDriver;
use Briko\courrier\Drivers\MailgunDriver;
use Briko\courrier\Drivers\LogDriver;
use Briko\courrier\Drivers\MailDriverInterface;

class MailMessage
{
    public array   $to          = [];
    public array   $cc          = [];
    public array   $bcc         = [];
    public ?string $from        = null;
    public ?string $fromName    = null;
    public ?string $replyTo     = null;
    public ?string $subject     = null;
    public ?string $html        = null;
    public ?string $text        = null;
    public array   $attachments = [];

    public function to(string|array $address): static
    {
        $this->to = array_merge($this->to, (array) $address);
        return $this;
    }

    public function cc(string|array $address): static
    {
        $this->cc = array_merge($this->cc, (array) $address);
        return $this;
    }

    public function bcc(string|array $address): static
    {
        $this->bcc = array_merge($this->bcc, (array) $address);
        return $this;
    }

    public function from(string $address, ?string $name = null): static
    {
        $this->from     = $address;
        $this->fromName = $name;
        return $this;
    }

    public function replyTo(string $address): static
    {
        $this->replyTo = $address;
        return $this;
    }

    public function subject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function html(string $html): static
    {
        $this->html = $html;
        return $this;
    }

    public function text(string $text): static
    {
        $this->text = $text;
        return $this;
    }

    // Charge un template PHP depuis village/mails/
    public function view(string $template, array $data = []): static
    {
        $file = base_path('village/mails/' . str_replace('.', '/', $template) . '.php');

        if (!file_exists($file)) {
            throw new \RuntimeException("Template mail introuvable : $file");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        $this->html = ob_get_clean();

        // Extrait le texte brut depuis le HTML
        if (!$this->text) {
            $this->text = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $this->html));
        }

        return $this;
    }

    // Pièce jointe depuis un chemin de fichier
    public function attach(string $path, string $name = '', string $mime = ''): static
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Fichier introuvable pour la pièce jointe : $path");
        }

        $this->attachments[] = [
            'path' => $path,
            'name' => $name ?: basename($path),
            'mime' => $mime,
        ];

        return $this;
    }

    public function send(): MailResult
    {
        if (empty($this->to)) {
            throw new \RuntimeException('Aucun destinataire défini. Utilise ->to(...)');
        }

        $result = $this->makeDriver()->send($this);

        Logger::channel('mail')->info('Envoi email', [
            'driver'  => env('MAIL_DRIVER', 'log'),
            'to'      => $this->to,
            'subject' => $this->subject,
            'ok'      => $result->success,
            'id'      => $result->messageId,
        ]);

        return $result;
    }

    private function makeDriver(): MailDriverInterface
    {
        return match (env('MAIL_DRIVER', 'log')) {
            'smtp'      => new SmtpDriver(),
            'sendgrid'  => new SendgridDriver(),
            'mailgun'   => new MailgunDriver(),
            default     => new LogDriver(),
        };
    }
}
