<?php
require_once __DIR__ . '/config.php';

$db = getDb();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_category') {
    $name = trim($_POST['name'] ?? '');
    $id = trim($_POST['id'] ?? '');
    $icon = trim($_POST['icon'] ?? 'utensils');
    $displayOrder = (int)($_POST['display_order'] ?? 1);
    $status = isset($_POST['status']) ? 1 : 0;

    if (empty($name)) {
        $error = 'Category name enter karna zaroori hai.';
    } else {
        if (empty($id)) {
            $id = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        }

        // Check uniqueness
        $check = $db->prepare("SELECT COUNT(*) FROM categories WHERE id = ?");
        $check->execute([$id]);
        if ($check->fetchColumn() > 0) {
            $error = "Category Code '$id' pehle se exist karta hai. Kripya doosra unique code enter karein.";
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO categories (id, name, icon, display_order, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$id, $name, $icon, $displayOrder, $status]);
                header('Location: categories.php?msg=added');
                exit;
            } catch (Exception $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Counts for badges
$totalBanners = (int)$db->query("SELECT COUNT(*) FROM banners")->fetchColumn();
$activeBanners = (int)$db->query("SELECT COUNT(*) FROM banners WHERE status = 1")->fetchColumn();
$totalFood = (int)$db->query("SELECT COUNT(*) FROM food_items")->fetchColumn();
$activeFood = (int)$db->query("SELECT COUNT(*) FROM food_items WHERE status = 1")->fetchColumn();
$totalCats = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$activeCats = (int)$db->query("SELECT COUNT(*) FROM categories WHERE status = 1")->fetchColumn();

$nextOrder = $totalCats + 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Category - Crisp &amp; Crave Control Center</title>
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
    .form-container {
      max-width: 1080px;
      margin: 0 auto;
    }
    .grid-2col {
      display: grid;
      grid-template-columns: 340px 1fr;
      gap: 24px;
      align-items: start;
    }
    @media (max-width: 800px) {
      .grid-2col {
        grid-template-columns: 1fr;
      }
    }
    .card {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }
    .card-header {
      padding: 16px 22px;
      background: #ffffff;
      border-bottom: 1px solid #e2e8f0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .card-header h3 {
      font-size: 1.05rem;
      font-weight: 800;
      color: #0f172a;
      display: flex;
      align-items: center;
      gap: 8px;
      margin: 0;
    }
    .card-body {
      padding: 22px;
    }
    .form-group {
      margin-bottom: 1.25rem;
    }
    .form-group label {
      display: block;
      font-size: 0.82rem;
      font-weight: 700;
      color: #334155;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 6px;
    }
    .form-group label span.req {
      color: #dc2626;
    }
    .form-input, .form-select {
      width: 100%;
      padding: 10px 14px;
      border: 1px solid #cbd5e1;
      font-size: 0.9rem;
      color: #0f172a;
      background: #ffffff;
      box-sizing: border-box;
      outline: none;
      transition: border-color 0.15s;
    }
    .form-input:focus, .form-select:focus {
      border-color: #f97316;
    }
    .form-row-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }
    .btn-sharp {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 20px;
      font-size: 0.9rem;
      font-weight: 700;
      border: 1px solid transparent;
      cursor: pointer;
      text-decoration: none;
    }
    .btn-sharp-primary {
      background: #f97316;
      color: #fff;
      border-color: #ea580c;
    }
    .btn-sharp-primary:hover {
      background: #ea580c;
    }
    .btn-sharp-edit {
      background: #f8fafc;
      color: #1e293b;
      border-color: #cbd5e1;
    }
    .btn-sharp-edit:hover {
      background: #f1f5f9;
    }
    .sharp-alert-danger {
      padding: 12px 18px;
      margin-bottom: 1.25rem;
      border-left: 4px solid #ef4444;
      background: #fef2f2;
      color: #991b1b;
      display: flex;
      align-items: center;
      gap: 10px;
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
      <a href="banners.php" class="nav-link">
        <i class="fa-solid fa-images"></i>
        <span>Hero Slider</span>
        <span class="nav-badge"><?= $activeBanners ?> Live</span>
      </a>
      <a href="offers.php" class="nav-link">
        <i class="fa-solid fa-tags"></i>
        <span>Offers</span>
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

  <!-- MAIN CONTENT -->
  <div class="app-main">
    <header class="app-topbar">
      <div class="topbar-left">
        <div class="topbar-title">
          <h1>Add Food Category</h1>
          <p>Create a new category for Flutter app menu filtering</p>
        </div>
      </div>
      <div class="topbar-actions">
        <a href="categories.php" class="btn-sharp btn-sharp-edit">
          <i class="fa-solid fa-arrow-left"></i> Back to Categories
        </a>
      </div>
    </header>

    <main class="app-content">
      <div class="form-container">

        <?php if ($error): ?>
          <div class="sharp-alert-danger">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div><?= htmlspecialchars($error) ?></div>
          </div>
        <?php endif; ?>

        <form action="add_category.php" method="POST">
          <input type="hidden" name="action" value="add_category">

          <div class="grid-2col">
            <!-- Left Column: Icon Guide & Tips -->
            <div class="card">
              <div class="card-header">
                <h3><i class="fa-solid fa-icons" style="color: #f97316;"></i> Category Icon</h3>
              </div>
              <div class="card-body">
                <div style="text-align: center; padding: 1.5rem; background: #f8fafc; border: 1px solid #e2e8f0; margin-bottom: 16px;">
                  <div id="iconPreviewBig" style="width: 64px; height: 64px; margin: 0 auto; background: #fff7ed; color: #f97316; border: 2px solid #fed7aa; display: flex; align-items: center; justify-content: center; font-size: 2rem;">
                    <i class="fa-solid fa-utensils"></i>
                  </div>
                  <div id="iconLabelPreview" style="margin-top: 10px; font-weight: 700; font-size: 0.9rem; color: #0f172a;">
                    Utensils
                  </div>
                </div>

                <div style="padding: 12px; background: #f8fafc; border: 1px solid #e2e8f0; font-size: 0.82rem; color: #475569; line-height: 1.5;">
                  <strong><i class="fa-solid fa-circle-info" style="color: #f97316;"></i> Mobile App Sync:</strong>
                  Category create hote hi Flutter app ke horizontal category bar me naya chip live aa jayega.
                </div>
              </div>
            </div>

            <!-- Right Column: Details -->
            <div class="card">
              <div class="card-header">
                <h3><i class="fa-solid fa-layer-group" style="color: #f97316;"></i> Category Details</h3>
              </div>
              <div class="card-body">
                
                <!-- Category Name -->
                <div class="form-group">
                  <label>Category Title / Name <span class="req">*</span></label>
                  <input type="text" name="name" class="form-input" placeholder="e.g. Gourmet Pizzas" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" oninput="autoSlug(this.value)">
                </div>

                <!-- Slug & Icon -->
                <div class="form-row-2">
                  <div class="form-group">
                    <label>Category Code / Slug <span style="font-weight: 400; color: #64748b;">(auto)</span></label>
                    <input type="text" id="categorySlug" name="id" class="form-input" placeholder="e.g. pizzas" value="<?= htmlspecialchars($_POST['id'] ?? '') ?>">
                  </div>
                  <div class="form-group">
                    <label>Display Icon <span class="req">*</span></label>
                    <select name="icon" class="form-select" id="iconSelect" onchange="updateIconPreview(this.value)">
                      <?php foreach ($CATEGORY_ICONS as $ikey => $idata): ?>
                        <option value="<?= $ikey ?>"><?= htmlspecialchars($idata['label']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>

                <!-- Display Order & Status -->
                <div class="form-row-2">
                  <div class="form-group">
                    <label>Display Order</label>
                    <input type="number" name="display_order" class="form-input" value="<?= $nextOrder ?>" min="1" max="99">
                  </div>
                  <div class="form-group" style="padding-top: 26px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; text-transform: none; font-size: 0.95rem;">
                      <input type="checkbox" name="status" value="1" checked style="width: 18px; height: 18px; accent-color: #f97316;">
                      <span><strong>Active in Mobile App</strong></span>
                    </label>
                  </div>
                </div>

                <div style="border-top: 1px solid #e2e8f0; padding-top: 18px; margin-top: 20px; display: flex; gap: 12px; justify-content: flex-end;">
                  <a href="categories.php" class="btn-sharp btn-sharp-edit">Cancel</a>
                  <button type="submit" class="btn-sharp btn-sharp-primary"><i class="fa-solid fa-floppy-disk"></i> Publish Category</button>
                </div>

              </div>
            </div>
          </div>
        </form>

      </div>
    </main>
  </div>
</div>

<script>
const iconMap = <?= json_encode($CATEGORY_ICONS) ?>;

function updateIconPreview(key) {
  const iconData = iconMap[key] || { fa: 'fa-solid fa-utensils', label: key };
  document.getElementById('iconPreviewBig').innerHTML = '<i class="' + iconData.fa + '"></i>';
  document.getElementById('iconLabelPreview').textContent = iconData.label;
}

function autoSlug(val) {
  const slugInput = document.getElementById('categorySlug');
  if (!slugInput.dataset.manual) {
    slugInput.value = val.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
  }
}

document.getElementById('categorySlug').addEventListener('input', function() {
  this.dataset.manual = 'true';
});
</script>
</body>
</html>
