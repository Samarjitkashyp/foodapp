<?php
require_once __DIR__ . '/config.php';

$id = trim($_GET['id'] ?? '');
if (!empty($id)) {
    try {
        $db = getDb();
        $stmt = $db->prepare("DELETE FROM food_items WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: food_items.php?msg=deleted');
        exit;
    } catch (Exception $e) {
        die('Error deleting food item: ' . $e->getMessage());
    }
}
header('Location: food_items.php');
exit;
