<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * Capa de acceso a MySQL/MariaDB sobre PDO.
 * Todos los módulos usan consultas preparadas; no hay SQL concatenado.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host    = (string) Env::get('DB_HOST', '127.0.0.1');
        $port    = (string) Env::get('DB_PORT', '3306');
        $name    = (string) Env::get('DB_NAME', 'aluconfig');
        $charset = (string) Env::get('DB_CHARSET', 'utf8mb4');
        $user    = (string) Env::get('DB_USER', 'root');
        $pass    = (string) Env::get('DB_PASS', '');

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $name, $charset);

        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('No se pudo conectar a la base de datos MySQL: ' . $e->getMessage(), 0, $e);
        }

        return self::$pdo;
    }

    /** @param array<string,mixed> $params */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>|null
     */
    public static function fetch(string $sql, array $params = []): ?array
    {
        $stmt = self::run($sql, $params);
        $row  = $stmt->fetch();
        $stmt->closeCursor();
        return $row === false ? null : $row;
    }

    /**
     * @param array<string,mixed> $params
     * @return array<int,array<string,mixed>>
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::run($sql, $params);
        $rows = $stmt->fetchAll();
        $stmt->closeCursor();
        return $rows;
    }

    /** @param array<string,mixed> $params */
    public static function scalar(string $sql, array $params = [], mixed $default = null): mixed
    {
        $stmt  = self::run($sql, $params);
        $value = $stmt->fetchColumn();
        $stmt->closeCursor();
        return $value === false ? $default : $value;
    }

    /** @param array<string,mixed> $data */
    public static function insert(string $table, array $data): int
    {
        $columns      = array_keys($data);
        $placeholders = array_map(static fn ($c) => ':' . $c, $columns);
        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $table,
            implode(', ', array_map(static fn ($c) => '`' . $c . '`', $columns)),
            implode(', ', $placeholders)
        );
        $stmt = self::run($sql, $data);
        $stmt->closeCursor();
        return (int) self::pdo()->lastInsertId();
    }

    /**
     * @param array<string,mixed> $data
     * @param array<string,mixed> $where
     */
    public static function update(string $table, array $data, array $where): int
    {
        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = '`' . $column . '` = :set_' . $column;
        }
        $conds = [];
        foreach (array_keys($where) as $column) {
            $conds[] = '`' . $column . '` = :where_' . $column;
        }
        $params = [];
        foreach ($data as $k => $v) {
            $params['set_' . $k] = $v;
        }
        foreach ($where as $k => $v) {
            $params['where_' . $k] = $v;
        }
        $sql  = sprintf('UPDATE `%s` SET %s WHERE %s', $table, implode(', ', $sets), implode(' AND ', $conds));
        $stmt = self::run($sql, $params);
        $n    = $stmt->rowCount();
        $stmt->closeCursor();
        return $n;
    }

    /** @param array<string,mixed> $where */
    public static function delete(string $table, array $where): int
    {
        $conds  = [];
        $params = [];
        foreach ($where as $column => $value) {
            $conds[]                = '`' . $column . '` = :' . $column;
            $params[$column]        = $value;
        }
        $sql  = sprintf('DELETE FROM `%s` WHERE %s', $table, implode(' AND ', $conds));
        $stmt = self::run($sql, $params);
        $n    = $stmt->rowCount();
        $stmt->closeCursor();
        return $n;
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
