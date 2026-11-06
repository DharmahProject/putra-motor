<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function password_reset_response(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    password_reset_response(405, ['message' => 'Metode permintaan tidak diizinkan.']);
}

$input = json_decode((string) file_get_contents('php://input'), true);
$token = strtolower(trim((string) ($input['token'] ?? '')));
$email = strtolower(trim((string) ($input['email'] ?? '')));
$password = (string) ($input['password'] ?? '');
$confirmation = (string) ($input['password_confirmation'] ?? '');

if (!preg_match('/^[a-f0-9]{64}$/', $token) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    password_reset_response(422, ['message' => 'Tautan reset password tidak valid.']);
}
if (strlen($password) < 8 || strlen($password) > 100) {
    password_reset_response(422, ['message' => 'Password harus terdiri dari 8 sampai 100 karakter.']);
}
if (!hash_equals($password, $confirmation)) {
    password_reset_response(422, ['message' => 'Konfirmasi password tidak sama.']);
}

$database = auth_database();
try {
    $database->beginTransaction();
    $statement = $database->prepare(
        'SELECT email, token FROM password_reset_tokens
         WHERE email = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
         LIMIT 1 FOR UPDATE'
    );
    $statement->execute([$email]);
    $reset = $statement->fetch();
    $validToken = $reset && hash_equals((string) $reset['token'], hash('sha256', $token));
    if (!$validToken) {
        $database->rollBack();
        password_reset_response(422, ['message' => 'Tautan sudah tidak berlaku. Silakan minta tautan reset yang baru.']);
    }

    $update = $database->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE email = ? AND active = 1');
    $update->execute([password_hash($password, PASSWORD_DEFAULT), $email]);
    if ($update->rowCount() < 1) {
        $database->rollBack();
        password_reset_response(422, ['message' => 'Akun tidak ditemukan atau sudah tidak aktif.']);
    }

    $delete = $database->prepare('DELETE FROM password_reset_tokens WHERE email = ?');
    $delete->execute([$email]);
    $database->commit();
    password_reset_response(200, ['message' => 'Password baru sudah disimpan. Silakan masuk menggunakan password tersebut.']);
} catch (Throwable $error) {
    if ($database->inTransaction()) {
        $database->rollBack();
    }
    error_log('Penyimpanan password baru gagal: ' . $error->getMessage());
    password_reset_response(500, ['message' => 'Password baru belum dapat disimpan. Silakan coba kembali.']);
}
