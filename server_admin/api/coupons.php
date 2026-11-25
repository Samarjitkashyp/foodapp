<?php
/**
 * Public JSON API: Active Coupons & Promo Codes for Crisp & Crave Mobile App
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

    // Ensure coupons table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS coupons (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) NOT NULL UNIQUE,
        title VARCHAR(255) NOT NULL,
        description TEXT DEFAULT NULL,
        discount_type ENUM('percentage', 'flat') DEFAULT 'percentage',
        discount_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        max_discount DECIMAL(10,2) DEFAULT NULL,
        min_order DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        display_order INT DEFAULT 1,
        status TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $count = (int)$pdo->query("SELECT COUNT(*) FROM coupons")->fetchColumn();
    if ($count === 0) {
        $pdo->exec("INSERT INTO coupons (code, title, description, discount_type, discount_value, max_discount, min_order, display_order, status) VALUES
            ('FIRST50', '50% OFF First Order', 'Get 50% discount up to ₹120 on orders above ₹249', 'percentage', 50.00, 120.00, 249.00, 1, 1),
            ('CRUNCH20', '20% OFF Crispy Deal', 'Save 20% up to ₹150 on orders above ₹399', 'percentage', 20.00, 150.00, 399.00, 2, 1),
            ('FREECHEF', 'Flat ₹100 Special', 'Flat ₹100 instant discount on orders above ₹499', 'flat', 100.00, 100.00, 499.00, 3, 1);");
    }

    $stmt = $pdo->query("SELECT id, code, title, COALESCE(description, '') AS description, discount_type, CAST(discount_value AS FLOAT) AS discount_value, CAST(max_discount AS FLOAT) AS max_discount, CAST(min_order AS FLOAT) AS min_order, display_order FROM coupons WHERE (status = 1 OR status IS NULL) ORDER BY display_order ASC, id DESC");
    $coupons = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'count'   => count($coupons),
        'coupons' => $coupons
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load coupons: ' . $e->getMessage()
    ]);
}
