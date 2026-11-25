<?php
/**
 * Public JSON API: Create New Food Order for Crisp & Crave
 * Handles orders from Flutter Mobile App & Website
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed. Use POST.']);
    exit;
}

try {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (!$data) {
        // Fallback to $_POST
        $data = $_POST;
    }

    $customerName = trim($data['customer_name'] ?? '');
    $customerPhone = trim($data['customer_phone'] ?? '');
    $deliveryAddress = trim($data['delivery_address'] ?? '');
    $landmark = trim($data['landmark'] ?? '');
    $items = $data['items'] ?? [];
    $subtotal = (float)($data['subtotal'] ?? 0);
    $deliveryFee = (float)($data['delivery_fee'] ?? 0);
    $taxAmount = (float)($data['tax_amount'] ?? 0);
    $discountAmount = (float)($data['discount_amount'] ?? 0);
    $couponCode = trim($data['coupon_code'] ?? '');
    $grandTotal = (float)($data['grand_total'] ?? 0);
    $paymentMethod = trim($data['payment_method'] ?? 'COD');
    $cookingInstructions = trim($data['cooking_instructions'] ?? '');
    $source = trim($data['source'] ?? 'mobile_app');

    if (empty($customerName) || empty($customerPhone) || empty($deliveryAddress)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Customer Name, Phone Number, and Delivery Address are required.'
        ]);
        exit;
    }

    if (empty($items)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Cart is empty. Please add items to place an order.'
        ]);
        exit;
    }

    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // Ensure tables exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        phone VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(255) DEFAULT NULL,
        address TEXT DEFAULT NULL,
        landmark VARCHAR(255) DEFAULT NULL,
        total_orders INT DEFAULT 0,
        total_spend DECIMAL(10,2) DEFAULT 0.00,
        status TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_number VARCHAR(50) NOT NULL UNIQUE,
        customer_name VARCHAR(255) NOT NULL,
        customer_phone VARCHAR(50) NOT NULL,
        delivery_address TEXT NOT NULL,
        landmark VARCHAR(255) DEFAULT NULL,
        items_json LONGTEXT NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        coupon_code VARCHAR(50) DEFAULT NULL,
        grand_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        payment_method VARCHAR(50) NOT NULL DEFAULT 'COD',
        payment_status VARCHAR(50) NOT NULL DEFAULT 'pending',
        order_status VARCHAR(50) NOT NULL DEFAULT 'preparing',
        cooking_instructions TEXT DEFAULT NULL,
        source VARCHAR(50) DEFAULT 'mobile_app',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 1. Create Unique Order Number
    $orderNumber = 'CC-' . strtoupper(dechex(time())) . rand(10, 99);

    // 2. Insert into orders table
    $itemsJson = is_string($items) ? $items : json_encode($items, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $stmt = $pdo->prepare("INSERT INTO orders (
        order_number, customer_name, customer_phone, delivery_address, landmark,
        items_json, subtotal, delivery_fee, tax_amount, discount_amount, coupon_code,
        grand_total, payment_method, payment_status, order_status, cooking_instructions, source
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $paymentStatus = (strtolower($paymentMethod) === 'upi' || strtolower($paymentMethod) === 'online') ? 'paid' : 'pending';

    $stmt->execute([
        $orderNumber, $customerName, $customerPhone, $deliveryAddress, $landmark,
        $itemsJson, $subtotal, $deliveryFee, $taxAmount, $discountAmount, $couponCode ?: null,
        $grandTotal, $paymentMethod, $paymentStatus, 'preparing', $cookingInstructions ?: null, $source
    ]);

    $orderDbId = $pdo->lastInsertId();

    // 3. Upsert User/Customer Profile
    $uStmt = $pdo->prepare("SELECT id, total_orders, total_spend FROM users WHERE phone = ?");
    $uStmt->execute([$customerPhone]);
    $existingUser = $uStmt->fetch();

    if ($existingUser) {
        $updUser = $pdo->prepare("UPDATE users SET 
            name = ?, address = ?, landmark = ?, 
            total_orders = total_orders + 1, 
            total_spend = total_spend + ? 
            WHERE id = ?");
        $updUser->execute([$customerName, $deliveryAddress, $landmark, $grandTotal, $existingUser['id']]);
    } else {
        $insUser = $pdo->prepare("INSERT INTO users (name, phone, address, landmark, total_orders, total_spend, status) VALUES (?, ?, ?, ?, 1, ?, 1)");
        $insUser->execute([$customerName, $customerPhone, $deliveryAddress, $landmark, $grandTotal]);
    }

    echo json_encode([
        'success'      => true,
        'order_id'     => $orderNumber,
        'db_id'        => $orderDbId,
        'message'      => 'Order placed successfully! Chef is firing up the kitchen grill.',
        'customer'     => [
            'name'    => $customerName,
            'phone'   => $customerPhone,
            'address' => $deliveryAddress
        ],
        'grand_total'  => $grandTotal,
        'order_status' => 'preparing'
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to place order: ' . $e->getMessage()
    ]);
}
