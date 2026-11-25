<?php
require_once __DIR__ . '/config.php';

$db = getDb();
$error = '';
$msg = $_GET['msg'] ?? '';

// Handle Toggle Status
if (isset($_GET['toggle']) && !empty($_GET['toggle'])) {
    $id = trim($_GET['toggle']);
    try {
        $stmt = $db->prepare("UPDATE food_items SET status = IF(status = 1, 0, 1) WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: food_items.php?msg=updated');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Counts for badges
try {
    $totalBanners = (int)$db->query("SELECT COUNT(*) FROM banners")->fetchColumn();
    $activeBanners = (int)$db->query("SELECT COUNT(*) FROM banners WHERE status = 1")->fetchColumn();
    
    $totalFood = (int)$db->query("SELECT COUNT(*) FROM food_items")->fetchColumn();
    $activeFood = (int)$db->query("SELECT COUNT(*) FROM food_items WHERE status = 1")->fetchColumn();

    $totalCats = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $activeCats = (int)$db->query("SELECT COUNT(*) FROM categories WHERE status = 1")->fetchColumn();
} catch (Exception $e) {
    $totalBanners = 0;
    $activeBanners = 0;
    $totalFood = 0;
    $activeFood = 0;
    $totalCats = 0;
    $activeCats = 0;
}

// Filters
$selectedCat = trim($_GET['category'] ?? '');
$search = trim($_GET['q'] ?? '');
$typeFilter = trim($_GET['type'] ?? ''); // veg, nonveg

// Build query
$where = [];
$params = [];

if ($selectedCat !== '' && $selectedCat !== 'all') {
    $where[] = "category = ?";
    $params[] = $selectedCat;
}
if ($typeFilter === 'veg') {
    $where[] = "is_veg = 1";
} elseif ($typeFilter === 'nonveg') {
    $where[] = "is_veg = 0";
}
if ($search !== '') {
    $where[] = "(name LIKE ? OR id LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
$query = "SELECT * FROM food_items $whereSql ORDER BY display_order ASC, id ASC";

try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $items = $stmt->fetchAll();
} catch (Exception $e) {
    $items = [];
    $error = 'Error loading food items: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Food Items Catalog - Crisp &amp; Crave Control Center</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* SHARP, FLAT & PREMIUM DATA GRID (NO BORDER-RADIUS) */
    *, *::before, *::after {
      border-radius: 0 !important;
    }

    body {
      background-color: #f8fafc;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    }

    .container-sharp {
      max-width: 1320px;
      margin: 0 auto;
    }

    /* Card without border-radius */
    .card {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
      margin-top: 0.5rem;
    }

    .card-header {
      padding: 16px 22px;
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

    /* Filter Toolbar */
    .filter-bar {
      padding: 12px 20px;
      background: #f8fafc;
      border-bottom: 1px solid #e2e8f0;
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .filter-select, .filter-input {
      padding: 8px 12px;
      border: 1px solid #cbd5e1;
      background: #fff;
      font-size: 0.85rem;
      color: #0f172a;
      outline: none;
    }
    .filter-select:focus, .filter-input:focus {
      border-color: #f97316;
    }

    /* Table with sharp, premium layout */
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

    /* Food Thumbnail */
    .food-row-thumb {
      width: 64px;
      height: 64px;
      object-fit: cover;
      border: 1px solid #e2e8f0;
      background: #f1f5f9;
      display: block;
      transition: border-color 0.15s ease;
    }
    .food-row-thumb:hover {
      border-color: #f97316;
    }

    /* Title & Meta Info */
    .food-title-text {
      font-size: 0.95rem;
      font-weight: 700;
      color: #0f172a;
      line-height: 1.3;
      margin-bottom: 4px;
    }

    .food-meta-row {
      display: flex;
      align-items: center;
      gap: 6px;
      flex-wrap: wrap;
    }

    .meta-id-tag {
      font-size: 0.72rem;
      font-weight: 700;
      background: #e2e8f0;
      color: #475569;
      padding: 2px 6px;
      font-family: monospace;
    }

    .meta-badge-tag {
      font-size: 0.72rem;
      font-weight: 700;
      background: #ffedd5;
      color: #c2410c;
      border: 1px solid #fed7aa;
      padding: 2px 6px;
    }

    .cat-badge {
      display: inline-block;
      font-size: 0.75rem;
      font-weight: 700;
      padding: 3px 8px;
      background: #f1f5f9;
      color: #334155;
      border: 1px solid #cbd5e1;
      text-transform: capitalize;
    }

    /* Veg & Non-Veg Badges */
    .type-badge-veg {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 0.78rem;
      font-weight: 700;
      color: #15803d;
      background: #f0fdf4;
      border: 1px solid #bbf7d0;
      padding: 3px 8px;
    }
    .type-box-veg {
      width: 12px;
      height: 12px;
      border: 2px solid #16a34a;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
    .type-box-veg::after {
      content: '';
      width: 6px;
      height: 6px;
      background: #16a34a;
      border-radius: 50% !important;
    }

    .type-badge-nonveg {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 0.78rem;
      font-weight: 700;
      color: #b91c1c;
      background: #fef2f2;
      border: 1px solid #fecaca;
      padding: 3px 8px;
    }
    .type-box-nonveg {
      width: 12px;
      height: 12px;
      border: 2px solid #dc2626;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
    .type-box-nonveg::after {
      content: '';
      width: 6px;
      height: 6px;
      background: #dc2626;
      border-radius: 50% !important;
    }

    /* Price display */
    .price-main {
      font-size: 1rem;
      font-weight: 800;
      color: #0f172a;
    }
    .price-strike {
      font-size: 0.8rem;
      color: #94a3b8;
      text-decoration: line-through;
      margin-left: 4px;
    }

    /* Status 1-Click Toggle Button */
    .status-toggle-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 5px 12px;
      font-size: 0.78rem;
      font-weight: 700;
      border: 1px solid transparent;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.15s ease;
    }
    .status-toggle-btn.active {
      background: #ecfdf5;
      color: #047857;
      border-color: #a7f3d0;
    }
    .status-toggle-btn.active:hover {
      background: #d1fae5;
      border-color: #6ee7b7;
    }
    .status-toggle-btn.inactive {
      background: #f8fafc;
      color: #64748b;
      border-color: #cbd5e1;
    }
    .status-toggle-btn.inactive:hover {
      background: #f1f5f9;
      color: #334155;
    }

    /* Sharp Action Buttons */
    .btn-sharp {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 7px 14px;
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
      color: #ffffff;
    }
    .btn-sharp-edit {
      background: #f8fafc;
      color: #1e293b;
      border-color: #cbd5e1;
    }
    .btn-sharp-edit:hover {
      background: #f1f5f9;
      border-color: #94a3b8;
      color: #0f172a;
    }
    .btn-sharp-delete {
      background: #fff;
      color: #dc2626;
      border-color: #fca5a5;
    }
    .btn-sharp-delete:hover {
      background: #fef2f2;
      border-color: #f87171;
    }

    /* Sharp Alerts */
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

  <!-- ======================================================================
       LEFT SIDEBAR (CLEAN & SHARP)
       ====================================================================== -->
  <aside class="app-sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">
        <i class="fa-solid fa-utensils"></i>
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
      </a>
      <a href="coupons.php" class="nav-link">
        <i class="fa-solid fa-ticket"></i>
        <span>Coupons</span>
      </a>
      <a href="food_items.php" class="nav-link active">
        <i class="fa-solid fa-bowl-food"></i>
        <span>Food Items</span>
        <span class="nav-badge"><?= $activeFood ?> Active</span>
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
        <div class="topbar-title">
          <h1>Food Items Catalog</h1>
          <p>Add &amp; Edit Dishes for Crisp &amp; Crave Mobile App</p>
        </div>
      </div>

      <div class="topbar-center">
        <div class="live-indicator">
          <span class="live-dot"></span>
          <span>Flutter App Live Synced</span>
        </div>
      </div>

      <div class="topbar-actions">
        <a href="banners.php" class="btn-sharp btn-sharp-edit">
          <i class="fa-solid fa-images"></i> Hero Slider
        </a>
        <a href="../api/products.php" target="_blank" class="btn-sharp btn-sharp-edit">
          <i class="fa-solid fa-code"></i> Products API
        </a>
        <a href="add_food_item.php" class="btn-sharp btn-sharp-primary">
          <i class="fa-solid fa-plus"></i> Add Food Item
        </a>
      </div>
    </header>

    <!-- Main Content Body -->
    <main class="app-content">
      <div class="container-sharp">

        <!-- Alerts -->
        <?php if ($msg === 'added'): ?>
          <div class="sharp-alert sharp-alert-success">
            <i class="fa-solid fa-circle-check" style="font-size: 1.1rem; color: #059669;"></i>
            <div><strong>Success:</strong> Naya Food Item add ho gaya hai aur mobile app catalog me live hai.</div>
          </div>
        <?php elseif ($msg === 'updated'): ?>
          <div class="sharp-alert sharp-alert-info">
            <i class="fa-solid fa-circle-info" style="font-size: 1.1rem; color: #2563eb;"></i>
            <div><strong>Updated:</strong> Food Item details update ho gayi hain.</div>
          </div>
        <?php elseif ($msg === 'deleted'): ?>
          <div class="sharp-alert sharp-alert-danger">
            <i class="fa-solid fa-trash" style="font-size: 1.1rem; color: #dc2626;"></i>
            <div>Food Item ko catalog se delete kar diya gaya hai.</div>
          </div>
        <?php endif; ?>

        <?php if ($error): ?>
          <div class="sharp-alert sharp-alert-danger">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <!-- Sharp & Clean Food Items Table Card -->
        <div class="card">
          <div class="card-header">
            <h3>
              <span style="display: inline-block; width: 4px; height: 18px; background: #f97316;"></span>
              Food Items
              <span class="header-tag"><?= $totalFood ?> Total &bull; <?= $activeFood ?> Active</span>
            </h3>
            <a href="add_food_item.php" class="btn-sharp btn-sharp-primary">
              <i class="fa-solid fa-plus"></i> Add Food Item
            </a>
          </div>

          <!-- Quick Filters Bar -->
          <form method="GET" action="food_items.php" class="filter-bar">
            <input type="text" name="q" class="filter-input" placeholder="Search dish name or ID..." value="<?= htmlspecialchars($search) ?>" style="min-width: 200px;">
            
            <select name="category" class="filter-select" onchange="this.form.submit()">
              <option value="all">All Categories</option>
              <?php foreach ($FOOD_CATEGORIES as $ckey => $cname): ?>
                <option value="<?= $ckey ?>" <?= $selectedCat === $ckey ? 'selected' : '' ?>><?= htmlspecialchars($cname) ?></option>
              <?php endforeach; ?>
            </select>

            <select name="type" class="filter-select" onchange="this.form.submit()">
              <option value="">All Types (Veg &amp; Non-Veg)</option>
              <option value="veg" <?= $typeFilter === 'veg' ? 'selected' : '' ?>>Pure Veg Only</option>
              <option value="nonveg" <?= $typeFilter === 'nonveg' ? 'selected' : '' ?>>Non-Veg Only</option>
            </select>

            <button type="submit" class="btn-sharp btn-sharp-edit"><i class="fa-solid fa-magnifying-glass"></i> Filter</button>
            <?php if ($search !== '' || ($selectedCat !== '' && $selectedCat !== 'all') || $typeFilter !== ''): ?>
              <a href="food_items.php" class="btn-sharp btn-sharp-delete" style="text-decoration: none;">Clear</a>
            <?php endif; ?>
          </form>

          <div class="table-responsive">
            <table class="custom-table">
              <thead>
                <tr>
                  <th style="width: 80px;">Graphic</th>
                  <th>Dish Name &amp; Code</th>
                  <th style="width: 140px;">Category</th>
                  <th style="width: 110px;">Price</th>
                  <th style="width: 120px; text-align: center;">Type</th>
                  <th style="width: 130px; text-align: center;">Status</th>
                  <th style="width: 160px; text-align: right;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($items)): ?>
                  <tr>
                    <td colspan="7" style="text-align: center; padding: 3.5rem; color: #64748b;">
                      <i class="fa-solid fa-utensils" style="font-size: 2.5rem; opacity: 0.25; margin-bottom: 0.75rem; display: block;"></i>
                      Koi food item nahi mila. Naya item add karne ke liye upar diye <strong>"Add Food Item"</strong> button par click karein.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($items as $item): ?>
                    <?php 
                      $isActive = (int)$item['status'] === 1;
                      $isVeg = (int)$item['is_veg'] === 1;
                      $imgUrl = getFoodImageUrl($item['image']);
                      $catName = $FOOD_CATEGORIES[$item['category']] ?? ucfirst($item['category']);
                    ?>
                    <tr>
                      <!-- Thumbnail -->
                      <td>
                        <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="food-row-thumb" loading="lazy" onerror="this.src='https://via.placeholder.com/80x80?text=Food';">
                      </td>

                      <!-- Name & Meta -->
                      <td>
                        <div class="food-title-text">
                          <?= htmlspecialchars($item['name']) ?>
                        </div>
                        <div class="food-meta-row">
                          <span class="meta-id-tag">#<?= htmlspecialchars($item['id']) ?></span>
                          <?php if (!empty($item['portion'])): ?>
                            <span class="meta-badge-tag" style="background: #ffedd5; color: #ea580c; border-color: #fdba74;"><i class="fa-solid fa-utensils"></i> <?= htmlspecialchars($item['portion']) ?></span>
                          <?php endif; ?>
                          <?php if (!empty($item['badge'])): ?>
                            <span class="meta-badge-tag"><?= htmlspecialchars($item['badge']) ?></span>
                          <?php endif; ?>
                          <?php if (!empty($item['prep_time'])): ?>
                            <span style="font-size: 0.72rem; color: #64748b;"><i class="fa-regular fa-clock"></i> <?= htmlspecialchars($item['prep_time']) ?></span>
                          <?php endif; ?>
                        </div>
                      </td>

                      <!-- Category -->
                      <td>
                        <span class="cat-badge"><?= htmlspecialchars($catName) ?></span>
                      </td>

                      <!-- Price -->
                      <td>
                        <span class="price-main">₹<?= number_format($item['price'], 0) ?></span>
                        <?php if (!empty($item['original_price']) && (float)$item['original_price'] > (float)$item['price']): ?>
                          <span class="price-strike">₹<?= number_format($item['original_price'], 0) ?></span>
                        <?php endif; ?>
                      </td>

                      <!-- Veg / Non-Veg -->
                      <td style="text-align: center;">
                        <?php if ($isVeg): ?>
                          <span class="type-badge-veg" title="Vegetarian">
                            <span class="type-box-veg"></span> Veg
                          </span>
                        <?php else: ?>
                          <span class="type-badge-nonveg" title="Non-Vegetarian">
                            <span class="type-box-nonveg"></span> Non-Veg
                          </span>
                        <?php endif; ?>
                      </td>

                      <!-- 1-Click Status Toggle -->
                      <td style="text-align: center;">
                        <a href="food_items.php?toggle=<?= urlencode($item['id']) ?>" class="status-toggle-btn <?= $isActive ? 'active' : 'inactive' ?>" title="Click to toggle status">
                          <i class="fa-solid <?= $isActive ? 'fa-circle-check' : 'fa-circle-pause' ?>"></i>
                          <span><?= $isActive ? 'Active' : 'Inactive' ?></span>
                        </a>
                      </td>

                      <!-- Sharp Actions -->
                      <td style="text-align: right;">
                        <div style="display: inline-flex; align-items: center; gap: 8px;">
                          <a href="edit_food_item.php?id=<?= urlencode($item['id']) ?>" class="btn-sharp btn-sharp-edit" title="Edit Food Item">
                            <i class="fa-solid fa-pen"></i> Edit
                          </a>
                          <a href="delete_food_item.php?id=<?= urlencode($item['id']) ?>" class="btn-sharp btn-sharp-delete" title="Delete Food Item" onclick="return confirm('Kya aap sach me \'<?= addslashes(htmlspecialchars($item['name'])) ?>\' ko delete karna chahte hain?');">
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
