<?php
session_start();
require 'admin/db.connect.php';

if (!isset($_GET['id'])) {
    header("Location: Manager-JobPosting.php?error=missing-id");
    exit;
}

$id = intval($_GET['id']);

// Delete vacancy only if it exists
$stmt = $conn->prepare("DELETE FROM vacancies WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: Manager-JobPosting.php?deleted=1");
} else {
    header("Location: Manager-JobPosting.php?error=db");
}

exit;
?>