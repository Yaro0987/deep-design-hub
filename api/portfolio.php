<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $db = getDB();

    // Ensure table exists
    $db->exec("CREATE TABLE IF NOT EXISTS portfolio (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) DEFAULT '',
        description TEXT,
        image VARCHAR(500) DEFAULT '',
        category VARCHAR(100) DEFAULT '',
        tags VARCHAR(500) DEFAULT '',
        project_url VARCHAR(500) DEFAULT '',
        gallery_images TEXT,
        seo_title VARCHAR(500) DEFAULT '',
        seo_description TEXT,
        seo_keywords VARCHAR(500) DEFAULT '',
        sort_order INT DEFAULT 0,
        is_visible TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Migration: add new columns if they don't exist
    $existingColumns = [];
    $colStmt = $db->query("SHOW COLUMNS FROM portfolio");
    while ($colRow = $colStmt->fetch()) {
        $existingColumns[] = $colRow['Field'];
    }
    $migrations = [
        'slug' => "ALTER TABLE portfolio ADD COLUMN slug VARCHAR(255) DEFAULT '' AFTER title",
        'gallery_images' => "ALTER TABLE portfolio ADD COLUMN gallery_images TEXT AFTER project_url",
        'seo_title' => "ALTER TABLE portfolio ADD COLUMN seo_title VARCHAR(500) DEFAULT '' AFTER gallery_images",
        'seo_description' => "ALTER TABLE portfolio ADD COLUMN seo_description TEXT AFTER seo_title",
        'seo_keywords' => "ALTER TABLE portfolio ADD COLUMN seo_keywords VARCHAR(500) DEFAULT '' AFTER seo_description",
    ];
    foreach ($migrations as $col => $sql) {
        if (!in_array($col, $existingColumns)) {
            $db->exec($sql);
        }
    }
    $hasUniqueSlug = false;
    $indexStmt = $db->query("SHOW INDEX FROM portfolio WHERE Key_name = 'unique_slug'");
    if ($indexStmt->fetch()) $hasUniqueSlug = true;
    if (!$hasUniqueSlug) {
        try { $db->exec("ALTER TABLE portfolio ADD UNIQUE KEY unique_slug (slug)"); } catch (Exception $e) {}
    }

    // Single project by slug
    if (!empty($slug)) {
        $stmt = $db->prepare("SELECT * FROM portfolio WHERE slug = ? AND is_visible = 1");
        $stmt->execute([$slug]);
        $row = $stmt->fetch();

        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Project not found']);
            exit;
        }

        $galleryImages = json_decode($row['gallery_images'], true);
        if (!is_array($galleryImages)) $galleryImages = [];

        $project = [
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'slug' => $row['slug'],
            'description' => $row['description'],
            'image' => $row['image'],
            'category' => $row['category'],
            'tags' => array_values(array_filter(explode(',', $row['tags']))),
            'project_url' => $row['project_url'],
            'gallery_images' => $galleryImages,
            'sort_order' => (int)$row['sort_order'],
            'seo' => [
                'title' => !empty($row['seo_title']) ? $row['seo_title'] : $row['title'] . ' — Deep Design Hubs',
                'description' => !empty($row['seo_description']) ? $row['seo_description'] : $row['description'],
                'keywords' => !empty($row['seo_keywords']) ? $row['seo_keywords'] : $row['title'] . ', deep design hubs, web development, design project',
            ],
        ];

        echo json_encode(['success' => true, 'project' => $project]);
        exit;
    }

    // All projects
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
        $galleryImages = json_decode($row['gallery_images'], true);
        if (!is_array($galleryImages)) $galleryImages = [];
        return [
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'slug' => $row['slug'],
            'description' => $row['description'],
            'image' => $row['image'],
            'category' => $row['category'],
            'tags' => array_values(array_filter(explode(',', $row['tags']))),
            'project_url' => $row['project_url'],
            'gallery_images' => $galleryImages,
            'sort_order' => (int)$row['sort_order'],
        ];
    }, $rows);

    echo json_encode(['success' => true, 'projects' => $items]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'projects' => [], 'message' => $e->getMessage()]);
}
