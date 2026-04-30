<?php
namespace Briko\courrier\Drivers;

use Briko\courrier\MailMessage;
use Briko\courrier\MailResult;

interface MailDriverInterface
{
    public function send(MailMessage $message): MailResult;
}
