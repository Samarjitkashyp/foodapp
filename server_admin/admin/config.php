<?php
/**
 * Crisp & Crave - Database & Site Configuration
 */

// Load Database Credentials safely
if (file_exists(__DIR__ . '/../db_credentials.php')) {
    require_once __DIR__ . '/../db_credentials.php';
}
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'damdama_food');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'damdama_food');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

define('SITE_URL', 'https://food.aiaxom.co.in/');
define('UPLOAD_DIR', __DIR__ . '/../uploads/banners/');
define('UPLOAD_URL', SITE_URL . 'uploads/banners/');
define('FOOD_UPLOAD_DIR', __DIR__ . '/../uploads/food/');
define('FOOD_UPLOAD_URL', SITE_URL . 'uploads/food/');
define('OFFERS_UPLOAD_DIR', __DIR__ . '/../uploads/offers/');
define('OFFERS_UPLOAD_URL', SITE_URL . 'uploads/offers/');

function getDb() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

            // Auto-align schema if user created table with target_product_id / is_active
            try {
                $cols = $pdo->query("SHOW COLUMNS FROM banners")->fetchAll(PDO::FETCH_COLUMN);
                if (!empty($cols)) {
                    if (!in_array('product_id', $cols) && in_array('target_product_id', $cols)) {
                        $pdo->exec("ALTER TABLE banners ADD COLUMN product_id VARCHAR(100) NULL AFTER image_url");
                        $pdo->exec("UPDATE banners SET product_id = target_product_id");
                    }
                    if (!in_array('status', $cols) && in_array('is_active', $cols)) {
                        $pdo->exec("ALTER TABLE banners ADD COLUMN status TINYINT(1) DEFAULT 1 AFTER display_order");
                        $pdo->exec("UPDATE banners SET status = is_active");
                    }
                    $pdo->exec("UPDATE banners SET product_id = 'biryani-01' WHERE product_id = 'biryani-handi'");
                    $pdo->exec("UPDATE banners SET product_id = 'momos-01' WHERE product_id = 'momos'");
                    $pdo->exec("UPDATE banners SET product_id = 'rice-01' WHERE product_id = 'fried-rice'");
                }
            } catch (\Exception $ex) {}

            // Auto-align food_items table
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS food_items (
                    id VARCHAR(100) NOT NULL PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    category VARCHAR(100) NOT NULL DEFAULT 'chicken',
                    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    original_price DECIMAL(10,2) DEFAULT NULL,
                    rating DECIMAL(3,2) DEFAULT 4.8,
                    reviews_count INT DEFAULT 150,
                    is_veg TINYINT(1) DEFAULT 0,
                    badge VARCHAR(100) DEFAULT NULL,
                    spicy_level TINYINT DEFAULT 1,
                    prep_time VARCHAR(50) DEFAULT '15-20 min',
                    portion VARCHAR(100) DEFAULT NULL,
                    calories VARCHAR(50) DEFAULT '450 kcal',
                    image VARCHAR(500) DEFAULT '',
                    description TEXT DEFAULT NULL,
                    status TINYINT(1) DEFAULT 1,
                    display_order INT DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                // Auto-add portion column if table already exists
                $checkPortionCol = $pdo->query("SHOW COLUMNS FROM food_items LIKE 'portion'")->fetch();
                if (!$checkPortionCol) {
                    $pdo->exec("ALTER TABLE food_items ADD COLUMN portion VARCHAR(100) DEFAULT NULL AFTER prep_time;");
                }

                // Auto-add addons column if table already exists
                $checkAddonsCol = $pdo->query("SHOW COLUMNS FROM food_items LIKE 'addons'")->fetch();
                if (!$checkAddonsCol) {
                    $pdo->exec("ALTER TABLE food_items ADD COLUMN addons TEXT DEFAULT NULL AFTER portion;");
                }
            } catch (\Exception $ex) {}

            // Auto-align categories table
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
                    id VARCHAR(100) NOT NULL PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    icon VARCHAR(100) DEFAULT 'utensils',
                    display_order INT DEFAULT 1,
                    status TINYINT(1) DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                $cCount = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
                if ($cCount === 0) {
                    $pdo->exec("INSERT INTO categories (id, name, icon, display_order, status) VALUES
                        ('chicken', 'Biryani & Chicken', 'utensils', 1, 1),
                        ('burgers', 'Gourmet Burgers', 'burger', 2, 1),
                        ('fries', 'Loaded Fries & Sides', 'french-fries', 3, 1),
                        ('wraps', 'Wraps & Bowls', 'bowl-food', 4, 1),
                        ('drinks', 'Thick Shakes & Drinks', 'mug-hot', 5, 1),
                        ('desserts', 'Desserts & Cakes', 'cake-candles', 6, 1),
                        ('combos', 'Mega Combos', 'fire', 7, 1);");
                }
            } catch (\Exception $ex) {}

            // Auto-align offers table
            try {
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

                $oCount = (int)$pdo->query("SELECT COUNT(*) FROM offers")->fetchColumn();
                if ($oCount === 0) {
                    $pdo->exec("INSERT INTO offers (title, image_url, product_id, display_order, status) VALUES
                        ('Weekend Mega Feast 40% OFF', 'https://food.aiaxom.co.in/assets/images/biryani-handi.png', 'combo-biryani', 1, 1),
                        ('Buy 1 Get 1 Crispy Momos Box', 'https://food.aiaxom.co.in/assets/images/momos.png', 'combo-momos', 2, 1);");
                }
            } catch (\Exception $ex) {}

            // Auto-align coupons table
            try {
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

                $cpCount = (int)$pdo->query("SELECT COUNT(*) FROM coupons")->fetchColumn();
                if ($cpCount === 0) {
                    $pdo->exec("INSERT INTO coupons (code, title, description, discount_type, discount_value, max_discount, min_order, display_order, status) VALUES
                        ('FIRST50', '50% OFF First Order', 'Get 50% discount up to ₹120 on orders above ₹249', 'percentage', 50.00, 120.00, 249.00, 1, 1),
                        ('CRUNCH20', '20% OFF Crispy Deal', 'Save 20% up to ₹150 on orders above ₹399', 'percentage', 20.00, 150.00, 399.00, 2, 1),
                        ('FREECHEF', 'Flat ₹100 Special', 'Flat ₹100 instant discount on orders above ₹499', 'flat', 100.00, 100.00, 499.00, 3, 1);");
                }
            } catch (\Exception $ex) {}

            // Auto-align users table
            try {
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
            } catch (\Exception $ex) {}

            // Auto-align orders table
            try {
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
            } catch (\Exception $ex) {}

        } catch (\PDOException $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

function getFoodImageUrl($img) {
    if (empty($img)) return 'https://via.placeholder.com/120x120?text=Food';
    if (strpos($img, 'http://') === 0 || strpos($img, 'https://') === 0) {
        return $img;
    }
    if (strpos($img, 'assets/') === 0) {
        return SITE_URL . $img;
    }
    if (strpos($img, 'uploads/') === 0) {
        return SITE_URL . $img;
    }
    return FOOD_UPLOAD_URL . ltrim($img, '/');
}

$FOOD_CATEGORIES_DEFAULT = [
    'chicken'  => 'Biryani & Chicken',
    'burgers'  => 'Gourmet Burgers',
    'fries'    => 'Loaded Fries & Sides',
    'wraps'    => 'Wraps & Bowls',
    'drinks'   => 'Thick Shakes & Drinks',
    'desserts' => 'Desserts & Cakes',
    'combos'   => 'Mega Combos'
];

function getFoodCategoriesList() {
    global $FOOD_CATEGORIES_DEFAULT;
    try {
        $db = getDb();
        $stmt = $db->query("SELECT id, name FROM categories WHERE status = 1 ORDER BY display_order ASC, name ASC");
        $rows = $stmt->fetchAll();
        if (!empty($rows)) {
            $cats = [];
            foreach ($rows as $r) {
                $cats[$r['id']] = $r['name'];
            }
            return $cats;
        }
    } catch (\Exception $e) {}
    return $FOOD_CATEGORIES_DEFAULT;
}

$FOOD_CATEGORIES = getFoodCategoriesList();

$APP_PRODUCTS_DEFAULT = [
    'biryani-01' => 'Royal Hyderabadi Chicken Dum Biryani (₹329)',
    'momos-01'   => 'Steamed & Crispy Juicy Momos (8 pcs) (₹179)',
    'rice-01'    => 'Spicy Wok Schezwan Fried Rice (₹219)',
    'noodles-01' => 'Sizzling Wok Chicken Chowmein (₹209)',
    'chicken-01' => 'Crispy Golden Fried Chicken Tenders (₹229)',
    'chicken-02' => 'Fiery Ghost Pepper Wings (6 pcs) (₹249)',
    'burger-01'  => 'The Grand Smokehouse Burger (₹289)',
    'burger-02'  => 'Crunchy Fiery Zinger Burger (₹249)',
    'burger-03'  => 'Double Melt Cheeseburger (₹269)',
    'burger-04'  => 'Smoked Truffle Portobello Burger (Veg) (₹239)',
    'burger-05'  => 'Spicy Paneer Tikka Smash Burger (Veg) (₹219)',
    'fries-01'   => 'Truffle Parmesan Hand-Cut Fries (₹159)',
    'fries-02'   => 'Cheesy Bacon Overloaded Fries (₹199)',
    'fries-03'   => 'Peri Peri Seasoned Crinkle Fries (₹129)',
    'fries-04'   => 'Crispy Golden Onion Rings (₹139)',
    'wrap-01'    => 'Smoky BBQ Grilled Chicken Wrap (₹199)',
    'wrap-02'    => 'Crunchy Falafel Hummus Wrap (Veg) (₹179)',
    'drink-01'   => 'Decadent Belgian Chocolate Shake (₹169)',
    'drink-02'   => 'Salted Caramel Lotus Biscoff Shake (₹189)',
    'drink-03'   => 'Royal Alphonso Mango Thick Shake (₹159)',
    'drink-04'   => 'Chilled Sparking Peach Iced Tea (₹119)',
    'dessert-01' => 'Molten Dark Chocolate Lava Cake (₹149)',
    'combo-momos'      => 'Combo: Steamed & Crispy Momos Feast (₹249)',
    'combo-chowmein'   => 'Combo: Wok-Hei Chicken Chowmein Box (₹269)',
    'combo-rice'       => 'Combo: Spicy Schezwan Fried Rice Box (₹289)',
    'combo-biryani'    => 'Combo: Royal Biryani & Momos Grand Feast (₹439)',
    'combo-burger-duo' => 'Combo: Double Smokehouse Burger Duo (₹549)',
];

function getAppProductsList() {
    global $APP_PRODUCTS_DEFAULT;
    try {
        $db = getDb();
        $stmt = $db->query("SELECT id, name, price FROM food_items WHERE status = 1 ORDER BY display_order ASC, id ASC");
        $items = $stmt->fetchAll();
        if (!empty($items)) {
            $list = [];
            foreach ($items as $item) {
                $list[$item['id']] = $item['name'] . ' (₹' . number_format($item['price'], 0) . ')';
            }
            return $list;
        }
    } catch (\Exception $e) {}
    return $APP_PRODUCTS_DEFAULT;
}

$APP_PRODUCTS = getAppProductsList();

// Popular food icons map for categories
$CATEGORY_ICONS = [
    'utensils'       => ['fa' => 'fa-solid fa-utensils', 'label' => 'Utensils (Biryani/Meals)'],
    'burger'         => ['fa' => 'fa-solid fa-burger', 'label' => 'Burger'],
    'french-fries'   => ['fa' => 'fa-solid fa-bowl-food', 'label' => 'Fries & Sides'],
    'bowl-food'      => ['fa' => 'fa-solid fa-box-open', 'label' => 'Wraps & Bowls'],
    'mug-hot'        => ['fa' => 'fa-solid fa-mug-hot', 'label' => 'Drinks & Shakes'],
    'cake-candles'   => ['fa' => 'fa-solid fa-cake-candles', 'label' => 'Desserts & Cakes'],
    'fire'           => ['fa' => 'fa-solid fa-fire', 'label' => 'Combos / Flame'],
    'pizza-slice'    => ['fa' => 'fa-solid fa-pizza-slice', 'label' => 'Pizza'],
    'ice-cream'      => ['fa' => 'fa-solid fa-ice-cream', 'label' => 'Ice Cream'],
    'drumstick-bite' => ['fa' => 'fa-solid fa-drumstick-bite', 'label' => 'Chicken Wing/Leg'],
    'tag'            => ['fa' => 'fa-solid fa-tag', 'label' => 'Special Deals / Tag']
];
