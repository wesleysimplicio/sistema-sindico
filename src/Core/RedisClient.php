<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class RedisClient
{
    /** @var resource|null */
    private $stream = null;
    private readonly string $host;
    private readonly int $port;
    private readonly int $database;
    private readonly ?string $username;
    private readonly ?string $password;
    private readonly float $timeout;
    private readonly string $transport;

    public function __construct(string $url, float $timeout = 2.0)
    {
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['host'])) {
            throw new RuntimeException('REDIS_URL invalida.');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? 'redis'));
        if (!in_array($scheme, ['redis', 'rediss'], true)) {
            throw new RuntimeException('REDIS_URL deve usar redis:// ou rediss://.');
        }

        $this->transport = $scheme === 'rediss' ? 'tls' : 'tcp';
        $this->host = (string) $parts['host'];
        $this->port = isset($parts['port']) ? (int) $parts['port'] : 6379;
        $path = trim((string) ($parts['path'] ?? '/0'), '/');
        $this->database = $path === '' ? 0 : max(0, (int) $path);
        $this->username = isset($parts['user']) && $parts['user'] !== '' ? (string) $parts['user'] : null;
        $this->password = isset($parts['pass']) && $parts['pass'] !== '' ? (string) $parts['pass'] : null;
        $this->timeout = $timeout;
    }

    public function __destruct()
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }

    public function command(array $args): mixed
    {
        $stream = $this->connect();
        $this->write($stream, $this->encode($args));
        return $this->read($stream);
    }

    /**
     * @return resource
     */
    private function connect()
    {
        if (is_resource($this->stream)) {
            return $this->stream;
        }

        $target = sprintf('%s://%s:%d', $this->transport, $this->host, $this->port);
        $stream = @stream_socket_client($target, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT);
        if (!is_resource($stream)) {
            throw new RuntimeException(sprintf('Falha ao conectar no Redis: %s', $errstr !== '' ? $errstr : (string) $errno));
        }

        stream_set_timeout($stream, (int) max(1, ceil($this->timeout)));

        if ($this->password !== null) {
            $authArgs = ['AUTH'];
            if ($this->username !== null) {
                $authArgs[] = $this->username;
            }
            $authArgs[] = $this->password;
            $this->write($stream, $this->encode($authArgs));
            $this->read($stream);
        }

        if ($this->database > 0) {
            $this->write($stream, $this->encode(['SELECT', (string) $this->database]));
            $this->read($stream);
        }

        $this->stream = $stream;
        return $this->stream;
    }

    /**
     * @param resource $stream
     */
    private function write($stream, string $payload): void
    {
        $written = fwrite($stream, $payload);
        if ($written === false || $written < strlen($payload)) {
            throw new RuntimeException('Falha ao escrever comando no Redis.');
        }
    }

    private function encode(array $args): string
    {
        $payload = '*' . count($args) . "\r\n";
        foreach ($args as $arg) {
            $value = (string) $arg;
            $payload .= '$' . strlen($value) . "\r\n" . $value . "\r\n";
        }
        return $payload;
    }

    /**
     * @param resource $stream
     */
    private function read($stream): mixed
    {
        $prefix = fgetc($stream);
        if ($prefix === false) {
            throw new RuntimeException('Redis encerrou a conexao inesperadamente.');
        }

        return match ($prefix) {
            '+' => $this->readLine($stream),
            '-' => throw new RuntimeException('Redis error: ' . $this->readLine($stream)),
            ':' => (int) $this->readLine($stream),
            '$' => $this->readBulkString($stream),
            '*' => $this->readArray($stream),
            default => throw new RuntimeException('Resposta invalida do Redis.'),
        };
    }

    /**
     * @param resource $stream
     */
    private function readLine($stream): string
    {
        $line = fgets($stream);
        if ($line === false) {
            throw new RuntimeException('Falha ao ler resposta do Redis.');
        }

        return rtrim($line, "\r\n");
    }

    /**
     * @param resource $stream
     */
    private function readBulkString($stream): ?string
    {
        $length = (int) $this->readLine($stream);
        if ($length === -1) {
            return null;
        }

        $remaining = $length + 2;
        $buffer = '';
        while ($remaining > 0) {
            $chunk = fread($stream, $remaining);
            if ($chunk === false || $chunk === '') {
                throw new RuntimeException('Falha ao ler bulk string do Redis.');
            }
            $buffer .= $chunk;
            $remaining -= strlen($chunk);
        }

        return substr($buffer, 0, $length);
    }

    /**
     * @param resource $stream
     * @return array<int,mixed>|null
     */
    private function readArray($stream): ?array
    {
        $length = (int) $this->readLine($stream);
        if ($length === -1) {
            return null;
        }

        $items = [];
        for ($i = 0; $i < $length; $i++) {
            $items[] = $this->read($stream);
        }

        return $items;
    }
}
