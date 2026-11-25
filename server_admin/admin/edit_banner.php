<?php
require_once __DIR__ . '/config.php';

$error = '';
$db = getDb();

$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM banners WHERE id = ?");
$stmt->execute([$id]);
$banner = $stmt->fetch();

if (!$banner) {
    header('Location: banners.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $productId = trim($_POST['product_id'] ?? '');
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    $status = isset($_POST['status']) ? 1 : 0;
    $imageUrl = $banner['image_url']; // Keep existing image by default

    // Check if new file uploaded
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
            $newFileName = 'banner_' . time() . '_' . rand(100, 999) . '.' . $fileExt;
            $destination = UPLOAD_DIR . $newFileName;
            
            if (move_uploaded_file($fileTmp, $destination)) {
                $imageUrl = UPLOAD_URL . $newFileName;
            } else {
                $error = 'Failed to upload image file to server.';
            }
        }
    }

    if (!$title) {
        $error = 'Please enter a title for the banner.';
    }

    if (!$productId) {
        $error = 'Please select which dish this banner opens in the mobile app.';
    }

    if (!$error) {
        $cols = $db->query("SHOW COLUMNS FROM banners")->fetchAll(PDO::FETCH_COLUMN);
        $hasProdId = in_array('product_id', $cols);
        $hasTargetProdId = in_array('target_product_id', $cols);
        $hasStatus = in_array('status', $cols);
        $hasIsActive = in_array('is_active', $cols);

        $updates = ["title = ?", "image_url = ?", "display_order = ?"];
        $params = [$title, $imageUrl, $displayOrder];

        if ($hasProdId) {
            $updates[] = "product_id = ?";
            $params[] = $productId;
        }
        if ($hasTargetProdId) {
            $updates[] = "target_product_id = ?";
            $params[] = $productId;
        }
        if ($hasStatus) {
            $updates[] = "status = ?";
            $params[] = $status;
        }
        if ($hasIsActive) {
            $updates[] = "is_active = ?";
            $params[] = $status;
        }

        $params[] = $id;
        $sql = "UPDATE banners SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        header('Location: banners.php?msg=updated');
        exit;
    }
}

$currProd = $banner['product_id'] ?? ($banner['target_product_id'] ?? '');
$isActive = (int)($banner['status'] ?? ($banner['is_active'] ?? 1)) === 1;
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
  <title>Edit Hero Banner #<?= $banner['id'] ?> | Crisp &amp; Crave</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="/admin/style.css">
  <style>
