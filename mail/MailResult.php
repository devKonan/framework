<?php
namespace Briko\Mail;

class MailResult
{
    public function __construct(
        public readonly bool   $success,
        public readonly string $messageId = '',
        public readonly string $error     = '',
        public readonly array  $raw       = []
    ) {}

    public function isOk(): bool
    {
        return $this->success;
    }

    public function toArray(): array
    {
        return [
            'success'    => $this->success,
            'message_id' => $this->messageId,
            'error'      => $this->error,
        ];
    }
}
