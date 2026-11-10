<?php

declare(strict_types=1);

final class DatabaseSessionHandler implements SessionHandlerInterface
{
    private bool $schemaReady = false;

    public function __construct(
        private readonly PDO $database,
        private readonly int $lifetime = 7200
    ) {
    }

    public function open(string $path, string $name): bool
    {
        $this->ensureSchema();

        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $this->ensureSchema();
        $statement = $this->database->prepare(
            'SELECT payload FROM pos_sessions WHERE id = ? AND last_activity >= ? LIMIT 1'
        );
        $statement->execute([$id, time() - $this->lifetime]);
        $payload = $statement->fetchColumn();

        return is_string($payload) ? $payload : '';
    }

    public function write(string $id, string $data): bool
    {
        $this->ensureSchema();
        $statement = $this->database->prepare(
            'INSERT INTO pos_sessions (id, payload, last_activity) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE payload = VALUES(payload), last_activity = VALUES(last_activity)'
        );

        return $statement->execute([$id, $data, time()]);
    }

    public function destroy(string $id): bool
    {
        $this->ensureSchema();
        $statement = $this->database->prepare('DELETE FROM pos_sessions WHERE id = ?');

        return $statement->execute([$id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $this->ensureSchema();
        $statement = $this->database->prepare('DELETE FROM pos_sessions WHERE last_activity < ?');
        $statement->execute([time() - max($max_lifetime, $this->lifetime)]);

        return $statement->rowCount();
    }

    private function ensureSchema(): void
    {
        if ($this->schemaReady) {
            return;
        }

        $this->database->exec(
            'CREATE TABLE IF NOT EXISTS pos_sessions (
                id VARCHAR(128) NOT NULL,
                payload LONGBLOB NOT NULL,
                last_activity INT UNSIGNED NOT NULL,
                PRIMARY KEY (id),
                KEY pos_sessions_last_activity_index (last_activity)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $this->schemaReady = true;
    }
}

function configure_database_session(PDO $database, int $lifetime = 7200): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_save_handler(new DatabaseSessionHandler($database, $lifetime), true);
}
