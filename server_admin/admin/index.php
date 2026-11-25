<?php
require_once __DIR__ . '/config.php';

$db = getDb();
$msg = $_GET['msg'] ?? '';

// Fetch active and total banners count
try {
    $cols = $db->query("SHOW COLUMNS FROM banners")->fetchAll(PDO::FETCH_COLUMN);
    $prodExpr = in_array('product_id', $cols) ? "COALESCE(product_id, '')" : (in_array('target_product_id', $cols) ? "COALESCE(target_product_id, '')" : "''");
    $statusExpr = in_array('status', $cols) ? "COALESCE(status, 1)" : (in_array('is_active', $cols) ? "COALESCE(is_active, 1)" : "1");

    $query = "SELECT id, title, image_url, $prodExpr AS product_id, $statusExpr AS status, display_order FROM banners ORDER BY display_order ASC, id DESC";
    $stmt = $db->query($query);
    $banners = $stmt->fetchAll();
} catch (Exception $e) {
    $banners = [];
}

$totalBanners = count($banners);
$activeBanners = array_filter($banners, fn($b) => (int)$b['status'] === 1);
$activeCount = count($activeBanners);
$catalogCount = count($APP_PRODUCTS);
try {
    $totalCats = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
} catch (Exception $e) {
    $totalCats = count($FOOD_CATEGORIES);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <base href="/admin/">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Overview | Crisp &amp; Crave App Control</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
<?php if (file_exists(__DIR__ . '/style.css')) { include __DIR__ . '/style.css'; } ?>
    .dashboard-hero {
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
      color: #fff;
      border-radius: var(--radius-lg);
      padding: 2.25rem 2.5rem;
      margin-bottom: 2rem;
      position: relative;
      overflow: hidden;
      border: 1px solid rgba(255, 255, 255, 0.08);
      box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.2);
    }
    .dashboard-hero::after {
      content: '';
      position: absolute;
      top: -50px;
      right: -50px;
      width: 250px;
      height: 250px;
      background: radial-gradient(circle, rgba(255, 87, 34, 0.25) 0%, transparent 70%);
      border-radius: 50%;
      pointer-events: none;
    }
    .hero-content h2 {
      font-size: 1.6rem;
      font-weight: 800;
      margin-bottom: 0.5rem;
    }
    .hero-content h2 span {
      color: var(--primary);
    }
    .hero-content p {
      color: #94a3b8;
      font-size: 0.95rem;
      max-width: 650px;
      margin-bottom: 1.5rem;
      line-height: 1.6;
    }
    .hero-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
    }
    .clean-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2rem;
      margin-top: 1rem;
    }
    @media (max-width: 900px) {
      .clean-grid { grid-template-columns: 1fr; }
    }
    .mini-banner-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .mini-banner-item {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 10px 14px;
      border-radius: var(--radius-md);
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      transition: var(--transition);
    }
    .mini-banner-item:hover {
      background: #fff;
      border-color: #cbd5e1;
      transform: translateX(4px);
      box-shadow: var(--shadow-sm);
    }
    .mini-banner-thumb {
      width: 80px;
      height: 45px;
      border-radius: 6px;
      object-fit: cover;
      background: #cbd5e1;
      border: 1px solid #e2e8f0;
    }
    .mini-banner-info {
      flex: 1;
      min-width: 0;
    }
    .mini-banner-info h5 {
      font-size: 0.9rem;
      font-weight: 700;
      color: var(--text-main);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      margin-bottom: 2px;
    }
    .mini-banner-info span {
      font-size: 0.75rem;
      color: var(--primary);
      font-weight: 600;
    }
    .info-row {
      display: flex;
      justify-content: space-between;
      padding: 10px 0;
      border-bottom: 1px solid #f1f5f9;
      font-size: 0.88rem;
    }
    .info-row:last-child {
      border-bottom: none;
    }
    .info-label {
      color: var(--text-muted);
      font-weight: 500;
    }
    .info-value {
      font-weight: 700;
      color: var(--text-main);
    }
  </style>
  <link rel="stylesheet" href="/admin/style.css">
