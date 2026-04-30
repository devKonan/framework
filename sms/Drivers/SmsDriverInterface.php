<?php
namespace Briko\Sms\Drivers;

use Briko\Sms\SmsResult;

interface SmsDriverInterface
{
    public function send(string $to, string $message, ?string $from = null): SmsResult;
}
