<?php
require_once __DIR__ . '/config.php';

$id = trim($_GET['id'] ?? '');
if (!empty($id)) {
    try {
        $db = getDb();
        // Check if any food items are linked
        $check = $db->prepare("SELECT COUNT(*) FROM food_items WHERE category = ?");
        $check->execute([$id]);
        $linkedCount = (int)$check->fetchColumn();

        if ($linkedCount > 0 && !isset($_GET['force'])) {
            header('Location: categories.php?error=' . urlencode("Yeh category delete nahi ki ja sakti kyunki isme $linkedCount dishes linked hain. Pehle un dishes ki category change karein."));
            exit;
        }

        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: categories.php?msg=deleted');
        exit;
    } catch (Exception $e) {
        die('Error deleting category: ' . $e->getMessage());
    }
}
header('Location: categories.php');
exit;
