<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $db = getDB();

    // Social links
    $social = json_decode(getAdminSetting('social_links', '[]'), true);
    if (!is_array($social)) $social = [];

    // Contact info
    $contact = [
        'email' => getAdminSetting('contact_email', 'abubakarmusa0987@gmail.com'),
        'location' => getAdminSetting('contact_location', 'Available for remote work worldwide'),
        'responseTime' => getAdminSetting('contact_response', "I'll respond within 24 hours"),
        'whatsapp' => getAdminSetting('whatsapp_number', ''),
    ];

    // Nav links (static)
    $nav = [
        ['label'=>'Home','page'=>'home','icon'=>'home'],
        ['label'=>'Services','page'=>'service','icon'=>'design_services'],
        ['label'=>'Portfolio','page'=>'portfolio','icon'=>'dashboard'],
        ['label'=>'Contact','page'=>'contact','icon'=>'mail'],
        ['label'=>'About','page'=>'about','icon'=>'person'],
        ['label'=>'Gallery','page'=>'gallery','icon'=>'photo_library'],
    ];

    // Services list (static)
    $services = ['Graphic Design','Web Development','UI/UX Design','Brand Identity','Motion Graphics','Other'];

    // Footer
    $footer = [
        'copyright' => '2026 Deep Design',
        'tagline' => 'A creative studio specializing in graphic design and web development. We craft digital experiences that elevate brands and drive results.',
        'quickLinks' => [
            ['label'=>'Home','page'=>'home'],
            ['label'=>'Services','page'=>'service'],
            ['label'=>'Portfolio','page'=>'portfolio'],
            ['label'=>'About','page'=>'about'],
        ],
        'serviceLinks' => [
            ['label'=>'Graphic Design','page'=>'service'],
            ['label'=>'Web Development','page'=>'service'],
            ['label'=>'UI/UX Design','page'=>'service'],
            ['label'=>'Branding','page'=>'service'],
        ],
    ];

    echo json_encode([
        'success' => true,
        'social' => $social,
        'contact' => $contact,
        'nav' => $nav,
        'services' => $services,
        'footer' => $footer,
    ], JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
