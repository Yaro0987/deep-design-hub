<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

// ========== DATABASE CONFIG ==========
$hostname = gethostname();
$isLocal = ($hostname === 'DESKTOP-DG2C91L' || $_SERVER['SERVER_NAME'] === 'localhost' || str_contains($_SERVER['SERVER_NAME'], 'localhost'));

if ($isLocal) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'deepdesign');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'smalvcae_deepdesin');
    define('DB_USER', 'smalvcae_deepdesin');
    define('DB_PASS', 'smalvcae_deepdesin');
}

// ========== ADMIN CREDENTIALS ==========
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'deepdesign2026');

// ========== SMTP CONFIG ==========
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'abubakarmusa0987@gmail.com');
define('SMTP_PASS', 'azcn nddg gwbt tkgr');
define('SMTP_FROM_NAME', 'Deep Design Hub');
define('SMTP_FROM_EMAIL', 'abubakarmusa0987@gmail.com');
define('ADMIN_EMAIL', 'abubakarmusa0987@gmail.com');

// ========== CORS HEADERS ==========
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ========== DATABASE CONNECTION ==========
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
            exit;
        }
    }
    return $pdo;
}

// ========== AUTO-CREATE TABLES ==========
function initDatabase() {
    $db = getDB();

    $db->exec("CREATE TABLE IF NOT EXISTS site_settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value TEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        subject VARCHAR(255) DEFAULT '',
        message TEXT NOT NULL,
        budget VARCHAR(255) DEFAULT '',
        type ENUM('contact','request') DEFAULT 'contact',
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS subscribers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        is_active TINYINT(1) DEFAULT 1,
        subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        unsubscribed_at TIMESTAMP NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS newsletter_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subject VARCHAR(255) NOT NULL,
        body TEXT NOT NULL,
        total_sent INT DEFAULT 0,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

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

    $db->exec("CREATE TABLE IF NOT EXISTS pages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        page_slug VARCHAR(100) NOT NULL UNIQUE,
        content LONGTEXT NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// Run table creation on every request
try {
    initDatabase();
} catch (Exception $e) {
    // Silently continue if table creation fails
}

// ========== ADMIN AUTH HELPERS ==========
function getAdminSetting($key, $default = '') {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

function setAdminSetting($key, $value) {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$key, $value, $value]);
    } catch (Exception $e) {}
}

function getAdminUser() {
    $u = getAdminSetting('admin_user', '');
    return $u !== '' ? $u : ADMIN_USER;
}

function getAdminPass() {
    $p = getAdminSetting('admin_pass', '');
    return $p !== '' ? $p : ADMIN_PASS;
}

function isLoggedIn() {
    return !empty($_SESSION['admin_logged_in']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// ========== SMTP EMAIL FUNCTION ==========
require_once __DIR__ . '/vendor/autoload.php';

function sendSMTP($to, $subject, $htmlBody, $plainBody = '') {
    if (empty($plainBody)) {
        $plainBody = strip_tags($htmlBody);
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $plainBody;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// ========== SEND NOTIFICATION TO ADMIN ==========
function notifyAdmin($subject, $htmlBody) {
    return sendSMTP(ADMIN_EMAIL, $subject, $htmlBody);
}

// ========== JSON RESPONSE ==========
function jsonResponse($success, $message, $code = 200) {
    header('Content-Type: application/json');
    http_response_code($code);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}
