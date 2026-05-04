<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use InvalidArgumentException;
use PDO;

abstract class BaseRepository
{
    protected PDO $pdo;
    protected string $table = '';

    private static function assertColumnName(string $col): void
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $col)) {
            throw new InvalidArgumentException('Invalid column name: ' . $col);
        }
    }

    public function __construct()
    {
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $this->pdo = Database::connection($config['db']);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function all(array $where = [], string $orderBy = 'id DESC', int $limit = 200): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        if (!empty($where)) {
            $clauses = [];
            foreach ($where as $col => $val) {
                self::assertColumnName((string) $col);
                $clauses[] = "$col = :$col";
                $params[$col] = $val;
            }
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }
        $sql .= ' ORDER BY ' . self::sanitizeOrderBy($orderBy) . ' LIMIT ' . (int) $limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private static function sanitizeOrderBy(string $orderBy): string
    {
        $parts = preg_split('/\s+/', trim($orderBy)) ?: [];
        if (count($parts) === 0 || count($parts) > 2) {
            throw new InvalidArgumentException('Invalid ORDER BY: ' . $orderBy);
        }
        self::assertColumnName($parts[0]);
        $dir = strtoupper($parts[1] ?? 'ASC');
        if ($dir !== 'ASC' && $dir !== 'DESC') {
            throw new InvalidArgumentException('Invalid ORDER BY direction: ' . $dir);
        }
        return $parts[0] . ' ' . $dir;
    }

    public function create(array $data): int
    {
        $cols = array_keys($data);
        foreach ($cols as $col) {
            self::assertColumnName((string) $col);
        }
        $placeholders = array_map(fn($c) => ":$c", $cols);
        $sql = "INSERT INTO {$this->table} (" . implode(',', $cols) . ') VALUES (' . implode(',', $placeholders) . ')';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        if (empty($data)) {
            return false;
        }
        $sets = [];
        foreach (array_keys($data) as $col) {
            self::assertColumnName((string) $col);
            $sets[] = "$col = :$col";
        }
        $sql = "UPDATE {$this->table} SET " . implode(',', $sets) . ' WHERE id = :id';
        $data['id'] = $id;
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function count(array $where = []): int
    {
        $sql = "SELECT COUNT(*) AS c FROM {$this->table}";
        $params = [];
        if (!empty($where)) {
            $clauses = [];
            foreach ($where as $col => $val) {
                self::assertColumnName((string) $col);
                $clauses[] = "$col = :$col";
                $params[$col] = $val;
            }
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) ($stmt->fetch()['c'] ?? 0);
    }
}
