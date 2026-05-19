<?php

declare(strict_types=1);

namespace App\Core;

interface MailTransport
{
    public function send(MailMessage $message): MailReceipt;
}
