<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    $sessionDirectory = dirname(__DIR__) . '/API/storage/framework/auth_sessions';
    if (!is_dir($sessionDirectory)) {
        mkdir($sessionDirectory, 0775, true);
    }
    session_save_path($sessionDirectory);
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

function auth_database(): PDO
{
    static $connection;
    if ($connection instanceof PDO) {
        return $connection;
    }

    $config = parse_ini_file(dirname(__DIR__) . '/API/.env', false, INI_SCANNER_RAW) ?: [];
    $host = $config['DB_HOST'] ?? '127.0.0.1';
    $port = $config['DB_PORT'] ?? '3306';
    $database = $config['DB_DATABASE'] ?? 'bengkel_pos';
    $username = $config['DB_USERNAME'] ?? 'root';
    $password = $config['DB_PASSWORD'] ?? '';

    $connection = new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    return $connection;
}

function auth_captcha_create(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 5; $i++) {
        $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }

    $_SESSION['auth_captcha'] = [
        'hash' => password_hash($code, PASSWORD_DEFAULT),
        'expires' => time() + 300,
    ];

    return $code;
}

function auth_captcha_check(string $answer): bool
{
    $stored = $_SESSION['auth_captcha'] ?? null;
    unset($_SESSION['auth_captcha']);
    if (!is_array($stored) || (int) ($stored['expires'] ?? 0) < time()) {
        return false;
    }

    $answer = strtoupper((string) preg_replace('/\s+/', '', $answer));

    return $answer !== '' && password_verify($answer, (string) ($stored['hash'] ?? ''));
}

function auth_user(): ?array
{
    return isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user'])
        ? $_SESSION['auth_user']
        : null;
}

function auth_permissions(): array
{
    return array_values(array_filter(auth_user()['permissions'] ?? [], 'is_string'));
}

function auth_refresh_user(): ?array
{
    $sessionUser = auth_user();
    if (!$sessionUser || empty($sessionUser['id'])) {
        return null;
    }

    $statement = auth_database()->prepare('SELECT id, name, email, role, permissions, active FROM users WHERE id = ? LIMIT 1');
    $statement->execute([(int) $sessionUser['id']]);
    $user = $statement->fetch();
    if (!$user || !(bool) $user['active']) {
        unset($_SESSION['auth_user']);
        return null;
    }

    $permissions = json_decode((string) ($user['permissions'] ?? '[]'), true);
    $_SESSION['auth_user'] = [
        'id' => (int) $user['id'],
        'name' => (string) $user['name'],
        'email' => (string) $user['email'],
        'role' => (string) $user['role'],
        'permissions' => is_array($permissions) ? array_values(array_filter($permissions, 'is_string')) : [],
    ];

    return $_SESSION['auth_user'];
}

function auth_page_permission(string $pageKey): ?string
{
    return [
        'dashboard' => 'Dashboard',
        'transactions' => 'Transaksi',
        'parts' => 'Sparepart',
        'services' => 'Jasa',
        'settings' => 'Pengaturan',
    ][$pageKey] ?? null;
}

function auth_first_allowed_page(array $permissions = []): ?string
{
    $routes = [
        'Dashboard' => 'dashboard.php',
        'Transaksi' => 'transaksi.php',
        'Sparepart' => 'master_sparepart.php',
        'Jasa' => 'master_jasa.php',
        'Pengaturan' => 'settings.php',
    ];
    foreach ($routes as $permission => $route) {
        if (in_array($permission, $permissions, true)) {
            return $route;
        }
    }
    return null;
}

function require_auth_for_page(string $pageKey): void
{
    $user = auth_refresh_user();
    if (!$user) {
        $target = basename($_SERVER['REQUEST_URI'] ?? 'dashboard.php');
        header('Location: login.php?next=' . rawurlencode($target));
        exit;
    }

    $permission = auth_page_permission($pageKey);
    if ($permission && !in_array($permission, auth_permissions(), true)) {
        $fallback = auth_first_allowed_page(auth_permissions());
        if ($fallback) {
            header('Location: ' . $fallback . '?access=denied');
            exit;
        }
        http_response_code(403);
        exit('Akun Anda belum memiliki hak akses ke modul aplikasi.');
    }
}

function auth_initials(string $name): string
{
    $words = preg_split('/\s+/', trim($name)) ?: [];
    $initials = '';
    foreach (array_slice(array_filter($words), 0, 2) as $word) {
        $initials .= mb_strtoupper(mb_substr($word, 0, 1));
    }
    return $initials ?: 'U';
}
