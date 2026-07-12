<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ========== CORS HEADERS (must be before any output/session) ==========
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['https://deep-design.netlify.app', 'https://deepdesign.netlify.app'];
if (in_array($origin, $allowed)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ========== SESSION (only for admin) ==========
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

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

    $db = getDB();

    // Auto-seed pages if empty
    $count = $db->query("SELECT COUNT(*) FROM pages")->fetchColumn();
    if ($count == 0) {
        $pages = [
            'home' => json_encode([
                'meta' => [
                    'title' => 'Deep Design \u2014 Developer & Designer | Portfolio',
                    'description' => 'Personal portfolio of Deep Design \u2014 a developer and designer crafting modern websites, brand identities, and digital experiences. View projects and get in touch.',
                    'keywords' => 'deep design, web developer, graphic designer, portfolio, UI/UX designer, brand identity',
                    'og_title' => 'Deep Design \u2014 Developer & Designer | Portfolio',
                    'og_desc' => 'Personal portfolio of Deep Design \u2014 a developer and designer crafting modern websites, brand identities, and digital experiences.',
                ],
                'hero_title' => 'We Build<br><span class="highlight">Digital Experiences</span>',
                'hero_subtitle' => 'Crafting modern, high-performance websites and applications for businesses that want to stand out.',
                'hero_cta' => 'Start a Project',
                'hero_cta_link' => '/contact',
                'services_label' => 'What I Do',
                'services_heading' => 'Services That <span class="highlight-dark">Drive Results</span>',
                'services' => [
                    ['icon'=>'code','title'=>'Web Development','desc'=>'Fast, responsive websites built with modern technologies.'],
                    ['icon'=>'palette','title'=>'Graphic Design','desc'=>'Visuals that communicate ideas and capture attention.'],
                    ['icon'=>'devices','title'=>'UI/UX Design','desc'=>'Interfaces that users love to navigate.'],
                    ['icon'=>'branding_watermark','title'=>'Brand Identity','desc'=>'Cohesive brand systems that stick.']
                ],
                'services_btn' => 'View All Services',
                'services_btn_link' => '/service',
                'about_label' => 'About Me',
                'about_heading' => 'Developer & Designer <span class="highlight">with Purpose</span>',
                'about_text' => "I don't just build websites \u2014 I craft digital experiences. Every project is a blend of clean code and bold design, tailored to make your brand stand out.",
                'about_btn' => 'Learn More',
                'about_stats' => [
                    ['number'=>'50+','label'=>'Projects Done'],
                    ['number'=>'3+','label'=>'Years Experience'],
                    ['number'=>'30+','label'=>'Happy Clients']
                ],
                'cta_heading' => 'Ready to Start Your Project?',
                'cta_text' => "Let's turn your idea into something extraordinary. Get in touch and let's make it happen.",
                'cta_btn1' => 'Get in Touch',
                'cta_btn1_link' => '/contact',
                'cta_btn2' => 'See My Work',
                'cta_btn2_link' => '/portfolio'
            ], JSON_UNESCAPED_SLASHES),
            'about' => json_encode([
                'meta' => [
                    'title' => 'About \u2014 Deep Design | Developer & Designer',
                    'description' => 'Learn about Deep Design \u2014 a developer and designer with expertise in web development, graphic design, UI/UX, and brand identity.',
                    'keywords' => 'about deep design, developer bio, designer bio, skills, experience, creative process',
                    'og_title' => 'About \u2014 Deep Design | Developer & Designer',
                    'og_desc' => 'Learn about Deep Design \u2014 a developer and designer with expertise in web development, graphic design, and brand identity.',
                ],
                'story_label' => 'My Story',
                'story_heading' => 'From Curiosity to <span class="highlight">Craft</span>',
                'story_p1' => "What started as curiosity about how websites work turned into a deep passion for building digital experiences.",
                'story_p2' => "I believe great design isn't just how something looks \u2014 it's how it works, how it feels, and the story it tells.",
                'story_image' => 'assets/imgs/photo/deep-design-202301.jpg',
                'story_stats' => [
                    ['number'=>'50+','label'=>'Projects Delivered'],
                    ['number'=>'3+','label'=>'Years Experience'],
                    ['number'=>'30+','label'=>'Happy Clients']
                ],
                'skills_label' => 'What I Do',
                'skills_heading' => 'Skills & <span class="highlight">Expertise</span>',
                'skills' => [
                    ['icon'=>'code','title'=>'Web Development','desc'=>'Building fast, responsive, and modern websites.','tags'=>['HTML/CSS','JavaScript','React','Node.js']],
                    ['icon'=>'palette','title'=>'Graphic Design','desc'=>'Creating visually striking designs.','tags'=>['Photoshop','Illustrator','Figma','InDesign']],
                    ['icon'=>'devices','title'=>'UI/UX Design','desc'=>'Designing intuitive, user-centered interfaces.','tags'=>['Figma','Wireframing','Prototyping','User Research']],
                    ['icon'=>'branding_watermark','title'=>'Brand Identity','desc'=>'Building cohesive brand identities.','tags'=>['Logo Design','Brand Guidelines','Typography','Color Theory']]
                ],
                'gallery_label' => 'Behind the Work',
                'gallery_heading' => 'A Glimpse <span class="highlight">Into My World</span>',
                'philosophy_quote' => "I don't just build websites or design graphics \u2014 I craft experiences.",
                'philosophy_author' => 'Deep Design',
                'philosophy_role' => 'Developer & Designer',
                'cta_heading' => 'Have a project in mind?',
                'cta_text' => "I'm always open to new opportunities and creative collaborations.",
                'cta_btn1' => 'Get in Touch',
                'cta_btn2' => 'View My Work'
            ], JSON_UNESCAPED_SLASHES),
            'service' => json_encode([
                'meta' => [
                    'title' => 'Services \u2014 Deep Design | Web Development, Graphic Design & UI/UX',
                    'description' => 'Explore design and development services offered by Deep Design \u2014 web development, graphic design, UI/UX design, brand identity, and motion graphics.',
                    'keywords' => 'web development services, graphic design services, UI/UX design, brand identity, motion graphics, freelance designer',
                    'og_title' => 'Services \u2014 Deep Design | Web Development, Graphic Design & UI/UX',
                    'og_desc' => 'Explore design and development services offered by Deep Design \u2014 web development, graphic design, UI/UX, and brand identity.',
                ],
                'hero_label' => 'What I Offer',
                'hero_heading' => 'Services Built Around <span class="highlight">Your Vision</span>',
                'hero_text' => "From concept to launch \u2014 I deliver end-to-end design and development solutions tailored to your goals.",
                'services' => [
                    ['name'=>'Web Development','desc'=>'Fast, responsive, modern websites.','icon'=>'code','items'=>['Custom responsive website design','Cross-browser & device testing','Performance optimization','SEO-ready semantic HTML'],'tools'=>['HTML5','CSS3','JavaScript','React','PHP','MySQL'],'delivery'=>'1\u20133 weeks'],
                    ['name'=>'Graphic Design','desc'=>'Striking visuals that capture attention.','icon'=>'palette','items'=>['Social media graphics','Posters, flyers, brochures','Presentations & pitch decks','Custom illustrations'],'tools'=>['Photoshop','Illustrator','Figma','InDesign'],'delivery'=>'3\u20137 days'],
                    ['name'=>'UI/UX Design','desc'=>'Interfaces that users love.','icon'=>'devices','items'=>['User research','Wireframing & prototypes','High-fidelity UI mockups','Usability testing'],'tools'=>['Figma','Adobe XD','Sketch','Miro'],'delivery'=>'1\u20134 weeks'],
                    ['name'=>'Brand Identity','desc'=>'Cohesive brand systems.','icon'=>'branding_watermark','items'=>['Logo design','Color palette & typography','Brand guidelines','Social media brand kit'],'tools'=>['Illustrator','Photoshop','Figma','InDesign'],'delivery'=>'2\u20134 weeks'],
                    ['name'=>'Motion Graphics','desc'=>'Dynamic animations.','icon'=>'animation','items'=>['Logo animations','Social media animated content','UI micro-interactions','Lottie animations for web'],'tools'=>['After Effects','Premiere Pro','Lottie','GSAP'],'delivery'=>'1\u20133 weeks']
                ],
                'process_label' => 'How I Work',
                'process_heading' => 'From Idea to <span class="highlight-dark">Launch</span>',
                'process_steps' => [
                    ['number'=>'01','title'=>'Discover','desc'=>"We discuss your goals, audience, and vision."],
                    ['number'=>'02','title'=>'Design','desc'=>"Wireframes, mockups, and prototypes."],
                    ['number'=>'03','title'=>'Develop','desc'=>"Clean, performant code."],
                    ['number'=>'04','title'=>'Launch','desc'=>"Final review, deployment, and handoff."]
                ],
                'pricing_heading' => 'Pricing That Fits Your Budget',
                'pricing_text' => "Every project is different. I offer flexible pricing based on scope, timeline, and complexity.",
                'pricing_btn' => 'Get a Quote'
            ], JSON_UNESCAPED_SLASHES),
            'contact' => json_encode([
                'meta' => [
                    'title' => 'Contact \u2014 Deep Design | Get in Touch',
                    'description' => 'Get in touch with Deep Design for web development, graphic design, UI/UX, or brand identity projects. Send a request and get a response within 24 hours.',
                    'keywords' => 'contact deep design, hire developer, hire designer, freelance inquiry, project request, design quote',
                    'og_title' => 'Contact \u2014 Deep Design | Get in Touch',
                    'og_desc' => 'Get in touch with Deep Design for web development, graphic design, or brand identity projects.',
                ],
                'hero_label' => 'Get in Touch',
                'hero_heading' => "Let's Build Something <span class=\"highlight\">Great Together</span>",
                'hero_text' => "Have a project in mind? I'd love to hear about it. I respond within 24 hours.",
                'info_cards' => [
                    ['icon'=>'mail','title'=>'Email','value'=>'abubakarmusa0987@gmail.com'],
                    ['icon'=>'schedule','title'=>'Response Time','value'=>'Within 24 hours'],
                    ['icon'=>'public','title'=>'Availability','value'=>'Available worldwide for remote projects'],
                    ['icon'=>'handshake','title'=>'Services','value'=>'Web Dev, Design, UI/UX, Branding']
                ],
                'newsletter_title' => 'Stay Updated',
                'newsletter_text' => 'Get notified about new projects and design tips.',
                'social_label' => "Let's Connect",
                'social_heading' => 'Follow Me on <span class="highlight-dark">Social Media</span>',
                'social_text' => "Stay updated with my latest work, design tips, and creative projects."
            ], JSON_UNESCAPED_SLASHES)
        ];
        $stmt = $db->prepare("INSERT INTO pages (page_slug, content) VALUES (?, ?) ON DUPLICATE KEY UPDATE content = ?");
        foreach ($pages as $slug => $json) {
            $stmt->execute([$slug, $json, $json]);
        }
    }

    // Auto-seed portfolio if empty
    $count = $db->query("SELECT COUNT(*) FROM portfolio")->fetchColumn();
    if ($count == 0) {
        $projects = [
            ['E-Commerce Platform','A full-stack online store with product management, cart system, and secure checkout.','uploads/proj_ecommerce.jpg','web','React, Node.js, Stripe','',1],
            ['Luxe Brand Identity','Complete brand system for a luxury lifestyle brand \u2014 logo, color palette, typography.','uploads/proj_branding.jpg','branding','Illustrator, Photoshop, InDesign','',2],
            ['FinTrack Dashboard','Modern financial analytics dashboard with real-time data visualization.','uploads/proj_dashboard.jpg','uiux','Figma, Prototyping, Design System','',3],
            ['Art Festival Campaign','Visual identity and promotional materials for an annual art festival.','uploads/proj_festival.jpg','graphic','Photoshop, Illustrator, Print','',4],
            ['DevConnect Platform','Developer community platform with profiles, project showcases, and messaging.','uploads/proj_devconnect.jpg','web','Vue.js, Firebase, Tailwind','',5],
            ['GreenLeaf Rebrand','Brand refresh for eco-friendly products \u2014 logo, packaging, social media kit.','uploads/proj_greenleaf.jpg','branding','Logo Design, Packaging, Guidelines','',6],
            ['MediCare App','Healthcare appointment booking app with patient profiles and telehealth.','uploads/proj_medicare.jpg','uiux','Figma, Wireframing, User Research','',7],
            ['Brand Packaging Set','Product packaging design collection for a gourmet food brand.','uploads/proj_packaging.jpg','graphic','Illustrator, Photoshop, Print','',8],
            ['PropertyFinder Website','Real estate listing platform with advanced search and interactive maps.','uploads/proj_property.jpg','web','React, PHP, MySQL','',9],
        ];
        $stmt = $db->prepare("INSERT IGNORE INTO portfolio (title, description, image, category, tags, project_url, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($projects as $p) $stmt->execute($p);
    }

    // Auto-seed gallery if empty
    $count = $db->query("SELECT COUNT(*) FROM gallery_images")->fetchColumn();
    if ($count == 0) {
        $images = [
            ['https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=800&q=80','Web Development Project','web','Modern web application',1],
            ['https://images.unsplash.com/photo-1561070791-2526d30994b5?w=800&q=80','Brand Identity Design','branding','Complete brand identity',2],
            ['https://images.unsplash.com/photo-1561070791-36c11767b26a?w=800&q=80','UI/UX Interface Design','uiux','Clean mobile interface',3],
            ['https://images.unsplash.com/photo-1626785774573-4b799315345d?w=800&q=80','Graphic Design Poster','graphic','Bold poster design',4],
            ['https://images.unsplash.com/photo-1558655146-9f40138edfeb?w=800&q=80','Social Media Campaign','graphic','Social media visuals',5],
            ['https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&q=80','Dashboard Interface','uiux','Analytics dashboard',6],
        ];
        $stmt = $db->prepare("INSERT IGNORE INTO gallery_images (src, title, category, description, sort_order) VALUES (?, ?, ?, ?, ?)");
        foreach ($images as $img) $stmt->execute($img);
    }

    // Auto-seed site_settings defaults if empty
    $count = $db->query("SELECT COUNT(*) FROM site_settings")->fetchColumn();
    if ($count == 0) {
        $settings = [
            ['admin_user', 'admin'],
            ['admin_pass', 'deepdesign2026'],
            ['contact_email', 'abubakarmusa0987@gmail.com'],
            ['contact_location', 'Available for remote work worldwide'],
            ['contact_response', "I'll respond within 24 hours"],
            ['whatsapp_number', ''],
            ['social_links', json_encode([
                ['label'=>'LinkedIn','url'=>'https://linkedin.com/in/','icon'=>'fab fa-linkedin'],
                ['label'=>'Twitter','url'=>'https://x.com/','icon'=>'fab fa-x-twitter'],
                ['label'=>'GitHub','url'=>'https://github.com/','icon'=>'fab fa-github'],
                ['label'=>'Dribbble','url'=>'https://dribbble.com/','icon'=>'fab fa-dribbble'],
                ['label'=>'Instagram','url'=>'https://instagram.com/','icon'=>'fab fa-instagram'],
                ['label'=>'Behance','url'=>'https://behance.net/','icon'=>'fab fa-behance'],
                ['label'=>'CodePen','url'=>'https://codepen.io/','icon'=>'fab fa-codepen'],
                ['label'=>'Figma','url'=>'https://figma.com/','icon'=>'fab fa-figma'],
            ], JSON_UNESCAPED_SLASHES)],
        ];
        $stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
        foreach ($settings as $s) $stmt->execute($s);
    }

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
