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
define('SMTP_FROM_NAME', 'Deep Design Hubs');
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
        slug VARCHAR(255) DEFAULT '',
        description TEXT,
        long_description LONGTEXT,
        image VARCHAR(500) DEFAULT '',
        category VARCHAR(100) DEFAULT '',
        tags VARCHAR(500) DEFAULT '',
        project_url VARCHAR(500) DEFAULT '',
        gallery_images TEXT,
        seo_title VARCHAR(500) DEFAULT '',
        seo_description TEXT,
        seo_keywords VARCHAR(500) DEFAULT '',
        is_downloadable TINYINT(1) DEFAULT 0,
        price DECIMAL(10,2) DEFAULT 0,
        download_file VARCHAR(500) DEFAULT '',
        sort_order INT DEFAULT 0,
        is_visible TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS download_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        portfolio_id INT NOT NULL,
        email VARCHAR(255) NOT NULL,
        name VARCHAR(255) DEFAULT '',
        status ENUM('pending_payment','pending_admin','approved','rejected','expired') DEFAULT 'pending_payment',
        access_code VARCHAR(20) DEFAULT '',
        code_expires_at DATETIME NULL,
        paystack_reference VARCHAR(100) DEFAULT '',
        amount DECIMAL(10,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_portfolio (portfolio_id),
        INDEX idx_email (email),
        INDEX idx_code (access_code),
        INDEX idx_status (status)
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
        'long_description' => "ALTER TABLE portfolio ADD COLUMN long_description LONGTEXT AFTER description",
        'is_downloadable' => "ALTER TABLE portfolio ADD COLUMN is_downloadable TINYINT(1) DEFAULT 0 AFTER seo_keywords",
        'price' => "ALTER TABLE portfolio ADD COLUMN price DECIMAL(10,2) DEFAULT 0 AFTER is_downloadable",
        'download_file' => "ALTER TABLE portfolio ADD COLUMN download_file VARCHAR(500) DEFAULT '' AFTER price",
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

    // Migration: populate empty slugs from titles
    try {
        $emptySlugs = $db->query("SELECT id, title FROM portfolio WHERE slug = '' OR slug IS NULL")->fetchAll();
        if (!empty($emptySlugs)) {
            $slugStmt = $db->prepare("UPDATE portfolio SET slug = ? WHERE id = ?");
            foreach ($emptySlugs as $row) {
                $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $row['title']), '-'));
                $slugStmt->execute([$slug, $row['id']]);
            }
        }
    } catch (Exception $e) {}

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
                    'title' => 'Deep Design Hubs \u2014 Developer & Designer | Portfolio',
                    'description' => 'Personal portfolio of Deep Design Hubs \u2014 a developer and designer crafting modern websites, brand identities, and digital experiences. View projects and get in touch.',
                    'keywords' => 'deep design hubs, web development company, graphic design agency, UI/UX design services, brand identity design, freelance developer, creative web design, custom website development, responsive design, digital experience design, professional web designer, business website design, portfolio website, ecommerce development, landing page design',
                    'og_title' => 'Deep Design Hubs \u2014 Developer & Designer | Portfolio',
                    'og_desc' => 'Personal portfolio of Deep Design Hubs \u2014 a developer and designer crafting modern websites, brand identities, and digital experiences.',
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
                    'title' => 'About \u2014 Deep Design Hubs | Developer & Designer',
                    'description' => 'Learn about Deep Design Hubs \u2014 a developer and designer with expertise in web development, graphic design, UI/UX, and brand identity.',
                    'keywords' => 'about deep design hubs, developer bio, designer profile, web development skills, graphic design expertise, UI/UX experience, brand identity portfolio, creative process, design agency about',
                    'og_title' => 'About \u2014 Deep Design Hubs | Developer & Designer',
                    'og_desc' => 'Learn about Deep Design Hubs \u2014 a developer and designer with expertise in web development, graphic design, and brand identity.',
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
                'philosophy_author' => 'Deep Design Hubs',
                'philosophy_role' => 'Developer & Designer',
                'cta_heading' => 'Have a project in mind?',
                'cta_text' => "I'm always open to new opportunities and creative collaborations.",
                'cta_btn1' => 'Get in Touch',
                'cta_btn2' => 'View My Work'
            ], JSON_UNESCAPED_SLASHES),
            'service' => json_encode([
                'meta' => [
                    'title' => 'Services \u2014 Deep Design Hubs | Web Development, Graphic Design & UI/UX',
                    'description' => 'Explore design and development services offered by Deep Design Hubs \u2014 web development, graphic design, UI/UX design, brand identity, and motion graphics.',
                    'keywords' => 'web development services, graphic design services, UI/UX design services, brand identity design, motion graphics, freelance design services, custom web development, responsive web design, logo design services, business website design',
                    'og_title' => 'Services \u2014 Deep Design Hubs | Web Development, Graphic Design & UI/UX',
                    'og_desc' => 'Explore design and development services offered by Deep Design Hubs \u2014 web development, graphic design, UI/UX, and brand identity.',
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
                    'title' => 'Contact \u2014 Deep Design Hubs | Get in Touch',
                    'description' => 'Get in touch with Deep Design Hubs for web development, graphic design, UI/UX, or brand identity projects. Send a request and get a response within 24 hours.',
                    'keywords' => 'contact deep design hubs, hire web developer, hire graphic designer, freelance inquiry, project quote, web development price, design consultation',
                    'og_title' => 'Contact \u2014 Deep Design Hubs | Get in Touch',
                    'og_desc' => 'Get in touch with Deep Design Hubs for web development, graphic design, or brand identity projects.',
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
            ], JSON_UNESCAPED_SLASHES),
            'portfolio' => json_encode([
                'meta' => [
                    'title' => 'Portfolio \u2014 Deep Design Hubs | Web Development & Design Projects',
                    'description' => 'Explore the portfolio of Deep Design Hubs \u2014 web development projects, graphic design work, UI/UX case studies, and brand identity showcases.',
                    'keywords' => 'deep design hubs portfolio, web development projects, graphic design portfolio, UI/UX case studies, brand identity work, design showcase',
                    'og_title' => 'Portfolio \u2014 Deep Design Hubs | Web Development & Design Projects',
                    'og_desc' => 'Explore the portfolio of Deep Design Hubs \u2014 web development projects, graphic design work, and brand identity showcases.',
                ],
                'hero_label' => 'My Work',
                'hero_heading' => 'Featured <span class="highlight">Projects</span>',
                'hero_text' => "A curated selection of web development, design, and branding projects.",
                'filter_all' => 'All',
                'filter_web' => 'Web Dev',
                'filter_branding' => 'Branding',
                'filter_uiux' => 'UI/UX',
                'filter_graphic' => 'Graphic Design',
                'view_project' => 'View Project',
                'cta_heading' => 'Like What You See?',
                'cta_text' => "Let's work together on your next project.",
                'cta_btn1' => 'Start a Project',
                'cta_btn1_link' => '/contact',
                'cta_btn2' => 'Back to Home',
                'cta_btn2_link' => '/'
            ], JSON_UNESCAPED_SLASHES),
            'gallery' => json_encode([
                'meta' => [
                    'title' => 'Gallery \u2014 Deep Design Hubs | Design Showcase',
                    'description' => 'Browse the gallery of Deep Design Hubs \u2014 visual portfolio showcasing graphic design examples, web design mockups, and creative work.',
                    'keywords' => 'deep design hubs gallery, design showcase, visual portfolio, graphic design examples, web design mockups, creative work gallery',
                    'og_title' => 'Gallery \u2014 Deep Design Hubs | Design Showcase',
                    'og_desc' => 'Browse the gallery of Deep Design Hubs \u2014 visual portfolio showcasing graphic design, web design, and creative work.',
                ],
                'hero_label' => 'Gallery',
                'hero_heading' => 'Visual <span class="highlight">Showcase</span>',
                'hero_text' => "A glimpse into our creative process and finished work.",
                'filter_all' => 'All',
                'filter_web' => 'Web',
                'filter_branding' => 'Branding',
                'filter_uiux' => 'UI/UX',
                'filter_graphic' => 'Graphic',
                'cta_heading' => 'Want Similar Work?',
                'cta_text' => "Let's discuss your next project.",
                'cta_btn1' => 'Get in Touch',
                'cta_btn1_link' => '/contact',
                'cta_btn2' => 'Back to Home',
                'cta_btn2_link' => '/'
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
            [
                'title' => 'E-Commerce Platform',
                'slug' => 'e-commerce-platform',
                'description' => 'A full-stack online store with product management, cart system, and secure checkout. Built with React frontend and Node.js backend, featuring Stripe payment integration, real-time inventory tracking, and a responsive admin dashboard.',
                'image' => 'uploads/proj_ecommerce.jpg',
                'category' => 'web',
                'tags' => 'React, Node.js, Stripe, MongoDB',
                'project_url' => '',
                'gallery_images' => json_encode([
                    ['src' => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=800&q=80', 'caption' => 'Product listing page with filters'],
                    ['src' => 'https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=800&q=80', 'caption' => 'Shopping cart and checkout flow'],
                    ['src' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&q=80', 'caption' => 'Admin dashboard analytics'],
                ]),
                'seo_title' => 'E-Commerce Platform — Deep Design Hubs | Full-Stack Online Store',
                'seo_description' => 'Full-stack e-commerce platform built by Deep Design Hubs with React, Node.js, Stripe integration, product management, and responsive admin dashboard.',
                'seo_keywords' => 'e-commerce website, online store, react ecommerce, full stack ecommerce, shopping cart, payment integration, web development project',
                'sort_order' => 1,
            ],
            [
                'title' => 'Luxe Brand Identity',
                'slug' => 'luxe-brand-identity',
                'description' => 'Complete brand system for a luxury lifestyle brand — logo, color palette, typography, brand guidelines, and social media kit. A cohesive identity that speaks elegance and exclusivity.',
                'image' => 'uploads/proj_branding.jpg',
                'category' => 'branding',
                'tags' => 'Illustrator, Photoshop, InDesign, Brand Guidelines',
                'project_url' => '',
                'gallery_images' => json_encode([
                    ['src' => 'https://images.unsplash.com/photo-1634942537034-2531766767d1?w=800&q=80', 'caption' => 'Logo variations and usage'],
                    ['src' => 'https://images.unsplash.com/photo-1586717791821-3f44a563fa4c?w=800&q=80', 'caption' => 'Color palette and typography'],
                    ['src' => 'https://images.unsplash.com/photo-1524601815066-2d6c256e3519?w=800&q=80', 'caption' => 'Brand collateral mockups'],
                ]),
                'seo_title' => 'Luxe Brand Identity — Deep Design Hubs | Luxury Brand Design',
                'seo_description' => 'Complete luxury brand identity system designed by Deep Design Hubs — logo, color palette, typography, brand guidelines, and social media kit.',
                'seo_keywords' => 'luxury brand identity, logo design, brand guidelines, typography design, brand system, visual identity, brand identity design agency',
                'sort_order' => 2,
            ],
            [
                'title' => 'FinTrack Dashboard',
                'slug' => 'fintrack-dashboard',
                'description' => 'Modern financial analytics dashboard with real-time data visualization. Features interactive charts, portfolio tracking, and customizable widgets for data-driven decision making.',
                'image' => 'uploads/proj_dashboard.jpg',
                'category' => 'uiux',
                'tags' => 'Figma, Prototyping, Design System, Data Visualization',
                'project_url' => '',
                'gallery_images' => json_encode([
                    ['src' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=800&q=80', 'caption' => 'Main dashboard with analytics charts'],
                    ['src' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&q=80', 'caption' => 'Portfolio tracking view'],
                    ['src' => 'https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3?w=800&q=80', 'caption' => 'Real-time data widgets'],
                ]),
                'seo_title' => 'FinTrack Dashboard — Deep Design Hubs | Financial Analytics UI/UX',
                'seo_description' => 'Modern financial analytics dashboard UI/UX design by Deep Design Hubs — real-time data visualization, interactive charts, and portfolio tracking.',
                'seo_keywords' => 'financial dashboard design, analytics UI, data visualization, fintech design, dashboard UI/UX, real-time dashboard, portfolio tracker design',
                'sort_order' => 3,
            ],
            [
                'title' => 'Art Festival Campaign',
                'slug' => 'art-festival-campaign',
                'description' => 'Visual identity and promotional materials for an annual art festival. Includes posters, social media graphics, banners, ticket designs, and event program layout.',
                'image' => 'uploads/proj_festival.jpg',
                'category' => 'graphic',
                'tags' => 'Photoshop, Illustrator, Print Design, Event Branding',
                'project_url' => '',
                'gallery_images' => json_encode([
                    ['src' => 'https://images.unsplash.com/photo-1561070791-2526d30994b5?w=800&q=80', 'caption' => 'Festival poster design'],
                    ['src' => 'https://images.unsplash.com/photo-1586717791821-3f44a563fa4c?w=800&q=80', 'caption' => 'Social media campaign graphics'],
                    ['src' => 'https://images.unsplash.com/photo-1626785774573-4b799315345d?w=800&q=80', 'caption' => 'Event program and materials'],
                ]),
                'seo_title' => 'Art Festival Campaign — Deep Design Hubs | Event Graphic Design',
                'seo_description' => 'Art festival visual identity and promotional campaign by Deep Design Hubs — posters, social media graphics, banners, and event materials.',
                'seo_keywords' => 'event graphic design, festival branding, poster design, social media campaign, promotional materials, event identity, print design',
                'sort_order' => 4,
            ],
            [
                'title' => 'DevConnect Platform',
                'slug' => 'devconnect-platform',
                'description' => 'Developer community platform with profiles, project showcases, and real-time messaging. Built with Vue.js and Firebase for seamless collaboration.',
                'image' => 'uploads/proj_devconnect.jpg',
                'category' => 'web',
                'tags' => 'Vue.js, Firebase, Tailwind CSS, Real-time',
                'project_url' => '',
                'gallery_images' => json_encode([
                    ['src' => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=800&q=80', 'caption' => 'Developer profile and projects'],
                    ['src' => 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=800&q=80', 'caption' => 'Code collaboration features'],
                    ['src' => 'https://images.unsplash.com/photo-1531482615713-2afd69097998?w=800&q=80', 'caption' => 'Community messaging interface'],
                ]),
                'seo_title' => 'DevConnect Platform — Deep Design Hubs | Developer Community',
                'seo_description' => 'Developer community platform built by Deep Design Hubs with Vue.js and Firebase — profiles, project showcases, and real-time messaging.',
                'seo_keywords' => 'developer community platform, social network for developers, vue.js project, firebase real-time, code collaboration, project showcase',
                'sort_order' => 5,
            ],
            [
                'title' => 'GreenLeaf Rebrand',
                'slug' => 'greenleaf-rebrand',
                'description' => 'Brand refresh for eco-friendly products — new logo, packaging design, social media brand kit, and comprehensive brand guidelines for consistent identity.',
                'image' => 'uploads/proj_greenleaf.jpg',
                'category' => 'branding',
                'tags' => 'Logo Design, Packaging, Brand Guidelines, Eco Design',
                'project_url' => '',
                'gallery_images' => json_encode([
                    ['src' => 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?w=800&q=80', 'caption' => 'Eco-friendly brand identity'],
                    ['src' => 'https://images.unsplash.com/photo-1586717791821-3f44a563fa4c?w=800&q=80', 'caption' => 'Packaging design mockups'],
                    ['src' => 'https://images.unsplash.com/photo-1561070791-2526d30994b5?w=800&q=80', 'caption' => 'Social media brand kit'],
                ]),
                'seo_title' => 'GreenLeaf Rebrand — Deep Design Hubs | Eco Brand Identity',
                'seo_description' => 'Eco-friendly brand rebrand by Deep Design Hubs — new logo, sustainable packaging design, social media kit, and brand guidelines.',
                'seo_keywords' => 'eco brand design, sustainable branding, green logo design, packaging design, environmental brand identity, eco-friendly marketing',
                'sort_order' => 6,
            ],
            [
                'title' => 'MediCare App',
                'slug' => 'medicare-app',
                'description' => 'Healthcare appointment booking app with patient profiles, telehealth video calls, prescription management, and health tracking features.',
                'image' => 'uploads/proj_medicare.jpg',
                'category' => 'uiux',
                'tags' => 'Figma, Wireframing, User Research, Healthcare UX',
                'project_url' => '',
                'gallery_images' => json_encode([
                    ['src' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=800&q=80', 'caption' => 'Appointment booking interface'],
                    ['src' => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?w=800&q=80', 'caption' => 'Telehealth video call screen'],
                    ['src' => 'https://images.unsplash.com/photo-1559757175-5700dde675bc?w=800&q=80', 'caption' => 'Patient profile dashboard'],
                ]),
                'seo_title' => 'MediCare App — Deep Design Hubs | Healthcare App UI/UX Design',
                'seo_description' => 'Healthcare appointment booking app UI/UX design by Deep Design Hubs — telehealth, patient profiles, and prescription management.',
                'seo_keywords' => 'healthcare app design, medical UI/UX, telehealth app, appointment booking app, patient portal design, health app interface',
                'sort_order' => 7,
            ],
            [
                'title' => 'Brand Packaging Set',
                'slug' => 'brand-packaging-set',
                'description' => 'Product packaging design collection for a gourmet food brand. Includes box designs, labels, wrappers, and promotional display materials.',
                'image' => 'uploads/proj_packaging.jpg',
                'category' => 'graphic',
                'tags' => 'Illustrator, Photoshop, Print, Packaging',
                'project_url' => '',
                'gallery_images' => json_encode([
                    ['src' => 'https://images.unsplash.com/photo-1586717791821-3f44a563fa4c?w=800&q=80', 'caption' => 'Box and wrapper designs'],
                    ['src' => 'https://images.unsplash.com/photo-1561070791-36c11767b26a?w=800&q=80', 'caption' => 'Product label designs'],
                    ['src' => 'https://images.unsplash.com/photo-1626785774573-4b799315345d?w=800&q=80', 'caption' => 'Display materials'],
                ]),
                'seo_title' => 'Brand Packaging Set — Deep Design Hubs | Product Packaging Design',
                'seo_description' => 'Gourmet food brand packaging design collection by Deep Design Hubs — boxes, labels, wrappers, and promotional display materials.',
                'seo_keywords' => 'product packaging design, food packaging, label design, box design, brand packaging, custom packaging, retail packaging design',
                'sort_order' => 8,
            ],
            [
                'title' => 'PropertyFinder Website',
                'slug' => 'propertyfinder-website',
                'description' => 'Real estate listing platform with advanced search filters, interactive maps, virtual tours, and a powerful property management system.',
                'image' => 'uploads/proj_property.jpg',
                'category' => 'web',
                'tags' => 'React, PHP, MySQL, Google Maps API',
                'project_url' => '',
                'gallery_images' => json_encode([
                    ['src' => 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=800&q=80', 'caption' => 'Property listing page'],
                    ['src' => 'https://images.unsplash.com/photo-1560184897-ae75f418493e?w=800&q=80', 'caption' => 'Interactive map search'],
                    ['src' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=800&q=80', 'caption' => 'Property detail view'],
                ]),
                'seo_title' => 'PropertyFinder Website — Deep Design Hubs | Real Estate Web Development',
                'seo_description' => 'Real estate listing platform built by Deep Design Hubs — advanced search, interactive maps, virtual tours, and property management.',
                'seo_keywords' => 'real estate website, property listing platform, real estate web development, property search, interactive map, virtual tour website',
                'sort_order' => 9,
            ],
        ];
        $stmt = $db->prepare("INSERT IGNORE INTO portfolio (title, slug, description, image, category, tags, project_url, gallery_images, seo_title, seo_description, seo_keywords, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($projects as $p) {
            $stmt->execute([
                $p['title'], $p['slug'], $p['description'], $p['image'], $p['category'],
                $p['tags'], $p['project_url'], $p['gallery_images'],
                $p['seo_title'], $p['seo_description'], $p['seo_keywords'], $p['sort_order']
            ]);
        }
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
