<?php
namespace Briko\village\mailables;

use Briko\courrier\Mail;
use Briko\courrier\Mailable;
use Briko\courrier\MailMessage;

class WelcomeMail extends Mailable
{
    public function __construct(
        private array $data = []
    ) {}

    public function build(): MailMessage
    {
        return Mail::to($this->data['email'])
            ->subject('Welcome — ' . env('APP_NAME', 'Brikocode'))
            ->view('welcome', ['data' => $this->data]);
            // ou ->html('<h1>Contenu HTML</h1>')
    }
}
