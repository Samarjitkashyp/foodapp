<?php
require_once __DIR__ . '/config.php';

$db = getDb();
$error = $_GET['error'] ?? '';
$msg = $_GET['msg'] ?? '';

// Handle Toggle Status
if (isset($_GET['toggle']) && !empty($_GET['toggle'])) {
    $id = trim($_GET['toggle']);
    try {
        $stmt = $db->prepare("UPDATE categories SET status = IF(status = 1, 0, 1) WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: categories.php?msg=updated');
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

// Fetch categories with dish counts
try {
    $query = "SELECT c.*, COUNT(f.id) AS dish_count 
              FROM categories c 
              LEFT JOIN food_items f ON f.category = c.id 
              GROUP BY c.id 
              ORDER BY c.display_order ASC, c.name ASC";
    $stmt = $db->query($query);
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    $categories = [];
    $error = 'Error loading categories: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Food Categories - Crisp &amp; Crave Control Center</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    *, *::before, *::after {
      border-radius: 0 !important;
    }

    body {
      background-color: #f8fafc;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    }

    .container-sharp {
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

    .cat-icon-box {
      width: 44px;
      height: 44px;
      background: #fff7ed;
      color: #f97316;
      border: 1px solid #fed7aa;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
    }

    .order-box {
      display: inline-block;
      width: 32px;
      height: 32px;
      line-height: 30px;
      text-align: center;
      font-weight: 800;
      font-size: 0.85rem;
      background: #f8fafc;
      color: #334155;
      border: 1px solid #cbd5e1;
    }

    .meta-id-tag {
      font-size: 0.75rem;
      font-weight: 700;
      background: #e2e8f0;
      color: #475569;
      padding: 2px 6px;
      font-family: monospace;
      margin-left: 8px;
    }

    .dish-count-tag {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 0.78rem;
      font-weight: 700;
      padding: 3px 10px;
      background: #eff6ff;
      color: #1d4ed8;
      border: 1px solid #bfdbfe;
    }

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
    }
    .status-toggle-btn.inactive {
      background: #f8fafc;
      color: #64748b;
      border-color: #cbd5e1;
    }
    .status-toggle-btn.inactive:hover {
      background: #f1f5f9;
    }

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
      color: #0f172a;
    }
    .btn-sharp-delete {
      background: #fff;
      color: #dc2626;
      border-color: #fca5a5;
    }
    .btn-sharp-delete:hover {
      background: #fef2f2;
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
      <a href="food_items.php" class="nav-link">
        <i class="fa-solid fa-bowl-food"></i>
        <span>Food Items</span>
        <span class="nav-badge"><?= $activeFood ?> Active</span>
      </a>
      <a href="categories.php" class="nav-link active">
        <i class="fa-solid fa-layer-group"></i>
        <span>Food Categories</span>
        <span class="nav-badge"><?= $activeCats ?></span>
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
          <h1>Food Categories</h1>
          <p>Manage Menu Categories &amp; Flutter App Chips</p>
        </div>
      </div>

      <div class="topbar-center">
        <div class="live-indicator">
          <span class="live-dot"></span>
          <span>Flutter App Live Synced</span>
        </div>
      </div>

      <div class="topbar-actions">
        <a href="food_items.php" class="btn-sharp btn-sharp-edit">
          <i class="fa-solid fa-bowl-food"></i> Food Items
        </a>
        <a href="../api/categories.php" target="_blank" class="btn-sharp btn-sharp-edit">
          <i class="fa-solid fa-code"></i> Categories API
        </a>
        <a href="add_category.php" class="btn-sharp btn-sharp-primary">
          <i class="fa-solid fa-plus"></i> Add New Category
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
            <div><strong>Success:</strong> Nayi Category add ho gayi hai aur mobile app chips me turant link ho gayi hai.</div>
          </div>
        <?php elseif ($msg === 'updated'): ?>
          <div class="sharp-alert sharp-alert-info">
            <i class="fa-solid fa-circle-info" style="font-size: 1.1rem; color: #2563eb;"></i>
            <div><strong>Updated:</strong> Category details successfully update ho gayi hain.</div>
          </div>
        <?php elseif ($msg === 'deleted'): ?>
          <div class="sharp-alert sharp-alert-danger">
            <i class="fa-solid fa-trash" style="font-size: 1.1rem; color: #dc2626;"></i>
            <div>Category remove kar di gayi hai.</div>
          </div>
        <?php endif; ?>

        <?php if ($error): ?>
          <div class="sharp-alert sharp-alert-danger">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <!-- Categories Table Card -->
        <div class="card">
          <div class="card-header">
            <h3>
              <span style="display: inline-block; width: 4px; height: 18px; background: #f97316;"></span>
              Food Categories
              <span class="header-tag"><?= $totalCats ?> Total &bull; <?= $activeCats ?> Active</span>
            </h3>
            <a href="add_category.php" class="btn-sharp btn-sharp-primary">
              <i class="fa-solid fa-plus"></i> Add New Category
            </a>
          </div>

          <div class="table-responsive">
            <table class="custom-table">
              <thead>
                <tr>
                  <th style="width: 70px; text-align: center;">Order</th>
                  <th style="width: 70px;">Icon</th>
                  <th>Category Name &amp; Code</th>
                  <th style="width: 140px; text-align: center;">Linked Dishes</th>
                  <th style="width: 130px; text-align: center;">Status</th>
                  <th style="width: 160px; text-align: right;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($categories)): ?>
                  <tr>
                    <td colspan="6" style="text-align: center; padding: 3.5rem; color: #64748b;">
                      <i class="fa-solid fa-layer-group" style="font-size: 2.5rem; opacity: 0.25; margin-bottom: 0.75rem; display: block;"></i>
                      Abhi koi category nahi hai. Upar diye <strong>"Add New Category"</strong> button par click karke add karein.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($categories as $cat): ?>
                    <?php 
                      $isActive = (int)$cat['status'] === 1;
                      $iconKey = $cat['icon'] ?? 'utensils';
                      $faIcon = $CATEGORY_ICONS[$iconKey]['fa'] ?? 'fa-solid fa-utensils';
                    ?>
                    <tr>
                      <!-- Display Order -->
                      <td style="text-align: center;">
                        <span class="order-box"><?= (int)$cat['display_order'] ?></span>
                      </td>

                      <!-- Icon Box -->
                      <td>
                        <span class="cat-icon-box" title="<?= htmlspecialchars($iconKey) ?>">
                          <i class="<?= htmlspecialchars($faIcon) ?>"></i>
                        </span>
                      </td>

                      <!-- Name & Slug -->
                      <td>
                        <div style="font-size: 0.95rem; font-weight: 700; color: #0f172a;">
                          <?= htmlspecialchars($cat['name']) ?>
                          <span class="meta-id-tag">#<?= htmlspecialchars($cat['id']) ?></span>
                        </div>
                      </td>

                      <!-- Linked Dishes -->
                      <td style="text-align: center;">
                        <a href="food_items.php?category=<?= urlencode($cat['id']) ?>" class="dish-count-tag" title="Click to view dishes">
                          <i class="fa-solid fa-utensils"></i> <?= (int)$cat['dish_count'] ?> Dishes
                        </a>
                      </td>

                      <!-- 1-Click Status Toggle -->
                      <td style="text-align: center;">
                        <a href="categories.php?toggle=<?= urlencode($cat['id']) ?>" class="status-toggle-btn <?= $isActive ? 'active' : 'inactive' ?>" title="Click to toggle status">
                          <i class="fa-solid <?= $isActive ? 'fa-circle-check' : 'fa-circle-pause' ?>"></i>
                          <span><?= $isActive ? 'Active' : 'Inactive' ?></span>
                        </a>
                      </td>

                      <!-- Actions -->
                      <td style="text-align: right;">
                        <div style="display: inline-flex; align-items: center; gap: 8px;">
                          <a href="edit_category.php?id=<?= urlencode($cat['id']) ?>" class="btn-sharp btn-sharp-edit" title="Edit Category">
                            <i class="fa-solid fa-pen"></i> Edit
                          </a>
                          <a href="delete_category.php?id=<?= urlencode($cat['id']) ?>" class="btn-sharp btn-sharp-delete" title="Delete Category" onclick="return confirm('Kya aap sach me category \'<?= addslashes(htmlspecialchars($cat['name'])) ?>\' ko delete karna chahte hain?');">
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
