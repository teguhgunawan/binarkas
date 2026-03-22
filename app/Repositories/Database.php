<?php

class Database
{
    private ?PDO $connection = null;
    private ?string $lastError = null;

    public function connection(): ?PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        $config = config('database');
        if (!is_array($config)) {
            $this->lastError = 'Database configuration is missing.';
            return null;
        }

        try {
            $driver = strtolower((string) ($config['driver'] ?? 'pgsql'));
            $dsn = $this->buildDsn($driver, $config);

            $this->connection = new PDO($dsn, (string) $config['username'], (string) $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            if ($driver === 'pgsql' && !empty($config['schema'])) {
                $schema = preg_replace('/[^a-zA-Z0-9_,]/', '', (string) $config['schema']);
                if ($schema !== '') {
                    $this->connection->exec("SET search_path TO {$schema}");
                }
            }

            $this->lastError = null;
            return $this->connection;
        } catch (Throwable $exception) {
            $this->lastError = $exception->getMessage();
            return null;
        }
    }

    public function isConnected(): bool
    {
        return $this->connection() instanceof PDO;
    }

    public function lastError(): ?string
    {
        $this->connection();
        return $this->lastError;
    }

    private function buildDsn(string $driver, array $config): string
    {
        return match ($driver) {
            'mysql' => sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset'] ?? 'utf8mb4'
            ),
            'pgsql', 'postgres', 'postgresql' => sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $config['host'],
                $config['port'],
                $config['database']
            ),
            default => throw new RuntimeException('Unsupported database driver: ' . $driver),
        };
    }
}
