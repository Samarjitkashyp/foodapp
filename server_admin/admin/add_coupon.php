<?php
require_once __DIR__ . '/config.php';

$db = getDb();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_coupon') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $discountType = $_POST['discount_type'] === 'flat' ? 'flat' : 'percentage';
    $discountValue = (float)($_POST['discount_value'] ?? 0);
    $maxDiscount = !empty($_POST['max_discount']) ? (float)$_POST['max_discount'] : null;
    $minOrder = (float)($_POST['min_order'] ?? 0);
    $displayOrder = (int)($_POST['display_order'] ?? 1);
    $status = isset($_POST['status']) ? 1 : 0;

    if (!$code) {
        $error = 'Please enter a coupon code (e.g. FIRST50, CRUNCH20).';
    } elseif (!$title) {
        $error = 'Please enter a coupon title (e.g. 50% OFF First Order).';
    } elseif ($discountValue <= 0) {
        $error = 'Discount value must be greater than 0.';
    } else {
        try {
            // Check duplicate code
            $chk = $db->prepare("SELECT COUNT(*) FROM coupons WHERE code = ?");
            $chk->execute([$code]);
            if ($chk->fetchColumn() > 0) {
                $error = "Coupon code '$code' pehle se exist karta hai. Kripya doosra code choose karein.";
            } else {
                $stmt = $db->prepare("INSERT INTO coupons (code, title, description, discount_type, discount_value, max_discount, min_order, display_order, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$code, $title, $description, $discountType, $discountValue, $maxDiscount, $minOrder, $displayOrder, $status]);

                header('Location: coupons.php?msg=added');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

try {
    $activeBanners = (int)$db->query("SELECT COUNT(*) FROM banners WHERE status = 1")->fetchColumn();
    $activeOffers = (int)$db->query("SELECT COUNT(*) FROM offers WHERE status = 1")->fetchColumn();
    $activeCoupons = (int)$db->query("SELECT COUNT(*) FROM coupons WHERE status = 1")->fetchColumn();
    $totalFood = (int)$db->query("SELECT COUNT(*) FROM food_items WHERE status = 1")->fetchColumn();
    $totalCats = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $nextOrder = (int)$db->query("SELECT COUNT(*) FROM coupons")->fetchColumn() + 1;
} catch (Exception $e) {
    $activeBanners = 0;
    $activeOffers = 0;
    $activeCoupons = 0;
    $totalFood = count($APP_PRODUCTS);
    $totalCats = 7;
    $nextOrder = 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <base href="/admin/">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add New Coupon | Crisp &amp; Crave</title>
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

    .form-container-2col {
      max-width: 1000px;
      margin: 0 auto;
    }

    .card {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
      padding: 24px;
      margin-bottom: 24px;
    }

    .card-title {
      font-size: 1.15rem;
      font-weight: 800;
      color: #0f172a;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
      border-bottom: 1px solid #f1f5f9;
      padding-bottom: 14px;
    }

    .form-group {
      margin-bottom: 18px;
    }

    .form-group label {
      display: block;
      font-size: 0.85rem;
      font-weight: 700;
      color: #334155;
      margin-bottom: 6px;
      text-transform: uppercase;
      letter-spacing: 0.03em;
    }

    .form-control {
      width: 100%;
      padding: 10px 14px;
      border: 1px solid #cbd5e1;
      background: #ffffff;
      color: #0f172a;
      font-size: 0.95rem;
      outline: none;
      transition: border-color 0.15s ease;
      box-sizing: border-box;
    }

    .form-control:focus {
      border-color: #f97316;
      box-shadow: 0 0 0 1px #f97316;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }

    .btn-sharp {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 11px 22px;
      font-size: 0.9rem;
      font-weight: 800;
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

    .sharp-alert-danger {
      padding: 14px 20px;
      margin-bottom: 20px;
      background: #fef2f2;
      color: #991b1b;
      border: 1px solid #fecaca;
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 0.9rem;
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
          <h1>Add New Coupon</h1>
          <p>Create a new discount code for Crisp &amp; Crave Mobile App</p>
        </div>
      </div>
      <div class="topbar-actions">
        <a href="coupons.php" class="btn-sharp btn-sharp-edit">
          <i class="fa-solid fa-arrow-left"></i> Back to Coupons
        </a>
      </div>
    </header>

    <!-- Main Content Body -->
    <main class="app-content">
      <div class="form-container-2col">

        <?php if ($error): ?>
          <div class="sharp-alert-danger">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="add_coupon.php">
          <input type="hidden" name="action" value="add_coupon">

          <div class="card">
            <div class="card-title">
              <span style="display: inline-block; width: 4px; height: 18px; background: #f97316;"></span>
              Coupon Basic Details
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Coupon Code *</label>
                <input type="text" name="code" class="form-control" placeholder="e.g. MONSOON30, FEAST100" style="text-transform: uppercase; font-family: monospace; font-weight: 800; font-size: 1.05rem;" required>
                <small style="color: #64748b; font-size: 0.78rem;">Customer yahi code cart me apply karega (Capital letters only).</small>
              </div>

              <div class="form-group">
                <label>Display Order *</label>
                <input type="number" name="display_order" class="form-control" value="<?= $nextOrder ?>" min="1" required>
                <small style="color: #64748b; font-size: 0.78rem;">App me pehle dikhane ke liye 1, 2, 3 set karein.</small>
              </div>
            </div>

            <div class="form-group">
              <label>Coupon Title / Heading *</label>
              <input type="text" name="title" class="form-control" placeholder="e.g. 50% OFF First Order, Flat ₹100 Special" required>
            </div>

            <div class="form-group">
              <label>Description / Terms Note</label>
              <textarea name="description" class="form-control" rows="2" placeholder="e.g. Save up to ₹120 on orders above ₹249"></textarea>
            </div>
          </div>

          <div class="card">
            <div class="card-title">
              <span style="display: inline-block; width: 4px; height: 18px; background: #10b981;"></span>
              Discount &amp; Order Rules
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Discount Type *</label>
                <select name="discount_type" class="form-control" id="discount_type" onchange="toggleDiscountFields();">
                  <option value="percentage">Percentage Discount (%)</option>
                  <option value="flat">Flat Discount Amount (₹)</option>
                </select>
              </div>

              <div class="form-group">
                <label id="value_label">Discount Value (% or ₹) *</label>
                <input type="number" step="0.01" name="discount_value" class="form-control" placeholder="e.g. 50 (for 50%) or 100 (for ₹100)" required>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group" id="max_discount_group">
                <label>Maximum Discount Cap (₹)</label>
                <input type="number" step="0.01" name="max_discount" class="form-control" placeholder="e.g. 120 (Max saving ₹120)">
                <small style="color: #64748b; font-size: 0.78rem;">Percentage discount ke liye max saving limit (Flat discount ke liye khali chhod sakte hain).</small>
              </div>

              <div class="form-group">
                <label>Minimum Order Amount (₹) *</label>
                <input type="number" step="0.01" name="min_order" class="form-control" placeholder="e.g. 249" value="0.00" required>
                <small style="color: #64748b; font-size: 0.78rem;">Cart subtotal is amount se zyada hone par hi coupon lagega.</small>
              </div>
            </div>

            <div class="form-group" style="margin-top: 10px;">
              <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; text-transform: none;">
                <input type="checkbox" name="status" value="1" checked style="width: 18px; height: 18px;">
                <span style="font-weight: 700; color: #0f172a; font-size: 0.95rem;">Active (Enable immediately in mobile app Deals &amp; Cart)</span>
              </label>
            </div>
          </div>

          <div style="display: flex; gap: 12px; margin-top: 20px;">
            <button type="submit" class="btn-sharp btn-sharp-primary" style="font-size: 1rem; padding: 12px 28px;">
              <i class="fa-solid fa-plus"></i> Save &amp; Publish Coupon
            </button>
            <a href="coupons.php" class="btn-sharp btn-sharp-edit">Cancel</a>
          </div>
        </form>

      </div>
    </main>

  </div>
</div>

<script>
function toggleDiscountFields() {
  var type = document.getElementById('discount_type').value;
  var maxGroup = document.getElementById('max_discount_group');
  var valLabel = document.getElementById('value_label');
  if (type === 'percentage') {
    valLabel.innerText = 'Discount Percentage (%) *';
    maxGroup.style.display = 'block';
  } else {
    valLabel.innerText = 'Flat Discount Amount (₹) *';
    maxGroup.style.display = 'none';
  }
}
toggleDiscountFields();
</script>

</body>
</html>
