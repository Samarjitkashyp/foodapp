<?php
/**
 * Public JSON API: Active Food Catalog Items for Crisp & Crave Mobile App
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
define('SITE_URL', 'https://food.aiaxom.co.in/');
define('FOOD_UPLOAD_URL', SITE_URL . 'uploads/food/');

function formatFoodImageUrl($img) {
    if (empty($img)) return 'https://via.placeholder.com/200x200?text=Food';
    if (strpos($img, 'http://') === 0 || strpos($img, 'https://') === 0) {
        return $img;
    }
    if (strpos($img, 'assets/') === 0 || strpos($img, 'uploads/') === 0) {
        return SITE_URL . ltrim($img, '/');
    }
    return FOOD_UPLOAD_URL . ltrim($img, '/');
}

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    $stmt = $pdo->query("SELECT * FROM food_items WHERE status = 1 ORDER BY display_order ASC, id ASC");
    $rows = $stmt->fetchAll();

    $items = [];
    foreach ($rows as $r) {
        $rawAddons = !empty($r['addons']) ? json_decode($r['addons'], true) : [];
        $addonsList = [];
        if (is_array($rawAddons)) {
            foreach ($rawAddons as $ad) {
                if (!empty($ad['name'])) {
                    $addonsList[] = [
                        'name'  => (string)$ad['name'],
                        'price' => (float)($ad['price'] ?? 0)
                    ];
                }
            }
        }

        $items[] = [
            'id'             => $r['id'],
            'name'           => $r['name'],
            'category'       => $r['category'],
            'price'          => (float)$r['price'],
            'original_price' => !empty($r['original_price']) ? (float)$r['original_price'] : (float)$r['price'],
            'rating'         => (float)($r['rating'] ?? 4.8),
            'reviews_count'  => (int)($r['reviews_count'] ?? 120),
            'is_veg'         => (int)$r['is_veg'] === 1,
            'badge'          => !empty($r['badge']) ? $r['badge'] : null,
            'spicy_level'    => (int)($r['spicy_level'] ?? 1),
            'prep_time'      => !empty($r['prep_time']) ? $r['prep_time'] : '15-20 min',
            'portion'        => !empty($r['portion']) ? $r['portion'] : null,
            'pieces'         => !empty($r['portion']) ? $r['portion'] : null,
            'addons'         => $addonsList,
            'calories'       => !empty($r['calories']) ? $r['calories'] : '450 kcal',
            'image'          => formatFoodImageUrl($r['image']),
            'description'    => !empty($r['description']) ? $r['description'] : '',
            'ingredients'    => []
        ];
    }

    echo json_encode([
        'success' => true,
        'count'   => count($items),
        'items'   => $items
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load food items: ' . $e->getMessage()
    ]);
}
