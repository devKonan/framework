<?php
namespace Briko\courrier;

/**
 * Classe de base pour les emails structurés.
 *
 * Usage :
 *   class WelcomeMail extends Mailable {
 *       public function __construct(private array $user) {}
 *       public function build(): MailMessage {
 *           return Mail::to($this->user['email'])
 *               ->subject('Bienvenue !')
 *               ->view('welcome', ['user' => $this->user]);
 *       }
 *   }
 *
 *   Mail::send(new WelcomeMail($user));
 */
abstract class Mailable
{
    abstract public function build(): MailMessage;

    public function send(): MailResult
    {
        return $this->build()->send();
    }
}
