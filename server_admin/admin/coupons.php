<?php
require_once __DIR__ . '/config.php';

$db = getDb();
$error = '';
$msg = $_GET['msg'] ?? '';

// Handle Toggle Status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    try {
        $stmt = $db->prepare("UPDATE coupons SET status = IF(status = 1, 0, 1) WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: coupons.php?msg=updated');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch all coupons
try {
    $stmt = $db->query("SELECT id, code, title, COALESCE(description, '') AS description, discount_type, discount_value, max_discount, min_order, display_order, COALESCE(status, 1) AS status, created_at FROM coupons ORDER BY display_order ASC, id DESC");
    $coupons = $stmt->fetchAll();
} catch (Exception $e) {
    $coupons = [];
    $error = 'Error loading coupons: ' . $e->getMessage();
}

// Stats
$totalCoupons = count($coupons);
$activeCoupons = array_filter($coupons, fn($c) => (int)$c['status'] === 1);
$activeCouponCount = count($activeCoupons);

try {
    $activeBanners = (int)$db->query("SELECT COUNT(*) FROM banners WHERE status = 1")->fetchColumn();
    $activeOffers = (int)$db->query("SELECT COUNT(*) FROM offers WHERE status = 1")->fetchColumn();
    $totalFood = (int)$db->query("SELECT COUNT(*) FROM food_items WHERE status = 1")->fetchColumn();
    $totalCats = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
} catch (Exception $e) {
    $activeBanners = 0;
    $activeOffers = 0;
    $totalFood = count($APP_PRODUCTS);
    $totalCats = 7;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <base href="/admin/">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Discount Coupons | Crisp &amp; Crave Control Center</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="/admin/style.css">
  <style>
<?php if (file_exists(__DIR__ . '/style.css')) { include __DIR__ . '/style.css'; } ?>

    *, *::before, *::after {
      border-radius: 0 !important;
    }

    body {
      background-color: #f8fafc;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    }

    .hero-manager-container {
      max-width: 1280px;
      margin: 0 auto;
    }

    .card {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
      margin-top: 0.5rem;
    }

    .card-header {
      padding: 18px 24px;
      background: #ffffff;
      border-bottom: 1px solid #e2e8f0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .card-header h3 {
      font-size: 1.15rem;
      font-weight: 800;
      color: #0f172a;
      display: flex;
      align-items: center;
      gap: 10px;
      letter-spacing: -0.01em;
      margin: 0;
    }

    .header-tag {
      font-size: 0.75rem;
      font-weight: 700;
      background: #f1f5f9;
      color: #475569;
      padding: 3px 8px;
      border: 1px solid #cbd5e1;
      margin-left: 6px;
    }

    .table-responsive {
      overflow-x: auto;
    }

    .custom-table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
    }

    .custom-table thead th {
      background: #f8fafc;
      color: #475569;
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      padding: 14px 20px;
      border-top: 1px solid #e2e8f0;
      border-bottom: 2px solid #e2e8f0;
    }

    .custom-table tbody tr {
      border-bottom: 1px solid #f1f5f9;
      background: #ffffff;
      transition: background-color 0.15s ease;
    }

    .custom-table tbody tr:hover {
      background-color: #fafbfd;
    }

    .custom-table td {
      padding: 14px 20px;
      vertical-align: middle;
      font-size: 0.9rem;
      color: #334155;
    }

    .coupon-code-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 5px 12px;
      background: #fff7ed;
      color: #c2410c;
      border: 1px dashed #f97316;
      font-family: monospace;
      font-size: 0.95rem;
      font-weight: 800;
      letter-spacing: 0.05em;
    }

    .order-box {
      display: inline-block;
      width: 28px;
      height: 28px;
      line-height: 28px;
      text-align: center;
      background: #f1f5f9;
      font-weight: 800;
      font-size: 0.8rem;
      color: #475569;
      border: 1px solid #cbd5e1;
    }

    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 10px;
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .status-active {
      background: #ecfdf5;
      color: #047857;
      border: 1px solid #a7f3d0;
    }

    .status-inactive {
      background: #fef2f2;
      color: #b91c1c;
      border: 1px solid #fecaca;
    }

    .btn-sharp {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 14px;
      font-size: 0.82rem;
      font-weight: 700;
      border: 1px solid transparent;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.15s ease;
    }

    .btn-sharp-primary {
      background: #f97316;
      color: #ffffff;
      border-color: #ea580c;
    }
    .btn-sharp-primary:hover {
      background: #ea580c;
    }

    .btn-sharp-edit {
      background: #f8fafc;
      color: #334155;
      border-color: #cbd5e1;
    }
    .btn-sharp-edit:hover {
      background: #f1f5f9;
      color: #0f172a;
    }

    .btn-sharp-danger {
      background: #fef2f2;
      color: #b91c1c;
      border-color: #fca5a5;
    }
    .btn-sharp-danger:hover {
      background: #fee2e2;
    }

    .sharp-alert {
      padding: 14px 20px;
      margin-bottom: 20px;
      border: 1px solid transparent;
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 0.9rem;
    }
    .sharp-alert-success {
      background: #f0fdf4;
      color: #166534;
      border-color: #bbf7d0;
    }
    .sharp-alert-info {
      background: #eff6ff;
      color: #1e40af;
      border-color: #bfdbfe;
    }
    .sharp-alert-danger {
      background: #fef2f2;
      color: #991b1b;
      border-color: #fecaca;
    }
  </style>
</head>
<body>

<div class="app-container">

  <!-- LEFT SIDEBAR -->
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
      <a href="index.php" class="nav-link">
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
        <span class="nav-badge"><?= $activeBanners ?> Live</span>
      </a>
      <a href="offers.php" class="nav-link">
        <i class="fa-solid fa-tags"></i>
        <span>Offers</span>
        <span class="nav-badge"><?= $activeOffers ?> Live</span>
      </a>
      <a href="coupons.php" class="nav-link active">
        <i class="fa-solid fa-ticket"></i>
        <span>Coupons</span>
        <span class="nav-badge"><?= $activeCouponCount ?> Live</span>
      </a>
      <a href="food_items.php" class="nav-link">
        <i class="fa-solid fa-bowl-food"></i>
        <span>Food Items</span>
        <span class="nav-badge"><?= $totalFood ?></span>
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

  <!-- MAIN CONTENT CONTAINER -->
  <div class="app-main">
    
    <!-- Topbar -->
    <header class="app-topbar">
      <div class="topbar-left">
        <div class="topbar-title">
          <h1>Coupons &amp; Promo Codes</h1>
          <p>Manage dynamic discount promo codes for Crisp &amp; Crave Mobile App</p>
        </div>
      </div>

      <div class="topbar-center">
        <div class="live-indicator">
          <span class="live-dot"></span>
          <span>Flutter App Live Synced</span>
        </div>
      </div>

      <div class="topbar-actions">
        <a href="../api/coupons.php" target="_blank" class="btn-sharp btn-sharp-edit">
          <i class="fa-solid fa-code"></i> Coupons API
        </a>
        <a href="add_coupon.php" class="btn-sharp btn-sharp-primary">
          <i class="fa-solid fa-plus"></i> Add New Coupon
        </a>
      </div>
    </header>

    <!-- Main Content Body -->
    <main class="app-content">
      <div class="hero-manager-container">

        <!-- Alerts -->
        <?php if ($msg === 'added'): ?>
          <div class="sharp-alert sharp-alert-success">
            <i class="fa-solid fa-circle-check" style="color: #059669;"></i>
            <div><strong>Success:</strong> Naya coupon add ho gaya hai aur mobile app Deals &amp; Cart screen par live aa chuka hai!</div>
          </div>
        <?php elseif ($msg === 'updated'): ?>
          <div class="sharp-alert sharp-alert-info">
            <i class="fa-solid fa-circle-info" style="color: #2563eb;"></i>
            <div><strong>Updated:</strong> Coupon details update ho gayi hain.</div>
          </div>
        <?php elseif ($msg === 'deleted'): ?>
          <div class="sharp-alert sharp-alert-danger">
            <i class="fa-solid fa-trash" style="color: #dc2626;"></i>
            <div>Coupon delete kar diya gaya hai.</div>
          </div>
        <?php endif; ?>

        <?php if ($error): ?>
          <div class="sharp-alert sharp-alert-danger">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <!-- Table Card -->
        <div class="card">
          <div class="card-header">
            <h3>
              <span style="display: inline-block; width: 4px; height: 18px; background: #f97316;"></span>
              Active Promo Coupons
              <span class="header-tag"><?= $totalCoupons ?> Total &bull; <?= $activeCouponCount ?> Active</span>
            </h3>
            <a href="add_coupon.php" class="btn-sharp btn-sharp-primary">
              <i class="fa-solid fa-plus"></i> Add Coupon
            </a>
          </div>

          <div class="table-responsive">
            <table class="custom-table">
              <thead>
                <tr>
                  <th style="width: 60px;">Order</th>
                  <th style="width: 140px;">Code</th>
                  <th>Title &amp; Description</th>
                  <th style="width: 150px;">Discount Benefit</th>
                  <th style="width: 130px;">Min. Order</th>
                  <th style="width: 120px;">App Status</th>
                  <th style="width: 150px; text-align: right;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($coupons)): ?>
                  <tr>
                    <td colspan="7" style="text-align: center; padding: 3rem; color: #64748b;">
                      <i class="fa-solid fa-ticket" style="font-size: 2.5rem; color: #cbd5e1; margin-bottom: 12px; display: block;"></i>
                      Koi coupon nahi mila. <a href="add_coupon.php" style="color: #f97316; font-weight: 700;">Add New Coupon</a> par click karein.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($coupons as $coupon): ?>
                    <tr>
                      <td>
                        <span class="order-box"><?= (int)$coupon['display_order'] ?></span>
                      </td>
                      <td>
                        <span class="coupon-code-pill">
                          <i class="fa-solid fa-tag"></i>
                          <?= htmlspecialchars($coupon['code']) ?>
                        </span>
                      </td>
                      <td>
                        <strong style="color: #0f172a; font-size: 0.95rem; display: block;"><?= htmlspecialchars($coupon['title']) ?></strong>
                        <small style="color: #64748b; font-size: 0.8rem;"><?= htmlspecialchars($coupon['description']) ?></small>
                      </td>
                      <td>
                        <?php if ($coupon['discount_type'] === 'percentage'): ?>
                          <span style="color: #16a34a; font-weight: 800; font-size: 0.95rem;">
                            <?= (int)$coupon['discount_value'] ?>% OFF
                          </span>
                          <?php if (!empty($coupon['max_discount'])): ?>
                            <div style="font-size: 0.78rem; color: #64748b;">Max ₹<?= (int)$coupon['max_discount'] ?></div>
                          <?php endif; ?>
                        <?php else: ?>
                          <span style="color: #16a34a; font-weight: 800; font-size: 0.95rem;">
                            Flat ₹<?= (int)$coupon['discount_value'] ?> OFF
                          </span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <strong style="color: #334155;">₹<?= (int)$coupon['min_order'] ?></strong>
                        <div style="font-size: 0.75rem; color: #94a3b8;">minimum cart</div>
                      </td>
                      <td>
                        <a href="coupons.php?toggle=<?= $coupon['id'] ?>" title="Click to toggle status" style="text-decoration: none;">
                          <?php if ((int)$coupon['status'] === 1): ?>
                            <span class="status-badge status-active">
                              <i class="fa-solid fa-circle-check"></i> Active
                            </span>
                          <?php else: ?>
                            <span class="status-badge status-inactive">
                              <i class="fa-solid fa-circle-xmark"></i> Inactive
                            </span>
                          <?php endif; ?>
                        </a>
                      </td>
                      <td style="text-align: right;">
                        <a href="edit_coupon.php?id=<?= $coupon['id'] ?>" class="btn-sharp btn-sharp-edit" title="Edit Coupon">
                          <i class="fa-solid fa-pen"></i> Edit
                        </a>
                        <a href="delete_coupon.php?id=<?= $coupon['id'] ?>" class="btn-sharp btn-sharp-danger" onclick="return confirm('Kya aap coupon <?= htmlspecialchars($coupon['code']) ?> delete karna chahte hain?');" title="Delete Coupon">
                          <i class="fa-solid fa-trash"></i>
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </main>

  </div>
</div>

</body>
</html>
