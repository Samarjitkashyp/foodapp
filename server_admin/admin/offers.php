<?php
require_once __DIR__ . '/config.php';

$db = getDb();
$error = '';
$msg = $_GET['msg'] ?? '';

// Handle Toggle Status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    try {
        $stmt = $db->prepare("UPDATE offers SET status = IF(status = 1, 0, 1) WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: offers.php?msg=updated');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch all offers
try {
    $stmt = $db->query("SELECT id, title, image_url, COALESCE(product_id, '') AS product_id, COALESCE(status, 1) AS status, display_order, created_at FROM offers ORDER BY display_order ASC, id DESC");
    $offers = $stmt->fetchAll();
} catch (Exception $e) {
    $offers = [];
    $error = 'Error loading offers: ' . $e->getMessage();
}

// Stats
$totalOffers = count($offers);
$activeOffers = array_filter($offers, fn($o) => (int)$o['status'] === 1);
$activeOfferCount = count($activeOffers);

try {
    $activeBanners = (int)$db->query("SELECT COUNT(*) FROM banners WHERE status = 1")->fetchColumn();
    $totalFood = (int)$db->query("SELECT COUNT(*) FROM food_items WHERE status = 1")->fetchColumn();
    $totalCats = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
} catch (Exception $e) {
    $activeBanners = 0;
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
  <title>Offers &amp; Deals Slider | Crisp &amp; Crave</title>
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
      flex-wrap: wrap;
      gap: 12px;
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

    .custom-table tbody td {
      padding: 14px 20px;
      vertical-align: middle;
      font-size: 0.9rem;
    }

    .banner-preview-box {
      width: 120px;
      height: 60px;
      background: #000;
      border: 1px solid #cbd5e1;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .banner-preview-box img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .order-box {
      display: inline-flex;
      width: 34px;
      height: 34px;
      align-items: center;
      justify-content: center;
      background: #f8fafc;
      border: 1px solid #cbd5e1;
      font-weight: 800;
      font-size: 13px;
      color: #0f172a;
    }

    .status-toggle-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 5px 12px;
      font-size: 11px;
      font-weight: 800;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      text-decoration: none;
      cursor: pointer;
      border: 1px solid transparent;
      transition: all 0.15s ease;
    }

    .status-toggle-btn.active {
      background: #ecfdf5;
      color: #047857;
      border: 1px solid #6ee7b7;
    }
    .status-toggle-btn.active:hover {
      background: #d1fae5;
    }

    .status-toggle-btn.inactive {
      background: #f8fafc;
      color: #64748b;
      border: 1px solid #cbd5e1;
    }
    .status-toggle-btn.inactive:hover {
      background: #f1f5f9;
    }

    .btn-sharp {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 14px;
      font-size: 12px;
      font-weight: 700;
      cursor: pointer;
      border: 1px solid transparent;
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
      color: #1e293b;
      border: 1px solid #cbd5e1;
    }
    .btn-sharp-edit:hover {
      background: #f1f5f9;
    }

    .btn-sharp-delete {
      background: #ffffff;
      color: #dc2626;
      border: 1px solid #fecaca;
    }
    .btn-sharp-delete:hover {
      background: #dc2626;
      color: #ffffff;
    }

    .sharp-alert {
      padding: 12px 18px;
      margin-bottom: 1rem;
      border-left: 4px solid;
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 0.9rem;
      background: #ffffff;
    }
    .sharp-alert-success {
      border-color: #10b981;
      background: #f0fdf4;
      color: #065f46;
    }
    .sharp-alert-info {
      border-color: #3b82f6;
      background: #eff6ff;
      color: #1e40af;
    }
    .sharp-alert-danger {
      border-color: #ef4444;
      background: #fef2f2;
      color: #991b1b;
    }
  </style>
</head>
<body class="app-body">
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
      <a href="offers.php" class="nav-link active">
        <i class="fa-solid fa-tags"></i>
        <span>Offers</span>
        <span class="nav-badge"><?= $activeOfferCount ?> Live</span>
      </a>
      <a href="coupons.php" class="nav-link">
        <i class="fa-solid fa-ticket"></i>
        <span>Coupons</span>
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
          <h1>Offers &amp; Deals Slider</h1>
          <p>Manage Offer Sliders for Crisp &amp; Crave Mobile App</p>
        </div>
      </div>

      <div class="topbar-center">
        <div class="live-indicator">
          <span class="live-dot"></span>
          <span>Flutter App Live Synced</span>
        </div>
      </div>

      <div class="topbar-actions">
        <a href="../api/offers.php" target="_blank" class="btn-sharp btn-sharp-edit">
          <i class="fa-solid fa-code"></i> Offers API
        </a>
        <a href="add_offer.php" class="btn-sharp btn-sharp-primary">
          <i class="fa-solid fa-plus"></i> Upload Offer Banner
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
            <div><strong>Success:</strong> Naya Offer Banner add ho gaya hai aur mobile app Offers slider me live aa chuka hai!</div>
          </div>
        <?php elseif ($msg === 'updated'): ?>
          <div class="sharp-alert sharp-alert-info">
            <i class="fa-solid fa-circle-info" style="color: #2563eb;"></i>
            <div><strong>Updated:</strong> Offer Banner update ho gaya hai.</div>
          </div>
        <?php elseif ($msg === 'deleted'): ?>
          <div class="sharp-alert sharp-alert-danger">
            <i class="fa-solid fa-trash" style="color: #dc2626;"></i>
            <div>Offer banner remove kar diya gaya hai.</div>
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
              Offers Slider
              <span class="header-tag"><?= $totalOffers ?> Total &bull; <?= $activeOfferCount ?> Active</span>
            </h3>
            <a href="add_offer.php" class="btn-sharp btn-sharp-primary">
              <i class="fa-solid fa-plus"></i> Add New Offer
            </a>
          </div>

          <div class="table-responsive">
            <table class="custom-table">
              <thead>
                <tr>
                  <th style="width: 70px;">Order</th>
                  <th style="width: 140px;">Banner</th>
                  <th>Offer Title</th>
                  <th>Opens in App on Tap</th>
                  <th style="width: 120px;">App Status</th>
                  <th style="width: 140px; text-align: right;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($offers)): ?>
                  <tr>
                    <td colspan="6" style="text-align: center; padding: 3rem; color: #64748b;">
                      <i class="fa-solid fa-tags" style="font-size: 2.5rem; color: #cbd5e1; margin-bottom: 12px; display: block;"></i>
                      Koi offer banner nahi mila. <a href="add_offer.php" style="color: #f97316; font-weight: 700;">Add New Offer</a> par click karein.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($offers as $offer): ?>
                    <tr>
                      <td>
                        <span class="order-box"><?= (int)$offer['display_order'] ?></span>
                      </td>
                      <td>
                        <div class="banner-preview-box">
                          <img src="<?= htmlspecialchars($offer['image_url']) ?>" alt="Offer Thumbnail" onerror="this.src='https://via.placeholder.com/200x100?text=Offer';">
                        </div>
                      </td>
                      <td>
                        <strong style="color: #0f172a; font-size: 0.95rem;"><?= htmlspecialchars($offer['title']) ?></strong>
                      </td>
                      <td>
                        <?php if (!empty($offer['product_id']) && isset($APP_PRODUCTS[$offer['product_id']])): ?>
                          <span style="font-size: 0.82rem; color: #f97316; font-weight: 700; background: #fff7ed; padding: 3px 8px; border: 1px solid #fed7aa;">
                            <i class="fa-solid fa-utensils"></i> <?= htmlspecialchars($APP_PRODUCTS[$offer['product_id']]) ?>
                          </span>
                        <?php elseif (!empty($offer['product_id'])): ?>
                          <span style="font-size: 0.82rem; color: #475569; font-family: monospace;">#<?= htmlspecialchars($offer['product_id']) ?></span>
                        <?php else: ?>
                          <span style="color: #94a3b8; font-size: 0.8rem;">None (View Only)</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if ((int)$offer['status'] === 1): ?>
                          <a href="offers.php?toggle=<?= $offer['id'] ?>" class="status-toggle-btn active" title="Click to Deactivate">
                            <i class="fa-solid fa-circle-check"></i> Active
                          </a>
                        <?php else: ?>
                          <a href="offers.php?toggle=<?= $offer['id'] ?>" class="status-toggle-btn inactive" title="Click to Activate">
                            <i class="fa-solid fa-circle-xmark"></i> Inactive
                          </a>
                        <?php endif; ?>
                      </td>
                      <td style="text-align: right;">
                        <div style="display: inline-flex; gap: 6px;">
                          <a href="edit_offer.php?id=<?= $offer['id'] ?>" class="btn-sharp btn-sharp-edit" title="Edit">
                            <i class="fa-solid fa-pen-to-square"></i> Edit
                          </a>
                          <a href="delete_offer.php?id=<?= $offer['id'] ?>" class="btn-sharp btn-sharp-delete" title="Delete" onclick="return confirm('Kya aap is offer banner ko delete karna chahte hain?');">
                            <i class="fa-solid fa-trash"></i>
                          </a>
                        </div>
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
