<?php
require_once __DIR__ . '/config.php';

$db = getDb();
$error = '';
$id = trim($_GET['id'] ?? '');

if (empty($id)) {
    header('Location: food_items.php');
    exit;
}

// Fetch Existing Food Item
try {
    $stmt = $db->prepare("SELECT * FROM food_items WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if (!$item) {
        header('Location: food_items.php');
        exit;
    }
} catch (Exception $e) {
    die('Error fetching food item: ' . $e->getMessage());
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_food_item') {
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? 'chicken');
    $price = (float)($_POST['price'] ?? 0);
    $origPrice = !empty($_POST['original_price']) ? (float)$_POST['original_price'] : null;
    $isVeg = isset($_POST['is_veg']) && $_POST['is_veg'] == '1' ? 1 : 0;
    $spicyLevel = (int)($_POST['spicy_level'] ?? 1);
    $prepTime = trim($_POST['prep_time'] ?? '15-20 min');
    $portion = trim($_POST['portion'] ?? '');
    $calories = trim($_POST['calories'] ?? '');
    $badge = trim($_POST['badge'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = isset($_POST['status']) ? 1 : 0;
    $imagePath = $item['image']; // Keep existing by default

    // Addons parsing
    $addonsList = [];
    if (isset($_POST['addon_name']) && is_array($_POST['addon_name'])) {
        $addonNames = $_POST['addon_name'];
        $addonPrices = $_POST['addon_price'] ?? [];
        for ($i = 0; $i < count($addonNames); $i++) {
            $aName = trim($addonNames[$i]);
            $aPrice = (float)($addonPrices[$i] ?? 0);
            if (!empty($aName)) {
                $addonsList[] = [
                    'name' => $aName,
                    'price' => $aPrice
                ];
            }
        }
    }
    $addonsJson = !empty($addonsList) ? json_encode($addonsList, JSON_UNESCAPED_UNICODE) : null;

    if (empty($name)) {
        $error = 'Dish name zaroori hai.';
    } elseif ($price <= 0) {
        $error = 'Valid price enter karein (greater than 0).';
    }

    // Check if new image uploaded
    if (!$error && isset($_FILES['item_file']) && $_FILES['item_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['item_file']['tmp_name'];
        $fileName = $_FILES['item_file']['name'];
        $fileSize = $_FILES['item_file']['size'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];

        if (!in_array($fileExt, $allowed)) {
            $error = 'Invalid image format. Sirf JPG aur PNG format allow hain.';
        } elseif ($fileSize > 3 * 1024 * 1024) {
            $error = 'Image size 3 MB se zyada nahi hona chahiye.';
        } else {
            if (!is_dir(FOOD_UPLOAD_DIR)) {
                mkdir(FOOD_UPLOAD_DIR, 0755, true);
            }
            $newFileName = 'dish_' . time() . '_' . rand(100, 999) . '.' . $fileExt;
            $destination = FOOD_UPLOAD_DIR . $newFileName;
            if (move_uploaded_file($fileTmp, $destination)) {
                $imagePath = 'uploads/food/' . $newFileName;
            } else {
                $error = 'Server par image save karne me dikkat aayi.';
            }
        }
    }

    if (!$error) {
        try {
            $sql = "UPDATE food_items SET 
                name = ?, category = ?, price = ?, original_price = ?, is_veg = ?, 
                spicy_level = ?, prep_time = ?, portion = ?, addons = ?, calories = ?, badge = ?, image = ?, 
                description = ?, status = ? 
                WHERE id = ?";
            $upStmt = $db->prepare($sql);
            $upStmt->execute([
                $name, $category, $price, $origPrice, $isVeg,
                $spicyLevel, $prepTime, $portion ?: null, $addonsJson, $calories, $badge ?: null, $imagePath,
                $description, $status, $id
            ]);

            header('Location: food_items.php?msg=updated');
            exit;
        } catch (Exception $e) {
            $error = 'Database update error: ' . $e->getMessage();
        }
    }
}

// Counts for sidebar badges
$totalBanners = (int)$db->query("SELECT COUNT(*) FROM banners")->fetchColumn();
$activeBanners = (int)$db->query("SELECT COUNT(*) FROM banners WHERE status = 1")->fetchColumn();
$totalFood = (int)$db->query("SELECT COUNT(*) FROM food_items")->fetchColumn();
$activeFood = (int)$db->query("SELECT COUNT(*) FROM food_items WHERE status = 1")->fetchColumn();
try {
    $totalCats = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
} catch (Exception $e) {
    $totalCats = count($FOOD_CATEGORIES);
}

$currentImgUrl = getFoodImageUrl($item['image']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Food Item: <?= htmlspecialchars($item['name']) ?> - Crisp &amp; Crave</title>
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
      max-width: 1240px;
      margin: 0 auto;
    }
    .grid-2col {
      display: grid;
      grid-template-columns: 380px 1fr;
      gap: 24px;
      align-items: start;
    }
    @media (max-width: 900px) {
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
    .current-image-box {
      border: 1px solid #e2e8f0;
      background: #0f172a;
      text-align: center;
      margin-bottom: 16px;
      position: relative;
    }
    .current-image-box img {
      width: 100%;
      max-height: 200px;
      object-fit: contain;
      display: block;
    }
    .dropzone-box {
      border: 2px dashed #cbd5e1;
      padding: 1.5rem 1rem;
      text-align: center;
      background: #f8fafc;
      cursor: pointer;
      transition: all 0.2s;
    }
    .dropzone-box:hover {
      border-color: #f97316;
      background: #fff7ed;
    }
    .dropzone-box i {
      font-size: 2rem;
      color: #f97316;
      margin-bottom: 0.5rem;
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
    .form-input, .form-select, .form-textarea {
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
    .form-input:focus, .form-select:focus, .form-textarea:focus {
      border-color: #f97316;
    }
    .form-input[readonly] {
      background: #f1f5f9;
      color: #64748b;
      cursor: not-allowed;
    }
    .form-row-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }
    .form-row-3 {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 16px;
    }
    @media (max-width: 600px) {
      .form-row-2, .form-row-3 {
        grid-template-columns: 1fr;
      }
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

  <!-- MAIN CONTENT -->
  <div class="app-main">
    <header class="app-topbar">
      <div class="topbar-left">
        <div class="topbar-title">
          <h1>Edit Food Item</h1>
          <p><?= htmlspecialchars($item['name']) ?> (ID: #<?= htmlspecialchars($item['id']) ?>)</p>
        </div>
      </div>
      <div class="topbar-actions">
        <a href="food_items.php" class="btn-sharp btn-sharp-edit">
          <i class="fa-solid fa-arrow-left"></i> Back to Food Items
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

        <form action="edit_food_item.php?id=<?= urlencode($id) ?>" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="edit_food_item">

          <div class="grid-2col">
            <!-- Left Column: Photo & Replace -->
            <div class="card">
              <div class="card-header">
                <h3><i class="fa-solid fa-image" style="color: #f97316;"></i> Dish Photo</h3>
              </div>
              <div class="card-body">
                
                <div style="font-size: 0.8rem; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 8px;">Current Active Image</div>
                <div class="current-image-box">
                  <img src="<?= htmlspecialchars($currentImgUrl) ?>" alt="Current Dish Photo" onerror="this.src='https://via.placeholder.com/300x200?text=Food';">
                  <span style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.75); color: #fff; font-size: 0.72rem; padding: 2px 8px;">Active</span>
                </div>

                <div style="font-size: 0.8rem; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 8px;">Replace Dish Photo (Optional)</div>
                <div class="dropzone-box" id="dropArea" onclick="document.getElementById('dishFileInput').click()">
                  <i class="fa-solid fa-cloud-arrow-up"></i>
                  <div style="font-weight: 700; font-size: 0.9rem; color: #0f172a; margin-bottom: 4px;">Click to Choose New Image</div>
                  <div style="font-size: 0.75rem; color: #64748b;">
                    JPG, PNG under 3 MB
                  </div>
                  <input type="file" id="dishFileInput" name="item_file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" style="display: none;" onchange="previewImage(this)">
                </div>

                <!-- Preview of newly selected image -->
                <div id="newPreviewArea" style="display: none; margin-top: 14px; border: 1px solid #f97316; background: #000; text-align: center; position: relative;">
                  <img id="newPreviewImg" src="" alt="New Preview" style="max-height: 180px; width: 100%; object-fit: contain;">
                  <span id="newFileBadge" style="position: absolute; bottom: 8px; right: 8px; background: #ea580c; color: #fff; font-size: 0.72rem; padding: 2px 8px;">New Selection</span>
                </div>

              </div>
            </div>

            <!-- Right Column: Details & Pricing -->
            <div class="card">
              <div class="card-header">
                <h3><i class="fa-solid fa-pen-to-square" style="color: #f97316;"></i> Edit Dish Information</h3>
              </div>
              <div class="card-body">
                
                <!-- Food Name -->
                <div class="form-group">
                  <label>Dish Name <span class="req">*</span></label>
                  <input type="text" name="name" class="form-input" required value="<?= htmlspecialchars($item['name']) ?>">
                </div>

                <!-- Item ID & Category -->
                <div class="form-row-2">
                  <div class="form-group">
                    <label>Item ID / Code (System Locked)</label>
                    <input type="text" class="form-input" value="<?= htmlspecialchars($item['id']) ?>" readonly>
                  </div>
                  <div class="form-group">
                    <label>Category <span class="req">*</span></label>
                    <select name="category" class="form-select" required>
                      <?php foreach ($FOOD_CATEGORIES as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $item['category'] === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>

                <!-- Price & Original Price -->
                <div class="form-row-2">
                  <div class="form-group">
                    <label>Selling Price (₹) <span class="req">*</span></label>
                    <input type="number" step="1" name="price" class="form-input" required value="<?= (int)$item['price'] ?>">
                  </div>
                  <div class="form-group">
                    <label>Original Price (₹) <span style="font-weight: 400; color: #64748b;">(Strikethrough)</span></label>
                    <input type="number" step="1" name="original_price" class="form-input" value="<?= !empty($item['original_price']) ? (int)$item['original_price'] : '' ?>">
                  </div>
                </div>

                <!-- Veg/Non-Veg & Spicy Level & Prep Time -->
                <div class="form-row-3">
                  <div class="form-group">
                    <label>Food Type <span class="req">*</span></label>
                    <select name="is_veg" class="form-select">
                      <option value="0" <?= (int)$item['is_veg'] === 0 ? 'selected' : '' ?>>🍗 Non-Vegetarian</option>
                      <option value="1" <?= (int)$item['is_veg'] === 1 ? 'selected' : '' ?>>🥬 Pure Vegetarian</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Spicy Level</label>
                    <select name="spicy_level" class="form-select">
                      <option value="1" <?= (int)$item['spicy_level'] === 1 ? 'selected' : '' ?>>Mild (1)</option>
                      <option value="2" <?= (int)$item['spicy_level'] === 2 ? 'selected' : '' ?>>Medium Spicy (2)</option>
                      <option value="3" <?= (int)$item['spicy_level'] === 3 ? 'selected' : '' ?>>Extra Fiery Hot (3)</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Prep Time</label>
                    <input type="text" name="prep_time" class="form-input" value="<?= htmlspecialchars($item['prep_time'] ?? '15-20 min') ?>">
                  </div>
                </div>

                <!-- Portion Size & Calories & Badge -->
                <div class="form-row-3">
                  <div class="form-group">
                    <label>Pieces / Portion Size <span style="font-weight: 400; color: #f97316;">(App me tile ke niche dikhega)</span></label>
                    <input type="text" name="portion" class="form-input" placeholder="e.g. 4 Pcs, 8 Pcs, 1 Plate, 1 Pc" value="<?= htmlspecialchars($item['portion'] ?? '') ?>">
                  </div>
                  <div class="form-group">
                    <label>Calories (Optional)</label>
                    <input type="text" name="calories" class="form-input" value="<?= htmlspecialchars($item['calories'] ?? '') ?>">
                  </div>
                  <div class="form-group">
                    <label>Special Badge (Optional)</label>
                    <input type="text" name="badge" class="form-input" placeholder="e.g. Bestseller / Royal Special" value="<?= htmlspecialchars($item['badge'] ?? '') ?>">
                  </div>
                </div>

                <!-- Description -->
                <div class="form-group">
                  <label>Description &amp; Ingredients</label>
                  <textarea name="description" class="form-textarea" rows="3"><?= htmlspecialchars($item['description'] ?? '') ?></textarea>
                </div>

                <!-- Food Addons Builder -->
                <div class="form-group" style="margin-top: 14px; border: 1px solid #e2e8f0; padding: 16px; background: #fafafa;">
                  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <div>
                      <strong style="color: #0f172a; font-size: 0.95rem;"><i class="fa-solid fa-plus-circle" style="color: #f97316;"></i> Product Addons (Optional)</strong>
                      <p style="margin: 3px 0 0; font-size: 0.78rem; color: #64748b;">Agar is dish me extra cheese, dip, extra sauce ya sides available hain toh yahan add karein. Agar koi addon nahi hoga toh app me ye section nahi dikhega.</p>
                    </div>
                    <button type="button" class="btn-sharp btn-sharp-edit" onclick="addAddonRow()" style="padding: 6px 12px; font-size: 0.8rem; background: #ffffff;">
                      <i class="fa-solid fa-plus"></i> + Add Addon
                    </button>
                  </div>

                  <div id="addonsContainer" style="display: flex; flex-direction: column; gap: 8px;">
                    <!-- Addon Rows injected via JS -->
                  </div>
                </div>

                <!-- Status Checkbox -->
                <div class="form-group" style="padding-top: 6px;">
                  <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; text-transform: none; font-size: 0.95rem;">
                    <input type="checkbox" name="status" value="1" <?= (int)$item['status'] === 1 ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: #f97316;">
                    <span><strong>Active in Mobile App</strong> (Dish customer app me available rahegi)</span>
                  </label>
                </div>

                <div style="border-top: 1px solid #e2e8f0; padding-top: 18px; margin-top: 20px; display: flex; gap: 12px; justify-content: flex-end;">
                  <a href="food_items.php" class="btn-sharp btn-sharp-edit">Cancel</a>
                  <button type="submit" class="btn-sharp btn-sharp-primary"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
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
function previewImage(input) {
  const file = input.files[0];
  if (!file) return;

  const validExts = ['jpg', 'jpeg', 'png'];
  const ext = file.name.split('.').pop().toLowerCase();

  if (!validExts.includes(ext)) {
    alert("Sirf JPG aur PNG format allow hain!");
    input.value = '';
    document.getElementById('newPreviewArea').style.display = 'none';
    return;
  }

  if (file.size > 3 * 1024 * 1024) {
    alert("File size 3 MB se zyada nahi hona chahiye!");
    input.value = '';
    document.getElementById('newPreviewArea').style.display = 'none';
    return;
  }

  const reader = new FileReader();
  reader.onload = function(e) {
    document.getElementById('newPreviewImg').src = e.target.result;
    document.getElementById('newFileBadge').textContent = file.name + ' (' + (file.size/1024).toFixed(1) + ' KB)';
    document.getElementById('newPreviewArea').style.display = 'block';
  };
  reader.readAsDataURL(file);
}

// Drag & Drop
const dropArea = document.getElementById('dropArea');
if (dropArea) {
  ['dragenter', 'dragover'].forEach(name => {
    dropArea.addEventListener(name, (e) => {
      e.preventDefault();
      dropArea.style.borderColor = '#f97316';
      dropArea.style.background = '#fff7ed';
    });
  });
  ['dragleave', 'drop'].forEach(name => {
    dropArea.addEventListener(name, (e) => {
      e.preventDefault();
      dropArea.style.borderColor = '#cbd5e1';
      dropArea.style.background = '#f8fafc';
    });
  });
  dropArea.addEventListener('drop', (e) => {
    const dt = e.dataTransfer;
    if (dt && dt.files && dt.files.length > 0) {
      const fileInput = document.getElementById('dishFileInput');
      fileInput.files = dt.files;
      previewImage(fileInput);
    }
  });
}
function addAddonRow(name = '', price = '') {
  const container = document.getElementById('addonsContainer');
  const div = document.createElement('div');
  div.style.display = 'grid';
  div.style.gridTemplateColumns = '2fr 1fr 40px';
  div.style.gap = '8px';
  div.style.alignItems = 'center';
  div.innerHTML = `
    <input type="text" name="addon_name[]" class="form-input" placeholder="Addon Name (e.g. Extra Cheddar Cheese)" value="${name}">
    <div style="display: flex; align-items: center; gap: 4px;">
      <span style="font-size: 0.85rem; font-weight: 700; color: #64748b;">₹</span>
      <input type="number" step="1" name="addon_price[]" class="form-input" placeholder="Price (e.g. 30)" value="${price}">
    </div>
    <button type="button" onclick="this.parentElement.remove()" title="Delete" style="background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; height: 38px; cursor: pointer; display: flex; align-items: center; justify-content: center;">
      <i class="fa-solid fa-trash"></i>
    </button>
  `;
  container.appendChild(div);
}

// Pre-fill existing addons
<?php 
  $existingAddons = !empty($item['addons']) ? json_decode($item['addons'], true) : [];
  if (!is_array($existingAddons)) $existingAddons = [];
?>
const existingAddons = <?= json_encode($existingAddons) ?>;
if (Array.isArray(existingAddons) && existingAddons.length > 0) {
  existingAddons.forEach(ad => {
    addAddonRow(ad.name || '', ad.price || '');
  });
}
</script>
</body>
</html>
