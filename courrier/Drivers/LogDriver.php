<?php
namespace Briko\courrier\Drivers;

use Briko\courrier\MailMessage;
use Briko\courrier\MailResult;
use Briko\core\Logger;

/**
 * Driver de développement — affiche l'email dans les logs, rien n'est envoyé.
 * Activé automatiquement quand MAIL_DRIVER=log (défaut en local).
 */
class LogDriver implements MailDriverInterface
{
    public function send(MailMessage $message): MailResult
    {
        $id   = 'mail_' . uniqid();
        $from = $message->from ?? env('MAIL_FROM_ADDRESS', 'noreply@brikocode.ci');

        Logger::channel('mail')->info('Email simulé [LOG DRIVER]', [
            'to'      => $message->to,
            'cc'      => $message->cc  ?: null,
            'bcc'     => $message->bcc ?: null,
            'from'    => $from,
            'subject' => $message->subject,
            'id'      => $id,
        ]);

        if (PHP_SAPI === 'cli') {
            echo "\n  ✉️  Email [LOG]\n";
            echo "     À       : " . implode(', ', $message->to) . "\n";
            echo "     De      : $from\n";
            echo "     Objet   : {$message->subject}\n";
            if ($message->html) {
                $preview = strip_tags($message->html);
                echo "     Aperçu  : " . mb_substr(trim($preview), 0, 80) . "\n";
            }
            if (!empty($message->attachments)) {
                echo "     Fichiers: " . count($message->attachments) . " pièce(s) jointe(s)\n";
            }
            echo "\n";
        }

        return new MailResult(success: true, messageId: $id);
    }
}