</head>
<body>

  <!-- ======================================================================
       LEFT SIDEBAR NAVIGATION
       ====================================================================== -->
  <aside class="app-sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo-icon">
        <i class="fa-solid fa-burger"></i>
      </div>
      <div class="sidebar-brand">
        <h2>Crisp<span>&</span>Crave</h2>
        <p>App Control Center v2.0</p>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section-title">App Management</div>
      <a href="index.php" class="nav-link active">
        <i class="fa-solid fa-gauge-high"></i>
        <span>Dashboard</span>
      </a>
      <a href="orders.php" class="nav-link">
        <i class="fa-solid fa-bag-shopping"></i>
        <span>Live Orders</span>
      </a>
      <a href="users.php" class="nav-link">
        <i class="fa-solid fa-users"></i>
        <span>Customers</span>
      </a>
      <a href="banners.php" class="nav-link">
        <i class="fa-solid fa-images"></i>
        <span>Hero Slider</span>
        <span class="nav-badge"><?= $activeCount ?> Live</span>
      </a>
      <a href="offers.php" class="nav-link">
        <i class="fa-solid fa-tags"></i>
        <span>Offers</span>
      </a>
      <a href="coupons.php" class="nav-link">
        <i class="fa-solid fa-ticket"></i>
        <span>Coupons</span>
      </a>
      <a href="food_items.php" class="nav-link">
        <i class="fa-solid fa-bowl-food"></i>
        <span>Food Items</span>
        <span class="nav-badge"><?= $catalogCount ?></span>
      </a>
      <a href="categories.php" class="nav-link">
        <i class="fa-solid fa-layer-group"></i>
        <span>Food Categories</span>
        <span class="nav-badge"><?= $totalCats ?></span>
      </a>
    </nav>

    <div class="sidebar-footer">
      <div class="admin-avatar">AD</div>
      <div class="admin-info">
        <div class="name">Administrator</div>
        <div class="role"><span class="pulse-dot"></span> Server Online</div>
      </div>
    </div>
  </aside>

  <!-- ======================================================================
       MAIN CONTENT CONTAINER
       ====================================================================== -->
  <div class="app-main">
    
    <!-- Topbar -->
    <header class="app-topbar">
      <div class="topbar-left">
        <h1 class="topbar-title">Dashboard Overview</h1>
        <div class="status-pill">
          <span class="pulse-dot"></span>
          <span>Fast Food Mobile Control</span>
        </div>
      </div>

      <div class="topbar-actions">
        <a href="banners.php" class="btn btn-primary btn-sm">
          <i class="fa-solid fa-images"></i> Manage Hero Sliders
        </a>
        <a href="../api/banners.php" target="_blank" class="btn btn-secondary btn-sm">
          <i class="fa-solid fa-code"></i> API JSON
        </a>
      </div>
    </header>

    <!-- Main Content Area -->
    <main class="app-content">

      <!-- Hero Welcome Card -->
      <div class="dashboard-hero">
        <div class="hero-content">
          <h2>Welcome to <span>Crisp &amp; Crave</span> Mobile Control!</h2>
          <p>
            Aapka mobile app dynamic control portal active hai. Canva hero sliders manage karne ke liye left sidebar me <strong>"Hero Slider"</strong> par click karein.
          </p>
          <div class="hero-actions">
            <a href="banners.php" class="btn btn-primary">
              <i class="fa-solid fa-images"></i> Open Hero Slider Manager (<?= $activeCount ?> Live)
            </a>
            <a href="food_items.php" class="btn btn-secondary" style="background: rgba(255,255,255,0.1); color: #fff; border-color: rgba(255,255,255,0.2);">
              <i class="fa-solid fa-bowl-food"></i> Manage Food Items (<?= $catalogCount ?> Dishes)
            </a>
          </div>
        </div>
      </div>

      <!-- 4 KPI Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon orange">
            <i class="fa-solid fa-images"></i>
          </div>
          <div class="stat-details">
            <div class="label">Live Hero Sliders</div>
            <div class="value"><?= $activeCount ?></div>
            <div class="sub"><?= $totalBanners ?> total banners created</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon blue">
            <i class="fa-solid fa-utensils"></i>
          </div>
          <div class="stat-details">
            <div class="label">Catalog Products</div>
            <div class="value"><?= $catalogCount ?></div>
            <div class="sub">Burgers, Momos, Biryani, Rolls</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon green">
            <i class="fa-solid fa-mobile-screen-button"></i>
          </div>
          <div class="stat-details">
            <div class="label">Flutter App Status</div>
            <div class="value" style="font-size: 1.3rem; color: var(--success);">Live Synced</div>
            <div class="sub">Instant banner update on save</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon purple">
            <i class="fa-solid fa-database"></i>
          </div>
          <div class="stat-details">
            <div class="label">Database Health</div>
            <div class="value" style="font-size: 1.3rem; color: #8b5cf6;">Connected</div>
            <div class="sub">damdama_food @ MySQL 8.0</div>
          </div>
        </div>
      </div>

      <!-- Clean 2-Column Split: Active Sliders Preview + Guidelines -->
      <div class="clean-grid">
        
        <!-- Column 1: Active Sliders Mini Preview -->
        <div class="card">
          <div class="card-header">
            <h3><i class="fa-solid fa-sliders" style="color: var(--primary);"></i> Active App Sliders</h3>
            <a href="banners.php" class="btn btn-secondary btn-sm">Manage All</a>
          </div>
          <div class="card-body">
            <?php if (empty($activeBanners)): ?>
              <p style="color: var(--text-muted); font-size: 0.9rem;">Koi active slider nahi hai.</p>
            <?php else: ?>
              <div class="mini-banner-list">
                <?php foreach (array_slice($activeBanners, 0, 5) as $b): ?>
                  <div class="mini-banner-item">
                    <img src="<?= htmlspecialchars($b['image_url']) ?>" alt="Banner" class="mini-banner-thumb">
                    <div class="mini-banner-info">
                      <h5><?= htmlspecialchars($b['title']) ?></h5>
                      <span><i class="fa-solid fa-utensils"></i> <?= htmlspecialchars($APP_PRODUCTS[$b['product_id']] ?? $b['product_id']) ?></span>
                    </div>
                    <span class="badge-status active">Live</span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Column 2: Canva Rules & Specifications -->
        <div class="card">
          <div class="card-header">
            <h3><i class="fa-solid fa-circle-info" style="color: #3b82f6;"></i> Canva Design Specifications</h3>
            <span class="canva-badge">Canva Ready</span>
          </div>
          <div class="card-body">
            <div class="info-row">
              <span class="info-label">Supported Formats:</span>
              <span class="info-value" style="color: var(--success);"><i class="fa-solid fa-check"></i> JPG aur PNG only</span>
            </div>
            <div class="info-row">
              <span class="info-label">Maximum File Size:</span>
              <span class="info-value" style="color: var(--success);"><i class="fa-solid fa-check"></i> 3 MB per image</span>
            </div>
            <div class="info-row">
              <span class="info-label">Recommended Resolution:</span>
              <span class="info-value" style="color: var(--primary);">1200 &times; 600 px (2:1 aspect ratio)</span>
            </div>
            <div class="info-row">
              <span class="info-label">Dish Tap Action:</span>
              <span class="info-value">Auto-navigates to tapped item</span>
            </div>

            <div style="margin-top: 1.25rem;">
              <a href="banners.php" class="btn btn-primary" style="width: 100%;">
                <i class="fa-solid fa-cloud-arrow-up"></i> Go to Hero Slider Manager
              </a>
            </div>
          </div>
        </div>

      </div>

    </main>
  </div>

</body>
</html>
