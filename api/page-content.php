<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$page = $_GET['page'] ?? '';
$validPages = ['home','about','service','contact'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $db = getDB();
    $input = json_decode(file_get_contents('php://input'), true);
    $pageSlug = $input['page'] ?? '';
    $content = $input['content'] ?? null;

    if (!in_array($pageSlug, $validPages) || $content === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid page or content']);
        exit;
    }

    $stmt = $db->prepare("INSERT INTO pages (page_slug, content) VALUES (?, ?) ON DUPLICATE KEY UPDATE content = ?");
    $json = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $stmt->execute([$pageSlug, $json, $json]);
    echo json_encode(['success' => true, 'message' => 'Page content saved']);
    exit;
}

if (!$page || !in_array($page, $validPages)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid page parameter']);
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT content FROM pages WHERE page_slug = ?");
$stmt->execute([$page]);
$row = $stmt->fetch();

if ($row) {
    $decoded = json_decode($row['content'], true);
    if ($decoded === null) {
        $decoded = ['raw' => $row['content']];
    }
    echo json_encode(['success' => true, 'page' => $page, 'content' => $decoded]);
} else {
    echo json_encode(['success' => false, 'message' => 'No content found for this page', 'page' => $page]);
}
