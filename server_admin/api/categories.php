<?php
/**
 * Public JSON API: Active Food Categories for Crisp & Crave Mobile App
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

    // Ensure categories table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id VARCHAR(100) NOT NULL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        icon VARCHAR(100) DEFAULT 'utensils',
        display_order INT DEFAULT 1,
        status TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $count = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    if ($count === 0) {
        $pdo->exec("INSERT INTO categories (id, name, icon, display_order, status) VALUES
            ('chicken', 'Biryani & Chicken', 'utensils', 1, 1),
            ('burgers', 'Gourmet Burgers', 'burger', 2, 1),
            ('fries', 'Loaded Fries & Sides', 'french-fries', 3, 1),
            ('wraps', 'Wraps & Bowls', 'bowl-food', 4, 1),
            ('drinks', 'Thick Shakes & Drinks', 'mug-hot', 5, 1),
            ('desserts', 'Desserts & Cakes', 'cake-candles', 6, 1),
            ('combos', 'Mega Combos', 'fire', 7, 1);");
    }

    $stmt = $pdo->query("SELECT id, name, icon, display_order FROM categories WHERE status = 1 ORDER BY display_order ASC, name ASC");
    $categories = $stmt->fetchAll();

    echo json_encode([
        'success'    => true,
        'count'      => count($categories),
        'categories' => $categories
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load categories: ' . $e->getMessage()
    ]);
}
