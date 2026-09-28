<?php
declare(strict_types=1);

/**
 * Future Skills – Robotics & AI API
 *
 * Required environment variables:
 * DB_HOST, DB_NAME, DB_USER, DB_PASS
 * ADMIN_EMAIL, ADMIN_PASSWORD_HASH, JWT_SECRET
 * ALLOWED_ORIGINS (comma-separated frontend origins)
 *
 * Generate an admin password hash:
 *   php -r "echo password_hash('CHANGE_ME', PASSWORD_DEFAULT), PHP_EOL;"
 */

header('Content-Type: application/json; charset=utf-8');

$allowedOrigins = array_filter(array_map('trim', explode(',', getenv('ALLOWED_ORIGINS') ?: '')));
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin && in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function jsonResponse(bool $success, $data = null, string $message = '', int $status = 200, ?string $code = null): never {
    http_response_code($status);
    $body = ['success' => $success];
    if ($success) {
        if ($data !== null) $body['data'] = $data;
    } else {
        $body['message'] = $message ?: 'Request failed.';
        if ($code) $body['code'] = $code;
    }
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function envOrFail(string $key): string {
    $value = getenv($key);
    if ($value === false || $value === '') {
        jsonResponse(false, null, "Server configuration missing: {$key}", 500, 'CONFIG_ERROR');
    }
    return $value;
}

function requestBody(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') return [];
    $data = json_decode($raw, true);
    if (!is_array($data)) jsonResponse(false, null, 'Invalid JSON request body.', 400, 'INVALID_JSON');
    return $data;
}

function cleanString($value, int $max = 4000): string {
    $value = trim((string)$value);
    if (mb_strlen($value) > $max) $value = mb_substr($value, 0, $max);
    return $value;
}

function base64UrlEncode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
function base64UrlDecode(string $data): string {
    return base64_decode(strtr($data, '-_', '+/')) ?: '';
}

function createToken(string $subject, string $secret, int $ttl = 28800): string {
    $header = base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = base64UrlEncode(json_encode(['sub' => $subject, 'iat' => time(), 'exp' => time() + $ttl]));
    $signature = base64UrlEncode(hash_hmac('sha256', "{$header}.{$payload}", $secret, true));
    return "{$header}.{$payload}.{$signature}";
}

function requireAdmin(string $secret): string {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
        jsonResponse(false, null, 'Authentication required.', 401, 'UNAUTHORIZED');
    }
    $token = $matches[1];
    $parts = explode('.', $token);
    if (count($parts) !== 3) jsonResponse(false, null, 'Invalid authentication token.', 401, 'UNAUTHORIZED');

    [$h, $p, $s] = $parts;
    $expected = base64UrlEncode(hash_hmac('sha256', "{$h}.{$p}", $secret, true));
    if (!hash_equals($expected, $s)) jsonResponse(false, null, 'Invalid authentication token.', 401, 'UNAUTHORIZED');

    $payload = json_decode(base64UrlDecode($p), true);
    if (!is_array($payload) || empty($payload['sub']) || empty($payload['exp']) || (int)$payload['exp'] < time()) {
        jsonResponse(false, null, 'Authentication token expired or invalid.', 401, 'UNAUTHORIZED');
    }
    return (string)$payload['sub'];
}

