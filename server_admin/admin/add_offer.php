<?php
require_once __DIR__ . '/config.php';

$db = getDb();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_offer') {
    $title = trim($_POST['title'] ?? '');
    $productId = trim($_POST['product_id'] ?? '');
    $displayOrder = (int)($_POST['display_order'] ?? 1);
    $status = isset($_POST['status']) ? 1 : 0;
    $imageUrl = trim($_POST['image_url'] ?? '');

    // Check File Upload
    if (isset($_FILES['offer_file']) && $_FILES['offer_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['offer_file']['tmp_name'];
        $fileName = $_FILES['offer_file']['name'];
        $fileSize = $_FILES['offer_file']['size'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];

        if (!in_array($fileExt, $allowed)) {
            $error = 'Invalid image format. Sirf JPG aur PNG format allow hai.';
        } elseif ($fileSize > 3 * 1024 * 1024) {
            $error = 'Image size bohot bada hai (' . round($fileSize / (1024 * 1024), 2) . ' MB). Maximum allowed size 3 MB hai!';
        } else {
            if (!is_dir(OFFERS_UPLOAD_DIR)) {
                mkdir(OFFERS_UPLOAD_DIR, 0755, true);
            }
            $newFileName = 'offer_' . time() . '_' . rand(100, 999) . '.' . $fileExt;
            $destination = OFFERS_UPLOAD_DIR . $newFileName;
            if (move_uploaded_file($fileTmp, $destination)) {
                $imageUrl = OFFERS_UPLOAD_URL . $newFileName;
            } else {
                $error = 'Failed to save uploaded offer image on server.';
            }
        }
    }

    if (!$imageUrl && !$error) {
        $error = 'Please upload a Canva offer design image (JPG/PNG under 3 MB).';
    }
    if (!$title) {
        $error = 'Please enter a title for the offer.';
    }

    if (!$error && $imageUrl) {
        try {
            $stmt = $db->prepare("INSERT INTO offers (title, image_url, product_id, display_order, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $imageUrl, $productId ?: null, $displayOrder, $status]);

            header('Location: offers.php?msg=added');
            exit;
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

try {
    $activeBanners = (int)$db->query("SELECT COUNT(*) FROM banners WHERE status = 1")->fetchColumn();
    $activeOffers = (int)$db->query("SELECT COUNT(*) FROM offers WHERE status = 1")->fetchColumn();
    $totalFood = (int)$db->query("SELECT COUNT(*) FROM food_items WHERE status = 1")->fetchColumn();
    $totalCats = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $nextOrder = (int)$db->query("SELECT COUNT(*) FROM offers")->fetchColumn() + 1;
} catch (Exception $e) {
    $activeBanners = 0;
    $activeOffers = 0;
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
  <title>Add Offer Banner | Crisp &amp; Crave</title>
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
      max-width: 1200px;
      margin: 0 auto;
    }

    .layout-2col {
      display: grid;
      grid-template-columns: 360px 1fr;
      gap: 24px;
      align-items: start;
    }

    @media (max-width: 900px) {
      .layout-2col {
        grid-template-columns: 1fr;
      }
    }

    .card {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }

    .card-header {
      padding: 16px 20px;
      background: #ffffff;
      border-bottom: 1px solid #e2e8f0;
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
      margin-bottom: 18px;
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .form-label {
      font-size: 0.85rem;
      font-weight: 700;
      color: #0f172a;
    }

    .form-input, .form-select {
      width: 100%;
      padding: 10px 14px;
      border: 1px solid #cbd5e1;
      background: #ffffff;
      font-size: 0.9rem;
      color: #0f172a;
    }

    .canva-dropzone {
      border: 2px dashed #cbd5e1;
      background: #f8fafc;
      padding: 2rem 1.5rem;
      text-align: center;
      cursor: pointer;
    }

    .canva-dropzone:hover {
      border-color: #f97316;
      background: #fff7ed;
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
      color: #ffffff;
      border-color: #ea580c;
    }
    .btn-sharp-primary:hover {
      background: #ea580c;
    }

    .btn-sharp-secondary {
      background: #ffffff;
      color: #0f172a;
      border: 1px solid #cbd5e1;
    }

    .sharp-alert-danger {
      background: #fef2f2;
      color: #991b1b;
      border: 1px solid #fecaca;
      border-left: 4px solid #ef4444;
      padding: 12px 18px;
      margin-bottom: 1.5rem;
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
      <a href="banners.php" class="nav-link">
        <i class="fa-solid fa-images"></i>
        <span>Hero Slider</span>
        <span class="nav-badge"><?= $activeBanners ?> Live</span>
      </a>
      <a href="offers.php" class="nav-link active">
        <i class="fa-solid fa-tags"></i>
        <span>Offers</span>
        <span class="nav-badge"><?= $activeOffers ?> Live</span>
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
    <header class="app-topbar">
      <div class="topbar-left">
        <div class="topbar-title">
          <h1>Add Offer Banner</h1>
          <p>Upload Canva Offer Graphic for Mobile App Slider</p>
        </div>
      </div>
      <div class="topbar-actions">
        <a href="offers.php" class="btn-sharp btn-sharp-secondary">
          <i class="fa-solid fa-arrow-left"></i> Back to Offers
        </a>
      </div>
    </header>

    <main class="app-content">
      <div class="form-container-2col">

        <?php if ($error): ?>
          <div class="sharp-alert-danger">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form action="add_offer.php" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="add_offer">

          <div class="layout-2col">
            <!-- Left Column: Upload Box -->
            <div class="card">
              <div class="card-header">
                <h3><i class="fa-solid fa-image" style="color: #f97316;"></i> Offer Graphic</h3>
              </div>
              <div class="card-body">
                <div class="canva-dropzone" onclick="document.getElementById('offerFileInput').click()">
                  <i class="fa-solid fa-cloud-arrow-up" style="font-size: 2rem; color: #f97316; margin-bottom: 8px;"></i>
                  <div style="font-weight: 700; color: #0f172a;">Click to select Canva design</div>
                  <div style="font-size: 0.8rem; color: #64748b; margin-top: 4px;">JPG or PNG &bull; Under 3 MB</div>
                  <input type="file" id="offerFileInput" name="offer_file" accept=".jpg,.jpeg,.png" style="display: none;" onchange="previewOfferFile(this)">
                </div>

                <div id="offerPreviewContainer" style="display: none; width: 100%; height: 160px; background: #000; border: 1px solid #cbd5e1; margin-top: 14px; overflow: hidden;">
                  <img id="offerPreviewImg" src="" style="width: 100%; height: 100%; object-fit: cover;">
                </div>

                <div style="padding: 12px; background: #f8fafc; border: 1px solid #e2e8f0; font-size: 0.82rem; color: #475569; line-height: 1.5; margin-top: 14px;">
                  <strong><i class="fa-solid fa-circle-info" style="color: #f97316;"></i> Mobile App Sync:</strong>
                  Offer banner publish hote hi Flutter app ke Offers carousel me turant live show ho jayega.
                </div>
              </div>
            </div>

            <!-- Right Column: Settings -->
            <div class="card">
              <div class="card-header">
                <h3><i class="fa-solid fa-sliders" style="color: #f97316;"></i> Offer Settings</h3>
              </div>
              <div class="card-body">
                
                <div class="form-group">
                  <label class="form-label">Offer Title <span style="color: #dc2626;">*</span></label>
                  <input type="text" name="title" class="form-input" placeholder="e.g. Weekend Special 40% OFF" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                </div>

                <div class="form-group">
                  <label class="form-label">Target Dish / Combo on Tap</label>
                  <select name="product_id" class="form-select">
                    <option value="">None (View Only / Informational)</option>
                    <?php foreach ($APP_PRODUCTS as $pId => $pTitle): ?>
                      <option value="<?= $pId ?>"><?= htmlspecialchars($pTitle) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                  <div class="form-group">
                    <label class="form-label">Display Order</label>
                    <input type="number" name="display_order" class="form-input" value="<?= $nextOrder ?>" min="1" max="99">
                  </div>
                  <div class="form-group" style="padding-top: 26px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                      <input type="checkbox" name="status" value="1" checked style="width: 18px; height: 18px; accent-color: #f97316;">
                      <span><strong>Active in Mobile App</strong></span>
                    </label>
                  </div>
                </div>

                <div style="border-top: 1px solid #e2e8f0; padding-top: 18px; margin-top: 20px; display: flex; gap: 12px; justify-content: flex-end;">
                  <a href="offers.php" class="btn-sharp btn-sharp-secondary">Cancel</a>
                  <button type="submit" class="btn-sharp btn-sharp-primary"><i class="fa-solid fa-floppy-disk"></i> Publish Offer Banner</button>
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
function previewOfferFile(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('offerPreviewImg').src = e.target.result;
      document.getElementById('offerPreviewContainer').style.display = 'block';
    }
    reader.readAsDataURL(input.files[0]);
  }
}
</script>
</body>
</html>
