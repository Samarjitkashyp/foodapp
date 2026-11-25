<?php
require_once __DIR__ . '/config.php';

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $db = getDb();
        $stmt = $db->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: coupons.php?msg=deleted');
        exit;
    } catch (Exception $e) {
        die('Error deleting coupon: ' . $e->getMessage());
    }
}

header('Location: coupons.php');
exit;
