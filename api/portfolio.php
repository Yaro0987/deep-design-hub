<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $db = getDB();

    // Ensure table exists
    $db->exec("CREATE TABLE IF NOT EXISTS portfolio (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        image VARCHAR(500) DEFAULT '',
        category VARCHAR(100) DEFAULT '',
        tags VARCHAR(500) DEFAULT '',
        project_url VARCHAR(500) DEFAULT '',
        sort_order INT DEFAULT 0,
        is_visible TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $where = 'WHERE is_visible = 1';
    $params = [];

    if (!empty($category) && $category !== 'all') {
        $where .= ' AND category = ?';
        $params[] = $category;
    }
    if (!empty($search)) {
        $where .= ' AND (title LIKE ? OR description LIKE ? OR tags LIKE ?)';
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $stmt = $db->prepare("SELECT * FROM portfolio $where ORDER BY sort_order ASC, id DESC");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $items = array_map(function($row) {
        return [
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'image' => $row['image'],
            'category' => $row['category'],
            'tags' => array_values(array_filter(explode(',', $row['tags']))),
            'project_url' => $row['project_url'],
            'sort_order' => (int)$row['sort_order'],
        ];
    }, $rows);

    echo json_encode(['success' => true, 'projects' => $items]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'projects' => [], 'message' => $e->getMessage()]);
}
