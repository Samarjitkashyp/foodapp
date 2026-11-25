<?php
require_once __DIR__ . '/config.php';

$db = getDb();
$error = '';
$msg = $_GET['msg'] ?? '';

// Handle Status Change
if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? 'preparing');
    if ($orderId > 0 && in_array($newStatus, ['pending', 'confirmed', 'preparing', 'out_for_delivery', 'delivered', 'cancelled'])) {
        try {
            $stmt = $db->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $orderId]);
            header('Location: orders.php?msg=status_updated');
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Fetch orders with optional filter
$statusFilter = $_GET['status'] ?? '';
try {
    if ($statusFilter && in_array($statusFilter, ['pending', 'confirmed', 'preparing', 'out_for_delivery', 'delivered', 'cancelled'])) {
        $stmt = $db->prepare("SELECT * FROM orders WHERE order_status = ? ORDER BY id DESC LIMIT 100");
        $stmt->execute([$statusFilter]);
    } else {
        $stmt = $db->query("SELECT * FROM orders ORDER BY id DESC LIMIT 100");
    }
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    $orders = [];
    $error = 'Error loading orders: ' . $e->getMessage();
}

// Stats
try {
    $totalOrdersCount = (int)$db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $activeOrdersCount = (int)$db->query("SELECT COUNT(*) FROM orders WHERE order_status IN ('pending', 'confirmed', 'preparing', 'out_for_delivery')")->fetchColumn();
    $totalRevenue = (float)$db->query("SELECT COALESCE(SUM(grand_total), 0) FROM orders WHERE order_status != 'cancelled'")->fetchColumn();
    $totalUsersCount = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $activeBanners = (int)$db->query("SELECT COUNT(*) FROM banners WHERE status = 1")->fetchColumn();
    $activeOffers = (int)$db->query("SELECT COUNT(*) FROM offers WHERE status = 1")->fetchColumn();
    $activeCoupons = (int)$db->query("SELECT COUNT(*) FROM coupons WHERE status = 1")->fetchColumn();
    $totalFood = (int)$db->query("SELECT COUNT(*) FROM food_items WHERE status = 1")->fetchColumn();
    $totalCats = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
} catch (Exception $e) {
    $totalOrdersCount = count($orders);
    $activeOrdersCount = 0;
    $totalRevenue = 0;
    $totalUsersCount = 0;
    $activeBanners = 0;
    $activeOffers = 0;
    $activeCoupons = 0;
    $totalFood = 0;
    $totalCats = 7;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <base href="/admin/">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Live Customer Orders | Crisp &amp; Crave Control Center</title>
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

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 16px;
      margin-bottom: 20px;
    }

    .stat-card {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      padding: 18px 20px;
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .stat-icon {
      width: 48px;
      height: 48px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
    }

    .stat-details h4 {
      margin: 0;
      font-size: 0.8rem;
      font-weight: 700;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .stat-details .value {
      font-size: 1.6rem;
      font-weight: 900;
      color: #0f172a;
      margin-top: 4px;
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
      padding: 14px 18px;
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
      padding: 16px 18px;
      vertical-align: top;
      font-size: 0.9rem;
      color: #334155;
    }

    .order-id-badge {
      display: inline-block;
      font-family: monospace;
      font-weight: 900;
      color: #f97316;
      background: #fff7ed;
      padding: 4px 10px;
      border: 1px solid #fed7aa;
      font-size: 0.95rem;
    }

    .customer-box {
      line-height: 1.4;
    }
    .customer-box .name {
      font-weight: 800;
      color: #0f172a;
      font-size: 0.95rem;
    }
    .customer-box .phone {
      font-size: 0.85rem;
      color: #2563eb;
      font-weight: 700;
      margin: 2px 0;
    }
    .customer-box .address {
      font-size: 0.8rem;
      color: #64748b;
      max-width: 260px;
    }

    .items-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .items-list li {
      padding: 4px 0;
      border-bottom: 1px dashed #f1f5f9;
      font-size: 0.85rem;
      display: flex;
      justify-content: space-between;
      gap: 12px;
    }
    .items-list li:last-child {
      border-bottom: none;
    }
    .item-qty {
      font-weight: 800;
      color: #0f172a;
      background: #f1f5f9;
      padding: 1px 6px;
      margin-right: 6px;
      font-size: 0.8rem;
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
    .status-preparing { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
    .status-out_for_delivery { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
    .status-delivered { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
    .status-cancelled { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
    .status-pending { background: #fefce8; color: #854d0e; border: 1px solid #fef08a; }

    .status-select {
      padding: 6px 10px;
      font-size: 0.8rem;
      font-weight: 700;
      border: 1px solid #cbd5e1;
      background: #ffffff;
      outline: none;
      cursor: pointer;
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
      <a href="orders.php" class="nav-link active">
        <i class="fa-solid fa-bag-shopping"></i>
        <span>Live Orders</span>
        <span class="nav-badge"><?= $activeOrdersCount ?> Active</span>
      </a>
      <a href="users.php" class="nav-link">
        <i class="fa-solid fa-users"></i>
        <span>Customers</span>
        <span class="nav-badge"><?= $totalUsersCount ?></span>
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
          <h1>Customer Orders Management</h1>
          <p>Real-time orders received from Mobile App and Website</p>
        </div>
      </div>
      <div class="topbar-actions">
        <a href="orders.php" class="btn-sharp btn-sharp-edit">
          <i class="fa-solid fa-rotate-right"></i> Refresh Orders
        </a>
      </div>
    </header>

    <main class="app-content">
      <div class="hero-manager-container">

        <!-- Stats Overview -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon" style="background: #fff7ed; color: #ea580c;">
              <i class="fa-solid fa-fire"></i>
            </div>
            <div class="stat-details">
              <h4>Active Kitchen Orders</h4>
              <div class="value"><?= $activeOrdersCount ?></div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon" style="background: #ecfdf5; color: #059669;">
              <i class="fa-solid fa-indian-rupee-sign"></i>
            </div>
            <div class="stat-details">
              <h4>Total Sales Revenue</h4>
              <div class="value">₹<?= number_format($totalRevenue, 2) ?></div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon" style="background: #eff6ff; color: #2563eb;">
              <i class="fa-solid fa-box-archive"></i>
            </div>
            <div class="stat-details">
              <h4>Total Orders Received</h4>
              <div class="value"><?= $totalOrdersCount ?></div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon" style="background: #faf5ff; color: #9333ea;">
              <i class="fa-solid fa-users"></i>
            </div>
            <div class="stat-details">
              <h4>Registered Customers</h4>
              <div class="value"><?= $totalUsersCount ?></div>
            </div>
          </div>
        </div>

        <!-- Filter Bar -->
        <div style="margin-bottom: 16px; display: flex; gap: 8px; flex-wrap: wrap;">
          <a href="orders.php" class="btn-sharp <?= empty($statusFilter) ? 'btn-sharp-primary' : 'btn-sharp-edit' ?>">All Orders (<?= $totalOrdersCount ?>)</a>
          <a href="orders.php?status=preparing" class="btn-sharp <?= $statusFilter === 'preparing' ? 'btn-sharp-primary' : 'btn-sharp-edit' ?>">Preparing in Kitchen</a>
          <a href="orders.php?status=out_for_delivery" class="btn-sharp <?= $statusFilter === 'out_for_delivery' ? 'btn-sharp-primary' : 'btn-sharp-edit' ?>">Out for Delivery</a>
          <a href="orders.php?status=delivered" class="btn-sharp <?= $statusFilter === 'delivered' ? 'btn-sharp-primary' : 'btn-sharp-edit' ?>">Delivered</a>
          <a href="orders.php?status=cancelled" class="btn-sharp <?= $statusFilter === 'cancelled' ? 'btn-sharp-primary' : 'btn-sharp-edit' ?>">Cancelled</a>
        </div>

        <!-- Orders Table -->
        <div class="card">
          <div class="card-header">
            <h3>
              <span style="display: inline-block; width: 4px; height: 18px; background: #f97316;"></span>
              Customer Orders Breakdown
            </h3>
          </div>

          <div class="table-responsive">
            <table class="custom-table">
              <thead>
                <tr>
                  <th style="width: 140px;">Order #</th>
                  <th style="width: 280px;">Kisne Order Kiya (Customer)</th>
                  <th>Kya Order Kiya (Items)</th>
                  <th style="width: 130px;">Amount</th>
                  <th style="width: 130px;">Payment</th>
                  <th style="width: 180px;">Status &amp; Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($orders)): ?>
                  <tr>
                    <td colspan="6" style="text-align: center; padding: 4rem; color: #64748b;">
                      <i class="fa-solid fa-bag-shopping" style="font-size: 2.5rem; color: #cbd5e1; margin-bottom: 12px; display: block;"></i>
                      Abhi tak koi order nahi mila. Mobile app ya website se order aate hi yahan live show hoga.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($orders as $order): ?>
                    <?php 
                      $items = json_decode($order['items_json'] ?? '[]', true) ?: []; 
                      $statusClass = 'status-' . ($order['order_status'] ?? 'preparing');
                    ?>
                    <tr>
                      <td>
                        <span class="order-id-badge"><?= htmlspecialchars($order['order_number']) ?></span>
                        <div style="font-size: 0.75rem; color: #64748b; margin-top: 6px;">
                          <?= date('d M, h:i A', strtotime($order['created_at'])) ?>
                        </div>
                        <span style="font-size: 0.7rem; background: #f1f5f9; padding: 2px 6px; font-weight: 700; color: #475569;">
                          <?= htmlspecialchars($order['source'] ?? 'App') ?>
                        </span>
                      </td>

                      <td>
                        <div class="customer-box">
                          <div class="name"><i class="fa-solid fa-user" style="color: #94a3b8; font-size: 0.8rem;"></i> <?= htmlspecialchars($order['customer_name']) ?></div>
                          <div class="phone"><i class="fa-solid fa-phone" style="font-size: 0.8rem;"></i> <?= htmlspecialchars($order['customer_phone']) ?></div>
                          <div class="address"><i class="fa-solid fa-location-dot" style="color: #ea580c; font-size: 0.8rem;"></i> <?= htmlspecialchars($order['delivery_address']) ?></div>
                          <?php if (!empty($order['landmark'])): ?>
                            <div style="font-size: 0.75rem; color: #94a3b8;"><i class="fa-regular fa-flag"></i> Landmark: <?= htmlspecialchars($order['landmark']) ?></div>
                          <?php endif; ?>
                        </div>
                      </td>

                      <td>
                        <ul class="items-list">
                          <?php foreach ($items as $item): ?>
                            <li>
                              <div>
                                <span class="item-qty"><?= (int)($item['quantity'] ?? 1) ?>x</span>
                                <strong style="color: #0f172a;"><?= htmlspecialchars($item['name'] ?? 'Item') ?></strong>
                                <?php if (!empty($item['selected_addons'])): ?>
                                  <div style="font-size: 0.75rem; color: #94a3b8; padding-left: 24px;">
                                    + <?= htmlspecialchars(implode(', ', (array)$item['selected_addons'])) ?>
                                  </div>
                                <?php endif; ?>
                              </div>
                              <span style="font-weight: 700; color: #334155;">₹<?= (int)($item['price'] ?? 0) * (int)($item['quantity'] ?? 1) ?></span>
                            </li>
                          <?php endforeach; ?>
                        </ul>

                        <?php if (!empty($order['cooking_instructions'])): ?>
                          <div style="margin-top: 8px; font-size: 0.78rem; background: #fffbeb; color: #b45309; padding: 4px 8px; border: 1px dashed #fcd34d;">
                            <strong><i class="fa-solid fa-kitchen-set"></i> Cooking Note:</strong> <?= htmlspecialchars($order['cooking_instructions']) ?>
                          </div>
                        <?php endif; ?>

                        <?php if (!empty($order['coupon_code'])): ?>
                          <div style="margin-top: 4px; font-size: 0.75rem; color: #16a34a; font-weight: 700;">
                            <i class="fa-solid fa-tag"></i> Coupon Applied: <?= htmlspecialchars($order['coupon_code']) ?> (-₹<?= (int)$order['discount_amount'] ?>)
                          </div>
                        <?php endif; ?>
                      </td>

                      <td>
                        <div style="font-size: 1.15rem; font-weight: 900; color: #0f172a;">
                          ₹<?= number_format((float)$order['grand_total'], 0) ?>
                        </div>
                        <div style="font-size: 0.75rem; color: #64748b;">
                          Subtotal: ₹<?= (int)$order['subtotal'] ?>
                        </div>
                      </td>

                      <td>
                        <strong style="text-transform: uppercase; font-size: 0.85rem; color: #334155;">
                          <?= htmlspecialchars($order['payment_method']) ?>
                        </strong>
                        <div style="font-size: 0.75rem; color: <?= $order['payment_status'] === 'paid' ? '#16a34a' : '#ea580c' ?>; font-weight: 700;">
                          <?= ucfirst($order['payment_status']) ?>
                        </div>
                      </td>

                      <td>
                        <form method="POST" action="orders.php" style="display: flex; gap: 6px; align-items: center;">
                          <input type="hidden" name="action" value="update_status">
                          <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                          <select name="status" class="status-select" onchange="this.form.submit();">
                            <option value="pending" <?= $order['order_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="confirmed" <?= $order['order_status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="preparing" <?= $order['order_status'] === 'preparing' ? 'selected' : '' ?>>Preparing</option>
                            <option value="out_for_delivery" <?= $order['order_status'] === 'out_for_delivery' ? 'selected' : '' ?>>Out for Delivery</option>
                            <option value="delivered" <?= $order['order_status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                            <option value="cancelled" <?= $order['order_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                          </select>
                        </form>
                        <div style="margin-top: 6px;">
                          <span class="status-badge <?= $statusClass ?>">
                            <?= ucfirst(str_replace('_', ' ', $order['order_status'])) ?>
                          </span>
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
