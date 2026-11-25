<?php
/**
 * Public JSON API: Users / Customers Management for Crisp & Crave
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (file_exists(__DIR__ . '/../db_credentials.php')) {
    require_once __DIR__ . '/../db_credentials.php';
}
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'damdama_food');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'damdama_food');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // Handle Profile Update via POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: $_POST;

        $phone = trim($data['phone'] ?? '');
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $address = trim($data['address'] ?? '');
        $landmark = trim($data['landmark'] ?? '');

        if (!$phone || !$name) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Phone and Name are required.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        $existing = $stmt->fetch();

        if ($existing) {
            $upd = $pdo->prepare("UPDATE users SET name = ?, email = ?, address = ?, landmark = ? WHERE id = ?");
            $upd->execute([$name, $email ?: null, $address, $landmark, $existing['id']]);
            $userId = $existing['id'];
        } else {
            $ins = $pdo->prepare("INSERT INTO users (name, phone, email, address, landmark, total_orders, total_spend, status) VALUES (?, ?, ?, ?, ?, 0, 0.00, 1)");
            $ins->execute([$name, $phone, $email ?: null, $address, $landmark]);
            $userId = $pdo->lastInsertId();
        }

        $user = $pdo->query("SELECT * FROM users WHERE id = $userId")->fetch();

        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'user'    => $user
        ]);
        exit;
    }

    // Handle GET Profile
    $phone = trim($_GET['phone'] ?? '');
    if (!empty($phone)) {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        $stmt = $pdo->prepare("SELECT * FROM users WHERE REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', '') LIKE ? LIMIT 1");
        $stmt->execute(['%' . substr($cleanPhone, -10)]);
        $user = $stmt->fetch();

        if ($user) {
            echo json_encode([
                'success' => true,
                'user'    => $user
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'User not found'
            ]);
        }
        exit;
    }

    // Return all users for Admin
    $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC LIMIT 200");
    $users = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'count'   => count($users),
        'users'   => $users
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
