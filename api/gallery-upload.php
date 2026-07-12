<?php
require_once __DIR__ . '/config.php';
session_start();

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?tab=gallery');
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    header('Location: index.php?tab=gallery');
    exit;
}

$file = $_FILES['image'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if (!in_array($ext, $allowed)) {
    header('Location: index.php?tab=gallery');
    exit;
}

if ($file['size'] > 10 * 1024 * 1024) {
    header('Location: index.php?tab=gallery');
    exit;
}

$filename = uniqid('img_', true) . '.' . $ext;
$dest = __DIR__ . '/uploads/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    header('Location: index.php?tab=gallery');
    exit;
}

$db = getDB();
$title = trim($_POST['title'] ?? '');
$category = trim($_POST['category'] ?? 'uncategorized');
$description = trim($_POST['description'] ?? '');
$sort_order = (int)($_POST['sort_order'] ?? 0);

$db->prepare("INSERT INTO gallery_images (src, title, category, description, sort_order, is_visible) VALUES (?, ?, ?, ?, ?, 1)")
   ->execute(['uploads/' . $filename, $title, $category, $description, $sort_order]);

header('Location: index.php?tab=gallery&msg=uploaded');
exit;
