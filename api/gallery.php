<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add a new image
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        jsonResponse(false, 'Invalid request body', 400);
    }

    $src         = trim($input['src'] ?? '');
    $title       = trim($input['title'] ?? '');
    $category    = trim($input['category'] ?? '');
    $description = trim($input['description'] ?? '');
    $sortOrder   = intval($input['sort_order'] ?? 0);

    if (empty($src)) {
        jsonResponse(false, 'Image URL is required', 400);
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO gallery_images (src, title, category, description, sort_order) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$src, $title, $category, $description, $sortOrder]);
        $newId = $db->lastInsertId();

        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Image added', 'id' => $newId]);
        exit;
    } catch (Exception $e) {
        jsonResponse(false, 'Failed to add image', 500);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Delete an image
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(false, 'Invalid image ID', 400);
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM gallery_images WHERE id = ?");
        $stmt->execute([$id]);
        jsonResponse(true, 'Image deleted');
    } catch (Exception $e) {
        jsonResponse(false, 'Failed to delete image', 500);
    }
}

// GET — fetch images
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

try {
    $db = getDB();

    // Ensure table exists
    $db->exec("CREATE TABLE IF NOT EXISTS gallery_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        src VARCHAR(500) NOT NULL,
        title VARCHAR(255) DEFAULT '',
        category VARCHAR(100) DEFAULT '',
        description TEXT,
        sort_order INT DEFAULT 0,
        is_visible TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    if (!empty($category) && $category !== 'all') {
        $stmt = $db->prepare("SELECT * FROM gallery_images WHERE is_visible = 1 AND category = ? ORDER BY sort_order ASC, id DESC");
        $stmt->execute([$category]);
    } else {
        $stmt = $db->query("SELECT * FROM gallery_images WHERE is_visible = 1 ORDER BY sort_order ASC, id DESC");
    }

    $images = $stmt->fetchAll();

    $formatted = array_map(function($img) {
        return [
            'id'          => (int)$img['id'],
            'src'         => $img['src'],
            'title'       => $img['title'],
            'category'    => $img['category'],
            'description' => $img['description'],
        ];
    }, $images);

    echo json_encode(['success' => true, 'images' => $formatted]);
} catch (Exception $e) {
    echo json_encode(['success' => true, 'images' => []]);
}
?>