<?php if (file_exists(__DIR__ . '/style.css')) { include __DIR__ . '/style.css'; } ?>

    /* ======================================================================
       SHARP, FLAT 2-COLUMN LAYOUT (NO BORDER-RADIUS)
       ====================================================================== */
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
      grid-template-columns: 1fr 340px;
      gap: 24px;
      align-items: start;
    }

    @media (max-width: 900px) {
      .layout-2col {
        grid-template-columns: 1fr;
      }
    }

    /* Cards */
    .card {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
      margin-bottom: 20px;
    }

    .card-header {
      padding: 14px 20px;
      background: #ffffff;
      border-bottom: 1px solid #e2e8f0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .card-header h3 {
      font-size: 1rem;
      font-weight: 700;
      color: #0f172a;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .card-body {
      padding: 20px;
    }

    /* Form Fields */
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

    .form-label .req {
      color: var(--danger);
    }

    .form-input, .form-select {
      width: 100%;
      padding: 10px 14px;
      border: 1px solid #cbd5e1;
      background: #ffffff;
      font-size: 0.9rem;
      color: #0f172a;
      transition: all 0.15s ease;
    }

    .form-input:focus, .form-select:focus {
      outline: 1px solid var(--primary);
      border-color: var(--primary);
      box-shadow: none;
    }

    /* Current Image Preview (Sharp 2:1 Box) */
    .current-graphic-box {
      width: 100%;
      height: 220px;
      background: #000;
      border: 1px solid #cbd5e1;
      overflow: hidden;
      margin-bottom: 14px;
      position: relative;
    }

    .current-graphic-box img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .current-tag {
      position: absolute;
      top: 8px;
      left: 8px;
      background: rgba(0, 0, 0, 0.75);
      color: #fff;
      font-size: 11px;
      font-weight: 700;
      padding: 2px 8px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    /* Canva Dropzone */
    .canva-dropzone {
      border: 2px dashed #cbd5e1;
      background: #f8fafc;
      padding: 1.75rem 1.5rem;
      text-align: center;
      cursor: pointer;
      transition: all 0.15s ease;
    }

    .canva-dropzone:hover {
      border-color: var(--primary);
      background: #fff2ee;
    }

    .dropzone-icon {
      font-size: 2rem;
      color: var(--primary);
      margin-bottom: 6px;
    }

    .dropzone-title {
      font-size: 0.9rem;
      font-weight: 700;
      color: #0f172a;
    }

    .dropzone-hint {
      font-size: 0.75rem;
      color: #64748b;
      margin-top: 4px;
    }

    /* New Image Preview Box */
    .preview-box-container {
      width: 100%;
      height: 180px;
      background: #000;
      border: 1px solid #cbd5e1;
      position: relative;
      margin-top: 12px;
      overflow: hidden;
    }

    .preview-box-container img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .preview-badge {
      position: absolute;
      bottom: 8px;
      right: 8px;
      background: rgba(0, 0, 0, 0.85);
      color: #ffffff;
      font-size: 0.75rem;
      padding: 3px 8px;
      font-family: monospace;
    }

    /* Sharp Buttons */
    .btn-sharp {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 18px;
      font-size: 0.9rem;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.15s ease;
      border: 1px solid transparent;
      box-sizing: border-box;
    }

    .btn-sharp-primary {
      background: var(--primary);
      color: #ffffff;
      border-color: var(--primary);
    }
    .btn-sharp-primary:hover {
      background: var(--primary-hover);
      border-color: var(--primary-hover);
      color: #ffffff;
    }

    .btn-sharp-secondary {
      background: #ffffff;
      color: #0f172a;
      border: 1px solid #cbd5e1;
    }
    .btn-sharp-secondary:hover {
      background: #f8fafc;
      border-color: #94a3b8;
    }

    .btn-sharp-delete {
      background: #ffffff;
      color: #dc2626;
      border: 1px solid #fecaca;
    }
    .btn-sharp-delete:hover {
      background: #dc2626;
      border-color: #dc2626;
      color: #ffffff;
    }

    /* Sharp Alert */
    .sharp-alert-danger {
      background: #fef2f2;
      color: #991b1b;
      border: 1px solid #fecaca;
      border-left: 4px solid #ef4444;
      padding: 12px 18px;
      margin-bottom: 1.5rem;
      font-size: 0.9rem;
    }
  </style>
</head>
<body>

  <!-- ======================================================================
       LEFT SIDEBAR NAVIGATION (CLEAN: ONLY DASHBOARD & HERO SLIDER)
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
      <a href="banners.php" class="nav-link active">
        <i class="fa-solid fa-images"></i>
        <span>Hero Slider</span>
      </a>
      <a href="offers.php" class="nav-link">
        <i class="fa-solid fa-tags"></i>
        <span>Offers</span>
      </a>
      <a href="food_items.php" class="nav-link">
        <i class="fa-solid fa-bowl-food"></i>
        <span>Food Items</span>
        <span class="nav-badge"><?= count($APP_PRODUCTS) ?></span>
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
        <h1 class="topbar-title">Edit Hero Banner #<?= $banner['id'] ?></h1>
      </div>
      <div class="topbar-actions">
        <a href="banners.php" class="btn-sharp btn-sharp-secondary">
          <i class="fa-solid fa-arrow-left"></i> Back to Hero Slider
        </a>
      </div>
    </header>

    <!-- Main Content Area -->
    <main class="app-content">
      <div class="form-container-2col">

        <?php if ($error): ?>
          <div class="sharp-alert-danger">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form action="edit_banner.php?id=<?= $banner['id'] ?>" method="POST" enctype="multipart/form-data">

          <div class="layout-2col">

            <!-- LEFT PRIMARY COLUMN (Content & Graphic) -->
            <div class="col-main">

              <!-- Card 1: Banner Graphic -->
              <div class="card">
                <div class="card-header">
                  <h3><i class="fa-solid fa-image" style="color: var(--primary);"></i> Banner Graphic</h3>
                  <span style="font-size: 11px; font-weight: 700; color: #64748b; background: #f1f5f9; padding: 2px 6px; border: 1px solid #e2e8f0;">Banner #<?= $banner['id'] ?></span>
                </div>
                <div class="card-body">
                  
                  <!-- Current Graphic Preview -->
                  <div class="current-graphic-box">
                    <span class="current-tag"><i class="fa-solid fa-circle-check"></i> Current Live Banner</span>
                    <img src="<?= htmlspecialchars($banner['image_url']) ?>" alt="Current Banner">
                  </div>

                  <!-- Replace with new image -->
                  <div class="canva-dropzone" id="dropArea" onclick="document.getElementById('editBannerInput').click()">
                    <div class="dropzone-icon">
                      <i class="fa-solid fa-cloud-arrow-up"></i>
                    </div>
                    <div class="dropzone-title">Click to Replace Graphic with New Canva Design</div>
                    <div class="dropzone-hint">
                      Formats: <strong>JPG, PNG only</strong> &bull; Max Size: <strong>3 MB</strong> &bull; Ratio: <strong>1200 &times; 600 px (2:1)</strong>
                    </div>
                    <input type="file" id="editBannerInput" name="banner_file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" style="display: none;" onchange="previewEditImage(this)">
                  </div>

                  <!-- New Image Preview Box -->
                  <div id="newPreviewBox" class="preview-box-container" style="display: none;">
                    <img id="newPreviewImg" src="" alt="New Banner Preview">
                    <span id="newFileBadge" class="preview-badge"></span>
                  </div>

                </div>
              </div>

              <!-- Card 2: Banner Details & Mobile App Link -->
              <div class="card">
                <div class="card-header">
                  <h3><i class="fa-solid fa-pen-to-square" style="color: var(--primary);"></i> Banner Details &amp; Mobile App Link</h3>
                </div>
                <div class="card-body">
                  
                  <div class="form-group">
                    <label class="form-label">Banner Title <span class="req">*</span></label>
                    <input type="text" name="title" class="form-input" value="<?= htmlspecialchars($banner['title']) ?>" placeholder="e.g. Royal Chicken Dum Biryani Deal" required>
                  </div>

                  <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Opens in Mobile App on Tap <span class="req">*</span></label>
                    <select name="product_id" class="form-select" required>
                      <?php foreach ($APP_PRODUCTS as $pId => $pName): ?>
                        <option value="<?= $pId ?>" <?= $currProd === $pId ? 'selected' : '' ?>>
                          <?= htmlspecialchars($pName) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <small style="color: #64748b; font-size: 0.78rem; margin-top: 4px;">
                      Customer jab mobile app me is slider par tap karega toh seedhe yeh dish khulegi.
                    </small>
                  </div>

                </div>
              </div>

            </div>

            <!-- RIGHT SIDEBAR COLUMN (Publish Settings) -->
            <div class="col-sidebar">

              <!-- Publish Box -->
              <div class="card">
                <div class="card-header">
                  <h3><i class="fa-solid fa-sliders" style="color: var(--primary);"></i> Publish Settings</h3>
                </div>
                <div class="card-body">
                  
                  <div class="form-group">
                    <label class="form-label">Publish Status</label>
                    <select name="status" class="form-select">
                      <option value="1" <?= $isActive ? 'selected' : '' ?>>Active in Mobile App</option>
                      <option value="0" <?= !$isActive ? 'selected' : '' ?>>Inactive / Hidden</option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label class="form-label">Display Order</label>
                    <input type="number" name="display_order" class="form-input" value="<?= (int)$banner['display_order'] ?>" min="1" max="99">
                    <small style="color: #64748b; font-size: 0.75rem;">1 = sabse pehle dikhega</small>
                  </div>

                  <div style="margin-top: 20px;">
                    <button type="submit" class="btn-sharp btn-sharp-primary" style="width: 100%; justify-content: center; padding: 12px;">
                      <i class="fa-solid fa-floppy-disk"></i> Save &amp; Update Banner
                    </button>
                    
                    <a href="banners.php" class="btn-sharp btn-sharp-secondary" style="width: 100%; justify-content: center; padding: 10px; margin-top: 8px;">
                      Cancel
                    </a>

                    <a href="delete_banner.php?id=<?= $banner['id'] ?>" class="btn-sharp btn-sharp-delete" style="width: 100%; justify-content: center; padding: 10px; margin-top: 12px;" onclick="return confirm('Kya aap sach me is banner ko delete karna chahte hain?');">
                      <i class="fa-solid fa-trash"></i> Delete Banner
                    </a>
                  </div>

                </div>
              </div>

            </div>

          </div>

        </form>

      </div>
    </main>
  </div>

  <script>
    function previewEditImage(input) {
      const file = input.files[0];
      if (!file) return;

      const validExtensions = ['jpg', 'jpeg', 'png'];
      const fileExt = file.name.split('.').pop().toLowerCase();

      if (!validExtensions.includes(fileExt)) {
        alert("⚠️ Invalid Format: Sirf JPG aur PNG images allow hain! Kripya Canva se JPG ya PNG download karke upload karein.");
        input.value = '';
        document.getElementById('newPreviewBox').style.display = 'none';
        return;
      }

      if (file.size > 3 * 1024 * 1024) {
        const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
        alert("⚠️ File Size Error: Image ka size " + sizeMb + " MB hai. Maximum allowed size 3 MB hai!");
        input.value = '';
        document.getElementById('newPreviewBox').style.display = 'none';
        return;
      }

      const reader = new FileReader();
      reader.onload = function(e) {
        document.getElementById('newPreviewImg').src = e.target.result;
        document.getElementById('newFileBadge').textContent = 'New: ' + file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
        document.getElementById('newPreviewBox').style.display = 'block';
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
          dropArea.style.backgroundColor = '#fff2ee';
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
          const input = document.getElementById('editBannerInput');
          input.files = files;
          previewEditImage(input);
        }
      }, false);
    }
  </script>
</body>
</html>
