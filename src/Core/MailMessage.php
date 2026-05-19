<?php

declare(strict_types=1);

namespace App\Core;

final class MailMessage
{
    public function __construct(
        public readonly string $to,
        public readonly string $subject,
        public readonly string $text,
        public readonly string $from,
        public readonly ?string $fromName = null,
    ) {
    }
}
