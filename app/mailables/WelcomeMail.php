<?php
namespace App\Mailables;

use Briko\Mail\Mail;
use Briko\Mail\Mailable;
use Briko\Mail\MailMessage;

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
