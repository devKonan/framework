<?php
namespace Briko\Mail;

/**
 * Facade Mail de Brikocode.
 *
 * Envoi fluide :
 *   Mail::to('aya@abidjan.ci')
 *       ->subject('Confirmation commande')
 *       ->view('commande', ['id' => 1042])
 *       ->send();
 *
 * Avec Mailable :
 *   Mail::send(new WelcomeMail($user));
 *
 * Multi-destinataires :
 *   Mail::to(['a@ci', 'b@ci'])->subject('...')->html('<p>...</p>')->send();
 */
class Mail
{
    public static function to(string|array $address): MailMessage
    {
        return (new MailMessage())->to($address);
    }

    public static function send(Mailable $mailable): MailResult
    {
        return $mailable->send();
    }
}
