<?php
/**
 * Public JSON API: Orders Query & History for Crisp & Crave
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

    $phone = trim($_GET['phone'] ?? '');

    if (!empty($phone)) {
        // Normalize phone: strip spaces and dashes
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE REPLACE(REPLACE(REPLACE(customer_phone, ' ', ''), '-', ''), '+', '') LIKE ? ORDER BY id DESC LIMIT 50");
        $stmt->execute(['%' . substr($cleanPhone, -10)]);
    } else {
        $stmt = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 100");
    }

    $orders = $stmt->fetchAll();

    // Decode items_json for each order
    foreach ($orders as &$order) {
        $decoded = json_decode($order['items_json'] ?? '[]', true);
        $order['items'] = $decoded ?: [];
    }

    echo json_encode([
        'success' => true,
        'count'   => count($orders),
        'orders'  => $orders
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch orders: ' . $e->getMessage()
    ]);
}
