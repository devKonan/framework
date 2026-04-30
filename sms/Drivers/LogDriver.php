<?php
namespace Briko\Sms\Drivers;

use Briko\Sms\SmsResult;
use Briko\Foundation\Logger;

/**
 * Driver de développement — affiche le SMS dans les logs au lieu d'envoyer.
 * Activé automatiquement quand SMS_DRIVER=log (défaut en local).
 */
class LogDriver implements SmsDriverInterface
{
    public function send(string $to, string $message, ?string $from = null): SmsResult
    {
        $id = 'log_' . uniqid();

        Logger::channel('sms')->info('SMS simulé [LOG DRIVER]', [
            'to'      => $to,
            'from'    => $from ?? env('SMS_FROM', 'Brikocode'),
            'message' => $message,
            'id'      => $id,
        ]);

        // Aussi en sortie console si lancé depuis CLI
        if (PHP_SAPI === 'cli') {
            echo "\n  📱 SMS [LOG]\n";
            echo "     À      : $to\n";
            echo "     De     : " . ($from ?? env('SMS_FROM', 'Brikocode')) . "\n";
            echo "     Texte  : $message\n\n";
        }

        return new SmsResult(success: true, messageId: $id, info: 'logged');
    }
}
