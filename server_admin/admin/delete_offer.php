<?php
require_once __DIR__ . '/config.php';

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    try {
        $db = getDb();
        $stmt = $db->prepare("DELETE FROM offers WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: offers.php?msg=deleted');
        exit;
    } catch (Exception $e) {
        die('Error deleting offer: ' . $e->getMessage());
    }
}
header('Location: offers.php');
exit;
