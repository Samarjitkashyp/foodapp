<?php
require_once __DIR__ . '/config.php';

$db = getDb();
$error = '';
$msg = $_GET['msg'] ?? '';

// Handle Toggle Status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    try {
        $stmt = $db->prepare("UPDATE users SET status = IF(status = 1, 0, 1) WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: users.php?msg=updated');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch users
try {
    $stmt = $db->query("SELECT * FROM users ORDER BY total_orders DESC, id DESC LIMIT 200");
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    $users = [];
    $error = 'Error loading users: ' . $e->getMessage();
}

// Stats
$totalUsers = count($users);
$activeUsers = array_filter($users, fn($u) => (int)$u['status'] === 1);
$activeUserCount = count($activeUsers);

try {
    $activeOrdersCount = (int)$db->query("SELECT COUNT(*) FROM orders WHERE order_status IN ('pending', 'confirmed', 'preparing', 'out_for_delivery')")->fetchColumn();
    $activeBanners = (int)$db->query("SELECT COUNT(*) FROM banners WHERE status = 1")->fetchColumn();
    $activeOffers = (int)$db->query("SELECT COUNT(*) FROM offers WHERE status = 1")->fetchColumn();
    $activeCoupons = (int)$db->query("SELECT COUNT(*) FROM coupons WHERE status = 1")->fetchColumn();
    $totalFood = (int)$db->query("SELECT COUNT(*) FROM food_items WHERE status = 1")->fetchColumn();
    $totalCats = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
} catch (Exception $e) {
    $activeOrdersCount = 0;
    $activeBanners = 0;
    $activeOffers = 0;
    $activeCoupons = 0;
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
  <title>Customer Management | Crisp &amp; Crave Control Center</title>
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
      max-width: 1400px;
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
      padding: 16px 20px;
      vertical-align: middle;
      font-size: 0.9rem;
      color: #334155;
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
    .status-active { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
    .status-inactive { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

    .user-avatar-circle {
      width: 40px;
      height: 40px;
      background: #f1f5f9;
      color: #f97316;
      font-weight: 900;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
      border: 1px solid #e2e8f0;
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
        <span class="nav-badge"><?= $activeOrdersCount ?> Active</span>
      </a>
      <a href="users.php" class="nav-link active">
        <i class="fa-solid fa-users"></i>
        <span>Customers</span>
        <span class="nav-badge"><?= $totalUsers ?></span>
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
      <a href="coupons.php" class="nav-link">
        <i class="fa-solid fa-ticket"></i>
        <span>Coupons</span>
        <span class="nav-badge"><?= $activeCoupons ?> Live</span>
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
  </aside>

  <!-- MAIN CONTENT CONTAINER -->
  <div class="app-main">
    
    <!-- Topbar -->
    <header class="app-topbar">
      <div class="topbar-left">
        <div class="topbar-title">
          <h1>Customer / User Management</h1>
          <p>View registered customers, their delivery addresses, order counts &amp; spending</p>
        </div>
      </div>
      <div class="topbar-actions">
        <a href="users.php" class="btn-sharp btn-sharp-edit">
          <i class="fa-solid fa-rotate-right"></i> Refresh
        </a>
      </div>
    </header>

    <main class="app-content">
      <div class="hero-manager-container">

        <!-- Table Card -->
        <div class="card">
          <div class="card-header">
            <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 10px;">
              <span style="display: inline-block; width: 4px; height: 18px; background: #f97316;"></span>
              Registered App &amp; Website Customers
              <span style="font-size: 0.75rem; font-weight: 700; background: #f1f5f9; color: #475569; padding: 3px 8px; border: 1px solid #cbd5e1;"><?= $totalUsers ?> Total Customers</span>
            </h3>
          </div>

          <div class="table-responsive">
            <table class="custom-table">
              <thead>
                <tr>
                  <th style="width: 70px;">ID</th>
                  <th>Customer Profile</th>
                  <th>Phone Number</th>
                  <th>Saved Delivery Address</th>
                  <th style="width: 120px;">Total Orders</th>
                  <th style="width: 140px;">Total Spend</th>
                  <th style="width: 120px;">Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($users)): ?>
                  <tr>
                    <td colspan="7" style="text-align: center; padding: 4rem; color: #64748b;">
                      <i class="fa-solid fa-users" style="font-size: 2.5rem; color: #cbd5e1; margin-bottom: 12px; display: block;"></i>
                      Abhi tak koi user record nahi hai. Mobile app me order hote hi customer automatically add ho jayega.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($users as $user): ?>
                    <tr>
                      <td>#<?= (int)$user['id'] ?></td>
                      <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                          <div class="user-avatar-circle">
                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                          </div>
                          <div>
                            <strong style="color: #0f172a; font-size: 0.95rem;"><?= htmlspecialchars($user['name']) ?></strong>
                            <?php if (!empty($user['email'])): ?>
                              <div style="font-size: 0.78rem; color: #64748b;"><?= htmlspecialchars($user['email']) ?></div>
                            <?php endif; ?>
                            <div style="font-size: 0.75rem; color: #94a3b8;">Joined: <?= date('d M Y', strtotime($user['created_at'])) ?></div>
                          </div>
                        </div>
                      </td>
                      <td>
                        <a href="tel:<?= htmlspecialchars($user['phone']) ?>" style="color: #2563eb; font-weight: 700; text-decoration: none;">
                          <i class="fa-solid fa-phone" style="font-size: 0.8rem;"></i> <?= htmlspecialchars($user['phone']) ?>
                        </a>
                      </td>
                      <td>
                        <div style="max-width: 280px; font-size: 0.85rem; color: #475569;">
                          <?= htmlspecialchars($user['address'] ?? 'Not provided') ?>
                          <?php if (!empty($user['landmark'])): ?>
                            <div style="font-size: 0.75rem; color: #94a3b8;">Landmark: <?= htmlspecialchars($user['landmark']) ?></div>
                          <?php endif; ?>
                        </div>
                      </td>
                      <td>
                        <span style="font-weight: 900; color: #0f172a; background: #f8fafc; padding: 3px 8px; border: 1px solid #e2e8f0;">
                          <?= (int)$user['total_orders'] ?> Orders
                        </span>
                      </td>
                      <td>
                        <strong style="color: #16a34a; font-size: 1rem;">₹<?= number_format((float)$user['total_spend'], 2) ?></strong>
                      </td>
                      <td>
                        <a href="users.php?toggle=<?= $user['id'] ?>" style="text-decoration: none;" title="Toggle Account Status">
                          <?php if ((int)$user['status'] === 1): ?>
                            <span class="status-badge status-active">
                              <i class="fa-solid fa-circle-check"></i> Active
                            </span>
                          <?php else: ?>
                            <span class="status-badge status-inactive">
                              <i class="fa-solid fa-ban"></i> Blocked
                            </span>
                          <?php endif; ?>
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
