<?php
/**
 * Public JSON API: Active Offers & Deals Sliders for Crisp & Crave Mobile App
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

    // Ensure offers table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS offers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        image_url VARCHAR(500) NOT NULL,
        product_id VARCHAR(100) DEFAULT NULL,
        display_order INT DEFAULT 1,
        status TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $count = (int)$pdo->query("SELECT COUNT(*) FROM offers")->fetchColumn();
    if ($count === 0) {
        $pdo->exec("INSERT INTO offers (title, image_url, product_id, display_order, status) VALUES
            ('Weekend Mega Feast 40% OFF', 'https://food.aiaxom.co.in/assets/images/biryani-handi.png', 'combo-biryani', 1, 1),
            ('Buy 1 Get 1 Crispy Momos Box', 'https://food.aiaxom.co.in/assets/images/momos.png', 'combo-momos', 2, 1);");
    }

    $stmt = $pdo->query("SELECT id, title, image_url, COALESCE(product_id, '') AS product_id, display_order FROM offers WHERE (status = 1 OR status IS NULL) ORDER BY display_order ASC, id DESC");
    $offers = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'count'   => count($offers),
        'offers'  => $offers
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load offers: ' . $e->getMessage()
    ]);
}
