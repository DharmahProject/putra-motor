<?php

declare(strict_types=1);

/**
 * Impor DATA saja (perintah INSERT) dari dump phpMyAdmin ke database tujuan.
 *
 * Struktur tabel dibuat lebih dulu lewat `php artisan migrate --force`.
 * Tabel `migrations` dilewati karena sudah diisi oleh Laravel.
 *
 * Pemakaian (PowerShell), isi env sesuai database tujuan:
 *   $env:DB_HOST="..."; $env:DB_PORT="4000"; $env:DB_DATABASE="putra_motor"
 *   $env:DB_USERNAME="..."; $env:DB_PASSWORD="..."
 *   $env:MYSQL_ATTR_SSL_CA="C:\cacert.pem"
 *   php tools/import_data.php bengkel_pos.sql
 *
 * Tambahkan --dry-run untuk hanya melihat statement yang akan dijalankan.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$arguments = array_slice($argv, 1);
$dryRun = in_array('--dry-run', $arguments, true);
$files = array_values(array_filter($arguments, static fn (string $a): bool => $a !== '--dry-run'));
$file = $files[0] ?? 'bengkel_pos.sql';

if (!is_file($file)) {
    fwrite(STDERR, "File tidak ditemukan: {$file}\n");
    exit(1);
}

/** @return list<string> */
function split_sql(string $sql): array
{
    $statements = [];
    $buffer = '';
    $inString = false;
    $length = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];

        if ($inString) {
            $buffer .= $char;
            if ($char === '\\' && $i + 1 < $length) {
                $buffer .= $sql[++$i];
            } elseif ($char === "'") {
                if ($i + 1 < $length && $sql[$i + 1] === "'") {
                    $buffer .= $sql[++$i];
                } else {
                    $inString = false;
                }
            }
            continue;
        }

        if ($char === '-' && ($sql[$i + 1] ?? '') === '-') {
            $newline = strpos($sql, "\n", $i);
            $i = $newline === false ? $length : $newline;
            continue;
        }

        if ($char === "'") {
            $inString = true;
            $buffer .= $char;
        } elseif ($char === ';') {
            $statement = trim($buffer);
            if ($statement !== '') {
                $statements[] = $statement;
            }
            $buffer = '';
        } else {
            $buffer .= $char;
        }
    }

    $statement = trim($buffer);
    if ($statement !== '') {
        $statements[] = $statement;
    }

    return $statements;
}

$inserts = array_values(array_filter(
    split_sql((string) file_get_contents($file)),
    static function (string $statement): bool {
        if (preg_match('/^INSERT\s+INTO\s+`?([A-Za-z0-9_]+)`?/i', $statement, $match) !== 1) {
            return false;
        }

        return strtolower($match[1]) !== 'migrations';
    }
));

echo count($inserts) . " statement INSERT ditemukan di {$file}\n";
foreach ($inserts as $statement) {
    preg_match('/^INSERT\s+INTO\s+`?([A-Za-z0-9_]+)`?/i', $statement, $match);
    echo ' - ' . ($match[1] ?? '?') . "\n";
}

if ($dryRun) {
    echo "Dry run: tidak ada yang dijalankan.\n";
    exit(0);
}

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_TIMEOUT => 15,
];
$sslCa = (string) (getenv('MYSQL_ATTR_SSL_CA') ?: '');
if ($sslCa !== '') {
    $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
}

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    getenv('DB_HOST') ?: '127.0.0.1',
    getenv('DB_PORT') ?: '3306',
    getenv('DB_DATABASE') ?: ''
);

try {
    $pdo = new PDO($dsn, (string) getenv('DB_USERNAME'), (string) getenv('DB_PASSWORD'), $options);
} catch (PDOException $e) {
    fwrite(STDERR, 'Gagal terhubung: ' . $e->getMessage() . "\n");
    exit(1);
}

$pdo->exec('SET foreign_key_checks = 0');
$pdo->exec("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");

$failed = 0;
foreach ($inserts as $statement) {
    preg_match('/^INSERT\s+INTO\s+`?([A-Za-z0-9_]+)`?/i', $statement, $match);
    $table = $match[1] ?? '?';
    try {
        $rows = $pdo->exec($statement);
        echo "OK    {$table}: {$rows} baris\n";
    } catch (PDOException $e) {
        $failed++;
        echo "GAGAL {$table}: " . $e->getMessage() . "\n";
    }
}

$pdo->exec('SET foreign_key_checks = 1');
echo $failed === 0 ? "Selesai tanpa error.\n" : "Selesai dengan {$failed} error.\n";
exit($failed === 0 ? 0 : 1);
