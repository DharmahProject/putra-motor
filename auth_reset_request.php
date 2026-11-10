<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function reset_response(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function normalize_whatsapp_number(string $phone): string
{
    $digits = preg_replace('/\D+/', '', trim($phone)) ?? '';
    if (str_starts_with($digits, '00')) {
        $digits = substr($digits, 2);
    }
    if (str_starts_with($digits, '0')) {
        $digits = '62' . substr($digits, 1);
    } elseif (str_starts_with($digits, '8')) {
        $digits = '62' . $digits;
    }
    return $digits;
}

function reset_configuration(): array
{
    return auth_configuration();
}

function reset_public_url(array $config): string
{
    $configured = trim((string) ($config['POS_APP_URL'] ?? ''));
    if ($configured !== '') {
        return rtrim($configured, '/');
    }
    $secure = auth_request_is_secure();
    $scheme = $secure ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $directory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    return $scheme . '://' . $host . rtrim($directory, '/');
}

function send_reset_whatsapp(string $target, string $message, array $config): bool
{
    $apiUrl = trim((string) ($config['FONNTE_API_URL'] ?? 'https://api.fonnte.com/send'));
    $apiToken = trim((string) ($config['FONNTE_TOKEN'] ?? ''));
    if ($apiToken === '' || !function_exists('curl_init')) {
        return false;
    }

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'target' => $target,
            'message' => $message,
            'countryCode' => '62',
            'preview' => 'true',
        ],
        CURLOPT_HTTPHEADER => ['Authorization: ' . $apiToken],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
    ]);
    $response = curl_exec($curl);
    $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($response === false || $curlError !== '' || $statusCode < 200 || $statusCode >= 300) {
        error_log('Pengiriman WhatsApp reset password gagal: ' . ($curlError ?: 'HTTP ' . $statusCode));
        return false;
    }
    $payload = json_decode((string) $response, true);
    $success = is_array($payload) && filter_var($payload['status'] ?? false, FILTER_VALIDATE_BOOLEAN);
    if (!$success) {
        error_log('Fonnte menolak pengiriman reset password: ' . substr((string) $response, 0, 500));
    }
    return $success;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    reset_response(405, ['message' => 'Metode permintaan tidak diizinkan.']);
}

$input = json_decode((string) file_get_contents('php://input'), true);
$phone = normalize_whatsapp_number((string) ($input['phone'] ?? ''));
if (!preg_match('/^62[0-9]{8,13}$/', $phone)) {
    reset_response(422, ['message' => 'Nomor WhatsApp tidak valid.']);
}

$config = reset_configuration();
if (trim((string) ($config['FONNTE_TOKEN'] ?? '')) === '') {
    reset_response(503, ['message' => 'Layanan WhatsApp belum dikonfigurasi. Hubungi administrator.']);
}

$genericMessage = 'Jika nomor tersebut terdaftar, tautan reset password akan dikirimkan melalui WhatsApp.';

try {
    $statement = auth_database()->query(
        'SELECT users.id, users.name, users.email, karyawan.phone
         FROM users
         INNER JOIN karyawan ON karyawan.id = users.karyawan_id
         WHERE users.active = 1 AND karyawan.active = 1'
    );
    $account = null;
    foreach ($statement->fetchAll() as $candidate) {
        if (hash_equals($phone, normalize_whatsapp_number((string) $candidate['phone']))) {
            $account = $candidate;
            break;
        }
    }

    if (!$account) {
        usleep(300000);
        reset_response(200, ['message' => $genericMessage]);
    }

    $cooldown = auth_database()->prepare(
        'SELECT created_at FROM password_reset_tokens WHERE email = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 60 SECOND)'
    );
    $cooldown->execute([(string) $account['email']]);
    if ($cooldown->fetch()) {
        reset_response(200, ['message' => $genericMessage]);
    }

    $plainToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $plainToken);
    $saveToken = auth_database()->prepare(
        'INSERT INTO password_reset_tokens (email, token, created_at) VALUES (?, ?, NOW())
         ON DUPLICATE KEY UPDATE token = VALUES(token), created_at = VALUES(created_at)'
    );
    $saveToken->execute([(string) $account['email'], $tokenHash]);

    $link = reset_public_url($config) . '/reset_password.php?token=' . rawurlencode($plainToken)
        . '&email=' . rawurlencode((string) $account['email']);
    $message = "Halo {$account['name']},\n\nKami menerima permintaan reset password akun Putra Motor. Buka tautan berikut untuk membuat password baru:\n{$link}\n\nTautan berlaku selama 30 menit dan hanya dapat digunakan satu kali. Abaikan pesan ini jika Anda tidak meminta reset password.";

    if (!send_reset_whatsapp($phone, $message, $config)) {
        $deleteToken = auth_database()->prepare('DELETE FROM password_reset_tokens WHERE email = ?');
        $deleteToken->execute([(string) $account['email']]);
        reset_response(502, ['message' => 'Pesan WhatsApp belum dapat dikirim. Silakan coba kembali atau hubungi administrator.']);
    }

    reset_response(200, ['message' => $genericMessage]);
} catch (Throwable $error) {
    error_log('Reset password gagal diproses: ' . $error->getMessage());
    reset_response(500, ['message' => 'Permintaan reset password belum dapat diproses.']);
}
