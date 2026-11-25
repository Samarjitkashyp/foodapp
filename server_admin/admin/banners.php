<?php
require_once __DIR__ . '/config.php';

$db = getDb();
$error = '';
$msg = $_GET['msg'] ?? '';

// Handle Toggle Status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    try {
        $cols = $db->query("SHOW COLUMNS FROM banners")->fetchAll(PDO::FETCH_COLUMN);
        $statusCol = in_array('status', $cols) ? 'status' : 'is_active';
        $stmt = $db->prepare("UPDATE banners SET $statusCol = IF($statusCol = 1, 0, 1) WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: banners.php?msg=updated');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle Form Submission for Adding New Banner
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_banner') {
    $title = trim($_POST['title'] ?? '');
    $productId = trim($_POST['product_id'] ?? '');
    $displayOrder = (int)($_POST['display_order'] ?? 1);
    $status = isset($_POST['status']) ? 1 : 0;
    $imageUrl = trim($_POST['image_url'] ?? '');

    // Check File Upload
    if (isset($_FILES['banner_file']) && $_FILES['banner_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['banner_file']['tmp_name'];
        $fileName = $_FILES['banner_file']['name'];
        $fileSize = $_FILES['banner_file']['size'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];

        if (!in_array($fileExt, $allowed)) {
            $error = 'Invalid image format. Sirf JPG aur PNG format allow hai.';
        } elseif ($fileSize > 3 * 1024 * 1024) {
            $error = 'Image size bohot bada hai (' . round($fileSize / (1024 * 1024), 2) . ' MB). Maximum allowed size 3 MB hai!';
        } else {
            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0755, true);
            }
            $newFileName = 'canva_' . time() . '_' . rand(100, 999) . '.' . $fileExt;
            $destination = UPLOAD_DIR . $newFileName;
            if (move_uploaded_file($fileTmp, $destination)) {
                $imageUrl = UPLOAD_URL . $newFileName;
            } else {
                $error = 'Failed to save uploaded Canva image on server.';
            }
        }
    }

    if (!$imageUrl && !$error) {
        $error = 'Please upload a Canva design image (JPG/PNG under 3 MB).';
    }
    if (!$title) {
        $error = 'Please enter a title for the banner.';
    }
    if (!$productId) {
        $error = 'Please select which dish opens in the app when tapped.';
    }

    if (!$error) {
        try {
            $cols = $db->query("SHOW COLUMNS FROM banners")->fetchAll(PDO::FETCH_COLUMN);
            $hasStatus = in_array('status', $cols);
            $hasIsActive = in_array('is_active', $cols);
            $hasProdId = in_array('product_id', $cols);
            $hasTargetProdId = in_array('target_product_id', $cols);

            $insertCols = ['title', 'image_url', 'display_order'];
            $insertVals = [$title, $imageUrl, $displayOrder];

            if ($hasProdId) {
                $insertCols[] = 'product_id';
                $insertVals[] = $productId;
            }
            if ($hasTargetProdId) {
                $insertCols[] = 'target_product_id';
                $insertVals[] = $productId;
            }
            if ($hasStatus) {
                $insertCols[] = 'status';
                $insertVals[] = $status;
            }
            if ($hasIsActive) {
                $insertCols[] = 'is_active';
                $insertVals[] = $status;
            }

            $placeholders = implode(', ', array_fill(0, count($insertCols), '?'));
            $sql = "INSERT INTO banners (" . implode(', ', $insertCols) . ") VALUES ($placeholders)";
            $stmt = $db->prepare($sql);
            $stmt->execute($insertVals);

            header('Location: banners.php?msg=added');
            exit;
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch all banners safely with column fallbacks
try {
    $cols = $db->query("SHOW COLUMNS FROM banners")->fetchAll(PDO::FETCH_COLUMN);
    $prodExpr = in_array('product_id', $cols) ? "COALESCE(product_id, '')" : (in_array('target_product_id', $cols) ? "COALESCE(target_product_id, '')" : "''");
    $statusExpr = in_array('status', $cols) ? "COALESCE(status, 1)" : (in_array('is_active', $cols) ? "COALESCE(is_active, 1)" : "1");

    $query = "SELECT id, title, image_url, $prodExpr AS product_id, $statusExpr AS status, display_order, created_at FROM banners ORDER BY display_order ASC, id DESC";
    $stmt = $db->query($query);
    $banners = $stmt->fetchAll();
} catch (Exception $e) {
    $banners = [];
    $error = 'Error loading banners: ' . $e->getMessage();
}

// Stats
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
  <title>Hero Slider | Crisp &amp; Crave</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="/admin/style.css">
  <style>
<?php if (file_exists(__DIR__ . '/style.css')) { include __DIR__ . '/style.css'; } ?>

    /* ======================================================================
       SHARP, FLAT & PREMIUM DATA GRID (NO BORDER-RADIUS)
       ====================================================================== */
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
    }

    .card-header h3 {
      font-size: 1.15rem;
      font-weight: 800;
      color: #0f172a;
      display: flex;
      align-items: center;
      gap: 10px;
      letter-spacing: -0.01em;
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
      padding: 16px 20px;
      vertical-align: middle;
      font-size: 0.9rem;
    }

    /* Banner Thumbnail (Sharp 2:1 aspect ratio) */
    .banner-row-thumb {
      width: 140px;
      height: 70px;
      object-fit: cover;
      border: 1px solid #e2e8f0;
      background: #f1f5f9;
      display: block;
      transition: border-color 0.15s ease;
    }
    .banner-row-thumb:hover {
      border-color: var(--primary);
    }

    /* Title & Meta Info */
    .banner-title-text {
      font-size: 1rem;
      font-weight: 700;
      color: #0f172a;
      line-height: 1.35;
      margin-bottom: 5px;
    }

    .banner-meta-row {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 0.78rem;
    }

    .meta-id-tag {
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
      background: #f1f5f9;
      color: #475569;
      padding: 2px 6px;
      border: 1px solid #e2e8f0;
      font-weight: 700;
      font-size: 11px;
    }

    .meta-dish-tag {
      color: var(--primary);
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: #fff2ee;
      padding: 2px 8px;
      border: 1px solid rgba(255, 87, 34, 0.2);
    }

    /* Sharp Order Number Square */
    .order-box {
      display: inline-flex;
      width: 36px;
      height: 36px;
      align-items: center;
      justify-content: center;
      background: #f8fafc;
      border: 1px solid #cbd5e1;
      font-weight: 800;
      font-size: 14px;
      color: #0f172a;
    }

    /* Sharp Status Toggle */
    .status-toggle-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 14px;
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
      border-color: #34d399;
    }

    .status-toggle-btn.inactive {
      background: #f8fafc;
      color: #64748b;
      border: 1px solid #cbd5e1;
    }

    .status-toggle-btn.inactive:hover {
      background: #f1f5f9;
      color: #1e293b;
      border-color: #94a3b8;
    }

    /* Sharp Action Buttons */
    .btn-sharp {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 7px 14px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.15s ease;
      border: 1px solid transparent;
    }

    .btn-sharp-primary {
      background: var(--primary);
      color: #ffffff;
      border-color: var(--primary);
      font-weight: 700;
      padding: 8px 18px;
    }
    .btn-sharp-primary:hover {
      background: var(--primary-hover);
      border-color: var(--primary-hover);
      color: #ffffff;
    }

    .btn-sharp-edit {
      background: #ffffff;
      color: #0f172a;
      border: 1px solid #cbd5e1;
    }
    .btn-sharp-edit:hover {
      background: #f8fafc;
      border-color: #94a3b8;
      color: #000000;
    }

    .btn-sharp-delete {
      background: #ffffff;
      color: #dc2626;
      border: 1px solid #fecaca;
      padding: 7px 11px;
    }
    .btn-sharp-delete:hover {
      background: #dc2626;
      border-color: #dc2626;
      color: #ffffff;
    }

    /* Sharp Alerts */
    .sharp-alert {
      padding: 12px 18px;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 13px;
      font-weight: 500;
      border: 1px solid transparent;
      border-left-width: 4px;
    }
    .sharp-alert-success {
      background: #ecfdf5;
      color: #065f46;
      border-color: #a7f3d0;
      border-left-color: #10b981;
    }
    .sharp-alert-info {
      background: #eff6ff;
      color: #1e40af;
      border-color: #bfdbfe;
      border-left-color: #3b82f6;
    }
    .sharp-alert-danger {
      background: #fef2f2;
      color: #991b1b;
      border-color: #fecaca;
      border-left-color: #ef4444;
    }

    /* Sharp Topbar & Status Pill */
    .app-topbar {
      border-bottom: 1px solid #e2e8f0;
      background: #ffffff;
    }
    .status-pill {
      background: #ecfdf5;
      color: #047857;
      border: 1px solid #a7f3d0;
      font-weight: 700;
      padding: 4px 10px;
    }

    /* Sharp Modal & Form Elements */
    .modal-content {
      border: 1px solid #cbd5e1;
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.25);
    }
    .form-input, .form-select {
      border: 1px solid #cbd5e1;
      font-size: 13px;
    }
    .form-input:focus, .form-select:focus {
      border-color: var(--primary);
      outline: 1px solid var(--primary);
      box-shadow: none;
    }
    .canva-dropzone {
      border: 2px dashed #cbd5e1;
      background: #f8fafc;
    }
  </style>
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
      <a href="banners.php" class="nav-link active">
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
        <h1 class="topbar-title">Hero Slider</h1>
        <div class="status-pill">
          <span class="pulse-dot"></span>
          <span>Flutter App Live Synced</span>
        </div>
      </div>

      <div class="topbar-actions">
        <a href="index.php" class="btn-sharp btn-sharp-edit">
          <i class="fa-solid fa-arrow-left"></i> Dashboard
        </a>
        <a href="../api/banners.php" target="_blank" class="btn-sharp btn-sharp-edit">
          <i class="fa-solid fa-code"></i> API JSON
        </a>
        <a href="add_banner.php" class="btn-sharp btn-sharp-primary">
          <i class="fa-solid fa-plus"></i> Upload Canva Banner
        </a>
      </div>
    </header>

    <!-- Main Content Body -->
    <main class="app-content">
      <div class="hero-manager-container">

        <!-- Alerts without border-radius -->
        <?php if ($msg === 'added'): ?>
          <div class="sharp-alert sharp-alert-success">
            <i class="fa-solid fa-circle-check" style="font-size: 1.1rem; color: #059669;"></i>
            <div><strong>Success:</strong> Canva banner uploaded and published to mobile app slider immediately.</div>
          </div>
        <?php elseif ($msg === 'updated'): ?>
          <div class="sharp-alert sharp-alert-info">
            <i class="fa-solid fa-circle-info" style="font-size: 1.1rem; color: #2563eb;"></i>
            <div><strong>Updated:</strong> Hero banner updated successfully.</div>
          </div>
        <?php elseif ($msg === 'deleted'): ?>
          <div class="sharp-alert sharp-alert-danger">
            <i class="fa-solid fa-trash" style="font-size: 1.1rem; color: #dc2626;"></i>
            <div>Hero banner removed from mobile app.</div>
          </div>
        <?php endif; ?>

        <?php if ($error): ?>
          <div class="sharp-alert sharp-alert-danger">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <!-- Sharp & Clean Hero Slider Table Card -->
        <div class="card">
          <div class="card-header">
            <h3>
              <span style="display: inline-block; width: 4px; height: 18px; background: var(--primary);"></span>
              Hero Slider
              <span class="header-tag"><?= $totalBanners ?> Total &bull; <?= $activeCount ?> Live</span>
            </h3>
            <a href="add_banner.php" class="btn-sharp btn-sharp-primary">
              <i class="fa-solid fa-plus"></i> Upload Canva Banner
            </a>
          </div>

          <div class="table-responsive">
            <table class="custom-table">
              <thead>
                <tr>
                  <th style="width: 160px;">Banner Graphic</th>
                  <th>Title &amp; Linked Dish</th>
                  <th style="width: 100px; text-align: center;">Order</th>
                  <th style="width: 140px; text-align: center;">Status</th>
                  <th style="width: 160px; text-align: right;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($banners)): ?>
                  <tr>
                    <td colspan="5" style="text-align: center; padding: 3.5rem; color: #64748b;">
                      <i class="fa-solid fa-image" style="font-size: 2.5rem; opacity: 0.25; margin-bottom: 0.75rem; display: block;"></i>
                      Abhi koi banner nahi hai. Upar diye <strong>"Upload Canva Banner"</strong> button par click karke naya banner add karein.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($banners as $b): ?>
                    <?php 
                      $isActive = (int)$b['status'] === 1;
                      $dishName = $APP_PRODUCTS[$b['product_id']] ?? $b['product_id'];
                    ?>
                    <tr>
                      <!-- Thumbnail (Sharp Rectangular 2:1 Preview) -->
                      <td>
                        <img src="<?= htmlspecialchars($b['image_url']) ?>" alt="Banner" class="banner-row-thumb" loading="lazy" onerror="this.src='https://via.placeholder.com/140x70?text=Banner';">
                      </td>

                      <!-- Title & Meta Row -->
                      <td>
                        <div class="banner-title-text">
                          <?= htmlspecialchars($b['title']) ?>
                        </div>
                        <div class="banner-meta-row">
                          <span class="meta-id-tag">ID #<?= $b['id'] ?></span>
                          <?php if (!empty($dishName)): ?>
                            <span class="meta-dish-tag">
                              <i class="fa-solid fa-utensils"></i> <?= htmlspecialchars($dishName) ?>
                            </span>
                          <?php endif; ?>
                        </div>
                      </td>

                      <!-- Display Order (Sharp Box) -->
                      <td style="text-align: center;">
                        <span class="order-box">
                          <?= (int)$b['display_order'] ?>
                        </span>
                      </td>

                      <!-- Status with 1-Click Sharp Toggle Button -->
                      <td style="text-align: center;">
                        <a href="banners.php?toggle=<?= $b['id'] ?>" class="status-toggle-btn <?= $isActive ? 'active' : 'inactive' ?>" title="Click to toggle active/inactive status">
                          <i class="fa-solid <?= $isActive ? 'fa-circle-check' : 'fa-circle-pause' ?>"></i>
                          <span><?= $isActive ? 'Active' : 'Inactive' ?></span>
                        </a>
                      </td>

                      <!-- Sharp Action Buttons -->
                      <td style="text-align: right;">
                        <div style="display: inline-flex; align-items: center; gap: 8px;">
                          <a href="edit_banner.php?id=<?= $b['id'] ?>" class="btn-sharp btn-sharp-edit" title="Edit banner">
                            <i class="fa-solid fa-pen"></i> Edit
                          </a>
                          <a href="delete_banner.php?id=<?= $b['id'] ?>" class="btn-sharp btn-sharp-delete" title="Delete banner" onclick="return confirm('Kya aap sach me is banner ko delete karna chahte hain?');">
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

  <!-- ======================================================================
       UPLOAD CANVA BANNER MODAL (Sharp & Clean)
       ====================================================================== -->
  <div class="modal-overlay" id="uploadModal" onclick="closeModalOnBackdrop(event)">
    <div class="modal-content" style="max-width: 600px;">
      <div class="modal-header">
        <h3><i class="fa-solid fa-cloud-arrow-up" style="color: var(--primary);"></i> Upload Canva Hero Banner</h3>
        <button class="modal-close" onclick="closeUploadModal()">&times;</button>
      </div>

      <form action="banners.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add_banner">
        
        <div class="card-body" style="padding: 1.5rem;">
          <div class="form-grid">

            <!-- Canva Dropzone File Picker -->
            <div class="form-group full-width">
              <label class="form-label" style="font-weight: 700;">Canva Banner Graphic <span class="req">*</span></label>
              <div class="canva-dropzone" id="dropArea" onclick="document.getElementById('bannerFileInput').click()">
                <div class="dropzone-icon">
                  <i class="fa-solid fa-cloud-arrow-up"></i>
                </div>
                <div class="dropzone-title">Click to Browse or Drag &amp; Drop Canva Image</div>
                <div class="dropzone-hint" style="margin-top: 4px;">
                  Formats: <strong>JPG, PNG only</strong> &bull; Max Size: <strong>3 MB</strong> &bull; Recommended: <strong>1200 &times; 600 px (2:1)</strong>
                </div>
                <input type="file" id="bannerFileInput" name="banner_file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" style="display: none;" onchange="previewSelectedImage(this)">
              </div>

              <!-- Image Preview Box -->
              <div id="previewBox" style="display: none; margin-top: 10px; background: #000; height: 140px; position: relative; border: 1px solid #cbd5e1;">
                <img id="previewImg" src="" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">
                <span id="fileBadge" style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.8); color: #fff; font-size: 0.75rem; padding: 2px 8px;"></span>
              </div>
            </div>



            <!-- Banner Title -->
            <div class="form-group full-width">
              <label class="form-label" style="font-weight: 700;">Banner Title <span class="req">*</span></label>
              <input type="text" name="title" class="form-input" placeholder="e.g. Royal Chicken Dum Biryani Deal" required>
            </div>

            <!-- Dish Selector Dropdown -->
            <div class="form-group full-width">
              <label class="form-label" style="font-weight: 700;">Opens in Mobile App on Tap <span class="req">*</span></label>
              <select name="product_id" class="form-select" required>
                <option value="">-- Select Dish to Open in App --</option>
                <?php foreach ($APP_PRODUCTS as $pId => $pName): ?>
                  <option value="<?= $pId ?>"><?= htmlspecialchars($pName) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Display Order -->
            <div class="form-group">
              <label class="form-label" style="font-weight: 700;">Display Order</label>
              <input type="number" name="display_order" class="form-input" value="1" min="1" max="99">
            </div>

            <!-- Active Status Checkbox -->
            <div class="form-group" style="justify-content: center;">
              <label class="form-label" style="font-weight: 700;">Publish Status</label>
              <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin-top: 8px;">
                <input type="checkbox" name="status" value="1" checked style="width: 18px; height: 18px; accent-color: var(--primary);">
                <span style="font-weight: 700; font-size: 0.9rem;">Active in Mobile App</span>
              </label>
            </div>

          </div>
        </div>

        <div style="padding: 1.25rem 1.5rem; background: #f8fafc; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 10px;">
          <button type="button" class="btn-sharp btn-sharp-edit" onclick="closeUploadModal()">Cancel</button>
          <button type="submit" class="btn-sharp btn-sharp-primary"><i class="fa-solid fa-floppy-disk"></i> Publish Banner</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openUploadModal() {
      document.getElementById('uploadModal').classList.add('open');
    }

    function closeUploadModal() {
      document.getElementById('uploadModal').classList.remove('open');
    }

    function closeModalOnBackdrop(e) {
      if (e.target.id === 'uploadModal') {
        closeUploadModal();
      }
    }

    // Image file selection & validation
    function previewSelectedImage(input) {
      const file = input.files[0];
      if (!file) return;

      const validExtensions = ['jpg', 'jpeg', 'png'];
      const fileExt = file.name.split('.').pop().toLowerCase();

      if (!validExtensions.includes(fileExt)) {
        alert("⚠️ Invalid Format: Sirf JPG aur PNG images allow hain! Kripya Canva se JPG ya PNG download karke upload karein.");
        input.value = '';
        document.getElementById('previewBox').style.display = 'none';
        return;
      }

      if (file.size > 3 * 1024 * 1024) {
        const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
        alert("⚠️ File Size Error: Image ka size " + sizeMb + " MB hai. Maximum allowed size 3 MB hai!");
        input.value = '';
        document.getElementById('previewBox').style.display = 'none';
        return;
      }

      const reader = new FileReader();
      reader.onload = function(e) {
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('fileBadge').textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
        document.getElementById('previewBox').style.display = 'block';
      };
      reader.readAsDataURL(file);
    }

    // Drag and drop setup
    const dropArea = document.getElementById('dropArea');
    if (dropArea) {
      ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, (e) => {
          e.preventDefault();
          dropArea.style.borderColor = 'var(--primary)';
          dropArea.style.backgroundColor = 'var(--primary-light)';
        }, false);
      });
      ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, (e) => {
          e.preventDefault();
          dropArea.style.borderColor = '#cbd5e1';
          dropArea.style.backgroundColor = '#f8fafc';
        }, false);
      });
      dropArea.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files.length > 0) {
          const input = document.getElementById('bannerFileInput');
          input.files = files;
          previewSelectedImage(input);
        }
      }, false);
    }
  </script>
</body>
</html>