try {
    $pdo = new PDO(
        'mysql:host=' . envOrFail('DB_HOST') . ';dbname=' . envOrFail('DB_NAME') . ';charset=utf8mb4',
        envOrFail('DB_USER'),
        envOrFail('DB_PASS'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (Throwable $e) {
    error_log($e->getMessage());
    jsonResponse(false, null, 'Database connection failed.', 500, 'DB_ERROR');
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($action === 'content' && $method === 'GET') {
        $body = requestBody();
        $page = cleanString($body['page'] ?? $_GET['page'] ?? '', 50);
        $allowedPages = ['home', 'scope', 'skills'];
        if (!in_array($page, $allowedPages, true)) {
            jsonResponse(false, null, 'Invalid content page.', 400, 'INVALID_PAGE');
        }

        $stmt = $pdo->prepare('SELECT content_key, title, content_json FROM site_content WHERE page = :page ORDER BY id ASC');
        $stmt->execute(['page' => $page]);
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $decoded = json_decode($row['content_json'], true);
            $result[$row['content_key']] = $decoded ?? $row['content_json'];
        }
        jsonResponse(true, $result);
    }

    if ($action === 'admin_login' && $method === 'POST') {
        $body = requestBody();
        $email = strtolower(cleanString($body['email'] ?? '', 190));
        $password = (string)($body['password'] ?? '');
        $adminEmail = strtolower(envOrFail('ADMIN_EMAIL'));
        $hash = envOrFail('ADMIN_PASSWORD_HASH');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !hash_equals($adminEmail, $email) || !password_verify($password, $hash)) {
            // Same response for invalid email/password to avoid account enumeration.
            jsonResponse(false, null, 'Invalid email or password.', 401, 'INVALID_CREDENTIALS');
        }

        $token = createToken($email, envOrFail('JWT_SECRET'));
        jsonResponse(true, ['token' => $token]);
    }

    if ($action === 'content' && $method === 'PUT') {
        $admin = requireAdmin(envOrFail('JWT_SECRET'));
        $body = requestBody();
        $page = cleanString($body['page'] ?? '', 50);
        $data = $body['data'] ?? null;
        $allowedPages = ['home', 'scope', 'skills'];

        if (!in_array($page, $allowedPages, true) || !is_array($data)) {
            jsonResponse(false, null, 'Invalid content payload.', 400, 'INVALID_PAYLOAD');
        }

        // Limit stored payload size and keys to prevent accidental abuse.
        if (strlen(json_encode($data)) > 50000) {
            jsonResponse(false, null, 'Content payload is too large.', 413, 'PAYLOAD_TOO_LARGE');
        }

        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE site_content SET content_json = :content_json, updated_by = :updated_by WHERE page = :page AND content_key = :content_key');

        foreach ($data as $key => $value) {
            $key = cleanString($key, 100);
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $key)) continue;
            $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json === false) continue;
            $stmt->execute([
                'content_json' => $json,
                'updated_by' => $admin,
                'page' => $page,
                'content_key' => $key
            ]);
        }
        $pdo->commit();
        jsonResponse(true, ['message' => 'Content updated.']);
    }

    if ($action === 'contact' && $method === 'POST') {
        $body = requestBody();
        $school = cleanString($body['school_name'] ?? '', 150);
        $name = cleanString($body['name'] ?? '', 120);
        $email = strtolower(cleanString($body['email'] ?? '', 190));
        $phone = cleanString($body['phone'] ?? '', 30);
        $message = cleanString($body['message'] ?? '', 4000);

        if ($school === '' || $name === '' || $phone === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(false, null, 'Please provide valid school name, name, email, phone and message.', 422, 'VALIDATION_ERROR');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO contact_messages (school_name, name, email, phone, message, status)
             VALUES (:school_name, :name, :email, :phone, :message, "Pending")'
        );
        $stmt->execute([
            'school_name' => $school,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'message' => $message
        ]);
        jsonResponse(true, ['id' => (int)$pdo->lastInsertId()]);
    }

    if ($action === 'messages' && $method === 'GET') {
        requireAdmin(envOrFail('JWT_SECRET'));
        $stmt = $pdo->query(
            'SELECT id, school_name, name, email, phone, message, status, admin_reply, replied_at, created_at
             FROM contact_messages ORDER BY created_at DESC LIMIT 500'
        );
        jsonResponse(true, $stmt->fetchAll());
    }

    if ($action === 'reply' && $method === 'POST') {
        $admin = requireAdmin(envOrFail('JWT_SECRET'));
        $body = requestBody();
        $id = filter_var($body['id'] ?? null, FILTER_VALIDATE_INT);
        $reply = cleanString($body['reply_text'] ?? '', 4000);

        if (!$id || $reply === '') {
            jsonResponse(false, null, 'A message ID and reply are required.', 422, 'VALIDATION_ERROR');
        }

        $stmt = $pdo->prepare(
            'UPDATE contact_messages
             SET admin_reply = :reply, status = "Replied", replied_at = NOW(), replied_by = :replied_by
             WHERE id = :id'
        );
        $stmt->execute(['reply' => $reply, 'replied_by' => $admin, 'id' => $id]);

        if ($stmt->rowCount() === 0) {
            jsonResponse(false, null, 'Inquiry not found.', 404, 'NOT_FOUND');
        }
        jsonResponse(true, ['message' => 'Reply saved.']);
    }

    jsonResponse(false, null, 'Unknown API action.', 404, 'NOT_FOUND');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log($e->getMessage());
    jsonResponse(false, null, 'Server error.', 500, 'SERVER_ERROR');
}
?>
