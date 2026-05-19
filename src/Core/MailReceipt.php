<?php

declare(strict_types=1);

namespace App\Core;

final class MailReceipt
{
    public function __construct(
        public readonly string $driver,
        public readonly string $channel = 'email',
        public readonly bool $delivered = true,
        public readonly ?string $messageId = null,
        public readonly ?string $debugCode = null,
    ) {
    }
}
