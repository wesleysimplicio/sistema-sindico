<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Mailer
{
    public function __construct(
        private readonly MailTransport $transport,
        private readonly string $from,
        private readonly ?string $fromName = null,
    ) {
    }

    public static function fromEnvironment(): self
    {
        $driver = strtolower((string) env('MAIL_DRIVER', 'log'));
        $from = (string) env('MAIL_FROM', 'no-reply@sindico.local');
        $fromName = (string) env('MAIL_FROM_NAME', 'Sistema Sindico');

        $transport = match ($driver) {
            'resend' => new ResendMailTransport(
                apiKey: (string) self::requiredEnv('MAIL_API_KEY'),
                baseUrl: (string) env('MAIL_API_BASE_URL', 'https://api.resend.com'),
                timeoutSeconds: (int) env('MAIL_TIMEOUT_SECONDS', 10),
            ),
            'log' => new LogMailTransport(
                dirname(__DIR__, 2) . '/storage/logs/mailer.log',
                self::isLocalEnv(),
            ),
            default => throw new RuntimeException(sprintf('MAIL_DRIVER nao suportado: %s', $driver)),
        };

        return new self($transport, $from, $fromName);
    }

    public function sendPasswordResetCode(string $to, string $recipientName, string $code): MailReceipt
    {
        $subject = 'Codigo de recuperacao de senha';
        $body = implode(PHP_EOL . PHP_EOL, [
            'Ola ' . $recipientName . ',',
            'Seu codigo de recuperacao e: ' . $code,
            'Ele expira em 15 minutos. Se voce nao solicitou esta acao, ignore esta mensagem.',
        ]);

        return $this->transport->send(
            new MailMessage(
                to: $to,
                subject: $subject,
                text: $body,
                from: $this->from,
                fromName: $this->fromName,
            )
        );
    }

    private static function requiredEnv(string $key): string
    {
        $value = (string) env($key, '');
        if ($value === '') {
            throw new RuntimeException(sprintf('%s obrigatorio para o driver de email configurado.', $key));
        }
        return $value;
    }

    private static function isLocalEnv(): bool
    {
        return strtolower((string) env('APP_ENV', 'local')) === 'local';
    }
}
