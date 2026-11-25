<?php
require_once __DIR__ . '/config.php';

$db = getDb();
$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        // Fetch image_url to unlink file if local
        $stmt = $db->prepare("SELECT image_url FROM banners WHERE id = ?");
        $stmt->execute([$id]);
        $banner = $stmt->fetch();

        if ($banner && !empty($banner['image_url'])) {
            $imgUrl = $banner['image_url'];
            if (strpos($imgUrl, 'uploads/banners/') !== false) {
                $filename = basename($imgUrl);
                $filePath = UPLOAD_DIR . $filename;
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
        }

        // Delete from database
        $stmt = $db->prepare("DELETE FROM banners WHERE id = ?");
        $stmt->execute([$id]);
    } catch (Exception $e) {
        // Ignore deletion errors
    }
}

header('Location: banners.php?msg=deleted');
exit;
