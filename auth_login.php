<?php

require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Metode tidak diizinkan']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$email = strtolower(trim((string) ($payload['email'] ?? '')));
$password = (string) ($payload['password'] ?? '');
$captcha = (string) ($payload['captcha'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '' || trim($captcha) === '') {
    http_response_code(422);
    echo json_encode(['message' => 'Email dan password wajib diisi dengan benar']);
    exit;
}

if (!auth_captcha_check($captcha)) {
    http_response_code(422);
    echo json_encode(['message' => 'Kode captcha tidak sesuai. Muat ulang kode lalu coba lagi.']);
    exit;
}

$statement = auth_database()->prepare('SELECT id, name, email, password, role, permissions, active FROM users WHERE email = ? LIMIT 1');
$statement->execute([$email]);
$user = $statement->fetch();

$storedHash = (string) ($user['password'] ?? '');
$isLegacyMd5 = (bool) preg_match('/^[a-f0-9]{32}$/i', $storedHash);
$passwordValid = $user && ($isLegacyMd5
    ? hash_equals(strtolower($storedHash), md5($password))
    : password_verify($password, $storedHash));

if (!$user || !(bool) $user['active'] || !$passwordValid) {
    usleep(250000);
    http_response_code(401);
    echo json_encode(['message' => 'Email atau password tidak sesuai']);
    exit;
}

// Accounts created before bcrypt still hold MD5 hashes; upgrade them on the first successful login.
if ($isLegacyMd5 || password_needs_rehash($storedHash, PASSWORD_DEFAULT)) {
    $upgrade = auth_database()->prepare('UPDATE users SET password = ? WHERE id = ?');
    $upgrade->execute([password_hash($password, PASSWORD_DEFAULT), (int) $user['id']]);
}

$permissions = json_decode((string) ($user['permissions'] ?? '[]'), true);
$permissions = is_array($permissions) ? array_values(array_filter($permissions, 'is_string')) : [];
$redirect = auth_first_allowed_page($permissions);
if (!$redirect) {
    http_response_code(403);
    echo json_encode(['message' => 'Akun belum memiliki hak akses ke modul aplikasi']);
    exit;
}

session_regenerate_id(true);
$_SESSION['auth_user'] = [
    'id' => (int) $user['id'],
    'name' => (string) $user['name'],
    'email' => (string) $user['email'],
    'role' => (string) $user['role'],
    'permissions' => $permissions,
];

if (!empty($payload['remember'])) {
    setcookie(session_name(), session_id(), [
        'expires' => time() + 60 * 60 * 24 * 30,
        'path' => '/',
        'httponly' => true,
        'secure' => auth_request_is_secure(),
        'samesite' => 'Lax',
    ]);
}

echo json_encode(['message' => 'Login berhasil', 'redirect' => $redirect]);
