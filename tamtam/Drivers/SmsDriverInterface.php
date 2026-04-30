<?php
namespace Briko\tamtam\Drivers;

use Briko\tamtam\SmsResult;

interface SmsDriverInterface
{
    public function send(string $to, string $message, ?string $from = null): SmsResult;
}
