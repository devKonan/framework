<?php
namespace Briko\Mail\Drivers;

use Briko\Mail\MailMessage;
use Briko\Mail\MailResult;

interface MailDriverInterface
{
    public function send(MailMessage $message): MailResult;
}
