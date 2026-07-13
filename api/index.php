<?php
require_once __DIR__ . '/config.php';
requireLogin();
$db = getDB();

$tab = $_GET['tab'] ?? 'dashboard';
$msg = '';
$msgType = '';

if (isset($_GET['msg'])) {
    $codes = ['uploaded'=>'Image uploaded successfully','deleted'=>'Image deleted','updated'=>'Changes saved'];
    $msg = $codes[$_GET['msg']] ?? '';
    $msgType = 'success';
}

// ========== HANDLE ACTIONS ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'delete_contact') {
            $db->prepare("DELETE FROM contacts WHERE id = ?")->execute([(int)$_POST['id']]);
            $msg = 'Contact deleted.'; $msgType = 'success';
        }
        if ($action === 'reply_contact') {
            $replyId = (int)$_POST['id'];
            $replyMsg = trim($_POST['reply_message'] ?? '');
            $contactRow = $db->prepare("SELECT * FROM contacts WHERE id = ?");
            $contactRow->execute([$replyId]);
            $contact = $contactRow->fetch();
            if ($contact && $replyMsg) {
                $contactName = htmlspecialchars($contact['name']);
                $contactEmail = $contact['email'];
                $replyHtml = '<html><body style="font-family:\'Segoe UI\',Arial,sans-serif;color:#1a1a1a;max-width:600px;margin:0 auto;padding:20px;">';
                $replyHtml .= '<div style="background:#000;padding:30px;border-radius:16px 16px 0 0;text-align:center;">';
                $replyHtml .= '<img src="https://deep-design.netlify.app/assets/imgs/logo/white-deep.png" alt="Deep Design Hubs" style="height:40px;margin-bottom:8px;">';
                $replyHtml .= '<p style="color:#888;font-size:12px;margin:0;letter-spacing:1px;text-transform:uppercase;">Reply to Your Message</p>';
                $replyHtml .= '</div>';
                $replyHtml .= '<div style="border:1px solid #e5e5e5;border-top:none;padding:32px;border-radius:0 0 16px 16px;">';
                $replyHtml .= '<p style="font-size:15px;line-height:1.7;">Hi ' . $contactName . ',</p>';
                $replyHtml .= '<div style="margin:20px 0;padding:16px;background:#f8f8f8;border-left:3px solid #000;border-radius:0 8px 8px 0;">';
                $replyHtml .= '<p style="margin:0 0 4px;color:#999;font-size:11px;text-transform:uppercase;font-weight:600;">Your original message</p>';
                $replyHtml .= '<p style="margin:0;font-size:13px;color:#666;">' . htmlspecialchars($contact['message']) . '</p>';
                $replyHtml .= '</div>';
                $replyHtml .= '<div style="margin:24px 0;padding:20px;background:#f0f0f0;border-radius:10px;">';
                $replyHtml .= '<p style="margin:0 0 6px;color:#999;font-size:11px;text-transform:uppercase;font-weight:600;">Reply from Deep Design Hubs</p>';
                $replyHtml .= '<p style="margin:0;font-size:15px;line-height:1.7;white-space:pre-wrap;">' . nl2br(htmlspecialchars($replyMsg)) . '</p>';
                $replyHtml .= '</div>';
                $replyHtml .= '<p style="font-size:15px;line-height:1.7;margin-top:24px;">Best regards,<br><strong>Deep Design Hubs</strong></p>';
                $replyHtml .= '</div>';
                $replyHtml .= '<p style="text-align:center;color:#bbb;font-size:11px;margin-top:20px;">deep-design.netlify.app</p>';
                $replyHtml .= '</body></html>';
                $sent = sendSMTP($contactEmail, 'Re: Your inquiry at Deep Design Hubs', $replyHtml);
                // Mark as read
                $db->prepare("UPDATE contacts SET is_read = 1 WHERE id = ?")->execute([$replyId]);
                $msg = $sent ? 'Reply sent to ' . $contactEmail : 'Failed to send reply.'; $msgType = $sent ? 'success' : 'error';
            } else {
                $msg = 'Could not send reply.'; $msgType = 'error';
            }
        }
        if ($action === 'mark_read') {
            $db->prepare("UPDATE contacts SET is_read = 1 WHERE id = ?")->execute([(int)$_POST['id']]);
            $msg = 'Marked as read.'; $msgType = 'success';
        }
        if ($action === 'delete_subscriber') {
            $db->prepare("DELETE FROM subscribers WHERE id = ?")->execute([(int)$_POST['id']]);
            $msg = 'Subscriber removed.'; $msgType = 'success';
        }
        if ($action === 'delete_gallery') {
            $id = (int)$_POST['id'];
            $row = $db->prepare("SELECT src FROM gallery_images WHERE id = ?");
            $row->execute([$id]);
            $img = $row->fetch();
            if ($img && strpos($img['src'], 'uploads/') === 0) {
                $file = __DIR__ . '/' . $img['src'];
                if (file_exists($file)) unlink($file);
            }
            $db->prepare("DELETE FROM gallery_images WHERE id = ?")->execute([$id]);
            $msg = 'Image deleted.'; $msgType = 'success';
        }
        if ($action === 'update_gallery') {
            $id = (int)$_POST['id'];
            $src = trim($_POST['src'] ?? '');
            if (!empty($src)) {
                $db->prepare("UPDATE gallery_images SET src=?, title=?, category=?, description=?, sort_order=?, is_visible=? WHERE id=?")
                   ->execute([$src, trim($_POST['title']), trim($_POST['category']), trim($_POST['description']), (int)$_POST['sort_order'], isset($_POST['is_visible'])?1:0, $id]);
            } else {
                $db->prepare("UPDATE gallery_images SET title=?, category=?, description=?, sort_order=?, is_visible=? WHERE id=?")
                   ->execute([trim($_POST['title']), trim($_POST['category']), trim($_POST['description']), (int)$_POST['sort_order'], isset($_POST['is_visible'])?1:0, $id]);
            }
            $msg = 'Image updated.'; $msgType = 'success';
        }
        if ($action === 'add_gallery_url') {
            $src = trim($_POST['src'] ?? '');
            if (!empty($src)) {
                $db->prepare("INSERT INTO gallery_images (src, title, category, description, sort_order) VALUES (?, ?, ?, ?, ?)")
                   ->execute([$src, trim($_POST['title']), trim($_POST['category']), trim($_POST['description']), (int)($_POST['sort_order']??0)]);
                $msg = 'Image added.'; $msgType = 'success';
            }
        }
        if ($action === 'seed_gallery') {
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
            $msg = 'Gallery seeded.'; $msgType = 'success';
        }
        if ($action === 'test_email') {
            $result = sendSMTP(ADMIN_EMAIL, 'Test Email', '<html><body><h2>Email works!</h2><p>Sent: '.date('Y-m-d H:i:s').'</p></body></html>');
            $msg = $result ? 'Email sent to '.ADMIN_EMAIL : 'Failed to send.'; $msgType = $result ? 'success' : 'error';
        }
        if ($action === 'send_newsletter') {
            $subject = trim($_POST['subject'] ?? '');
            $body = trim($_POST['body'] ?? '');
            if (!empty($subject) && !empty($body)) {
                $subscribers = $db->query("SELECT email FROM subscribers WHERE is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
                $sent = 0;
                foreach ($subscribers as $email) {
                    if (sendSMTP($email, $subject, str_replace('{{email}}', $email, $body))) $sent++;
                }
                $db->prepare("INSERT INTO newsletter_log (subject, body, total_sent) VALUES (?, ?, ?)")->execute([$subject, $body, $sent]);
                $msg = "Newsletter sent to $sent/".count($subscribers)." subscribers."; $msgType = 'success';
            }
        }

        // ========== PAGE CONTENT ACTIONS ==========
        if ($action === 'save_site_info') {
            $socialJson = $_POST['social_links'] ?? '[]';
            $contactEmail = trim($_POST['contact_email'] ?? '');
            $contactLocation = trim($_POST['contact_location'] ?? '');
            $contactResponse = trim($_POST['contact_response'] ?? '');
            $whatsapp = trim($_POST['whatsapp_number'] ?? '');
            setAdminSetting('social_links', $socialJson);
            setAdminSetting('contact_email', $contactEmail);
            setAdminSetting('contact_location', $contactLocation);
            setAdminSetting('contact_response', $contactResponse);
            setAdminSetting('whatsapp_number', $whatsapp);
            $msg = 'Site info saved.'; $msgType = 'success';
        }
        if ($action === 'save_page_content') {
            $pageSlug = $_POST['page_slug'] ?? '';
            if (!in_array($pageSlug, ['home','about','service','contact'])) {
                $msg = 'Invalid page.'; $msgType = 'error';
            } else {
                $content = [];
                $content['meta'] = [
                    'title' => trim($_POST['meta_title'] ?? ''),
                    'description' => trim($_POST['meta_description'] ?? ''),
                    'keywords' => trim($_POST['meta_keywords'] ?? ''),
                    'og_title' => trim($_POST['meta_og_title'] ?? ''),
                    'og_desc' => trim($_POST['meta_og_desc'] ?? ''),
                ];
                if ($pageSlug === 'home') {
                    $content['hero_title'] = $_POST['hero_title'] ?? '';
                    $content['hero_subtitle'] = $_POST['hero_subtitle'] ?? '';
                    $content['hero_cta'] = $_POST['hero_cta'] ?? '';
                    $content['hero_cta_link'] = $_POST['hero_cta_link'] ?? '';
                    $content['services_label'] = $_POST['services_label'] ?? '';
                    $content['services_heading'] = $_POST['services_heading'] ?? '';
                    $services = [];
                    for ($i = 0; $i < 4; $i++) {
                        $icon = trim($_POST["svc_{$i}_icon"] ?? '');
                        $title = trim($_POST["svc_{$i}_title"] ?? '');
                        $desc = trim($_POST["svc_{$i}_desc"] ?? '');
                        if ($icon || $title) $services[] = ['icon'=>$icon,'title'=>$title,'desc'=>$desc];
                    }
                    $content['services'] = $services;
                    $content['services_btn'] = $_POST['services_btn'] ?? '';
                    $content['services_btn_link'] = $_POST['services_btn_link'] ?? '';
                    $content['about_label'] = $_POST['about_label'] ?? '';
                    $content['about_heading'] = $_POST['about_heading'] ?? '';
                    $content['about_text'] = $_POST['about_text'] ?? '';
                    $content['about_btn'] = $_POST['about_btn'] ?? '';
                    $stats = [];
                    for ($i = 0; $i < 3; $i++) {
                        $num = trim($_POST["about_stat_{$i}_number"] ?? '');
                        $lab = trim($_POST["about_stat_{$i}_label"] ?? '');
                        if ($num || $lab) $stats[] = ['number'=>$num,'label'=>$lab];
                    }
                    $content['about_stats'] = $stats;
                    $content['cta_heading'] = $_POST['cta_heading'] ?? '';
                    $content['cta_text'] = $_POST['cta_text'] ?? '';
                    $content['cta_btn1'] = $_POST['cta_btn1'] ?? '';
                    $content['cta_btn1_link'] = $_POST['cta_btn1_link'] ?? '';
                    $content['cta_btn2'] = $_POST['cta_btn2'] ?? '';
                    $content['cta_btn2_link'] = $_POST['cta_btn2_link'] ?? '';
                } elseif ($pageSlug === 'about') {
                    $content['story_label'] = $_POST['story_label'] ?? '';
                    $content['story_heading'] = $_POST['story_heading'] ?? '';
                    $content['story_p1'] = $_POST['story_p1'] ?? '';
                    $content['story_p2'] = $_POST['story_p2'] ?? '';
                    $content['story_image'] = $_POST['story_image'] ?? '';
                    $stats = [];
                    for ($i = 0; $i < 3; $i++) {
                        $num = trim($_POST["story_stat_{$i}_number"] ?? '');
                        $lab = trim($_POST["story_stat_{$i}_label"] ?? '');
                        if ($num || $lab) $stats[] = ['number'=>$num,'label'=>$lab];
                    }
                    $content['story_stats'] = $stats;
                    $content['skills_label'] = $_POST['skills_label'] ?? '';
                    $content['skills_heading'] = $_POST['skills_heading'] ?? '';
                    $skills = [];
                    for ($i = 0; $i < 4; $i++) {
                        $icon = trim($_POST["skill_{$i}_icon"] ?? '');
                        $title = trim($_POST["skill_{$i}_title"] ?? '');
                        $desc = trim($_POST["skill_{$i}_desc"] ?? '');
                        $tags = array_filter(array_map('trim', explode(',', $_POST["skill_{$i}_tags"] ?? '')));
                        if ($icon || $title) $skills[] = ['icon'=>$icon,'title'=>$title,'desc'=>$desc,'tags'=>array_values($tags)];
                    }
                    $content['skills'] = $skills;
                    $content['gallery_label'] = $_POST['gallery_label'] ?? '';
                    $content['gallery_heading'] = $_POST['gallery_heading'] ?? '';
                    $content['philosophy_quote'] = $_POST['philosophy_quote'] ?? '';
                    $content['philosophy_author'] = $_POST['philosophy_author'] ?? '';
                    $content['philosophy_role'] = $_POST['philosophy_role'] ?? '';
                    $content['cta_heading'] = $_POST['cta_heading'] ?? '';
                    $content['cta_text'] = $_POST['cta_text'] ?? '';
                    $content['cta_btn1'] = $_POST['cta_btn1'] ?? '';
                    $content['cta_btn2'] = $_POST['cta_btn2'] ?? '';
                } elseif ($pageSlug === 'service') {
                    $content['hero_label'] = $_POST['hero_label'] ?? '';
                    $content['hero_heading'] = $_POST['hero_heading'] ?? '';
                    $content['hero_text'] = $_POST['hero_text'] ?? '';
                    $services = [];
                    $svcCount = 0;
                    for ($si = 0; $si < 10; $si++) {
                        $name = trim($_POST["svc_{$si}_name"] ?? '');
                        if (!$name) continue;
                        $svcCount++;
                        $items = array_filter(array_map('trim', explode("\n", $_POST["svc_{$si}_items"] ?? '')));
                        $tools = array_filter(array_map('trim', explode(',', $_POST["svc_{$si}_tools"] ?? '')));
                        $services[] = [
                            'name'=>$name,
                            'desc'=>trim($_POST["svc_{$si}_desc"] ?? ''),
                            'icon'=>trim($_POST["svc_{$si}_icon"] ?? ''),
                            'items'=>array_values($items),
                            'tools'=>array_values($tools),
                            'delivery'=>trim($_POST["svc_{$si}_delivery"] ?? '')
                        ];
                    }
                    $content['services'] = $services;
                    $content['process_label'] = $_POST['process_label'] ?? '';
                    $content['process_heading'] = $_POST['process_heading'] ?? '';
                    $steps = [];
                    for ($i = 0; $i < 4; $i++) {
                        $title = trim($_POST["step_{$i}_title"] ?? '');
                        if (!$title) continue;
                        $steps[] = [
                            'number'=>trim($_POST["step_{$i}_number"] ?? ''),
                            'title'=>$title,
                            'desc'=>trim($_POST["step_{$i}_desc"] ?? '')
                        ];
                    }
                    $content['process_steps'] = $steps;
                    $content['pricing_heading'] = $_POST['pricing_heading'] ?? '';
                    $content['pricing_text'] = $_POST['pricing_text'] ?? '';
                    $content['pricing_btn'] = $_POST['pricing_btn'] ?? '';
                } elseif ($pageSlug === 'contact') {
                    $content['hero_label'] = $_POST['hero_label'] ?? '';
                    $content['hero_heading'] = $_POST['hero_heading'] ?? '';
                    $content['hero_text'] = $_POST['hero_text'] ?? '';
                    $cards = [];
                    for ($i = 0; $i < 4; $i++) {
                        $icon = trim($_POST["info_{$i}_icon"] ?? '');
                        $title = trim($_POST["info_{$i}_title"] ?? '');
                        $value = trim($_POST["info_{$i}_value"] ?? '');
                        if ($icon || $title) $cards[] = ['icon'=>$icon,'title'=>$title,'value'=>$value];
                    }
                    $content['info_cards'] = $cards;
                    $content['newsletter_title'] = $_POST['newsletter_title'] ?? '';
                    $content['newsletter_text'] = $_POST['newsletter_text'] ?? '';
                    $content['social_label'] = $_POST['social_label'] ?? '';
                    $content['social_heading'] = $_POST['social_heading'] ?? '';
                    $content['social_text'] = $_POST['social_text'] ?? '';
                }
                $json = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $stmt = $db->prepare("INSERT INTO pages (page_slug, content) VALUES (?, ?) ON DUPLICATE KEY UPDATE content = ?");
                $stmt->execute([$pageSlug, $json, $json]);
                $msg = ucfirst($pageSlug) . ' page content saved.'; $msgType = 'success';
            }
        }
        if ($action === 'seed_pages') {
            $pages = [
                'home' => json_encode([
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
                    'story_label' => 'My Story',
                    'story_heading' => 'From Curiosity to <span class="highlight">Craft</span>',
                    'story_p1' => "What started as curiosity about how websites work turned into a deep passion for building digital experiences. Over the years, I've worked across disciplines \u2014 writing code, designing interfaces, and building brand identities that leave lasting impressions.",
                    'story_p2' => "I believe great design isn't just how something looks \u2014 it's how it works, how it feels, and the story it tells. Every project I take on is a chance to push boundaries and create something meaningful.",
                    'story_image' => 'assets/imgs/photo/deep-design-202301.jpg',
                    'story_stats' => [
                        ['number'=>'50+','label'=>'Projects Delivered'],
                        ['number'=>'3+','label'=>'Years Experience'],
                        ['number'=>'30+','label'=>'Happy Clients']
                    ],
                    'skills_label' => 'What I Do',
                    'skills_heading' => 'Skills & <span class="highlight">Expertise</span>',
                    'skills' => [
                        ['icon'=>'code','title'=>'Web Development','desc'=>'Building fast, responsive, and modern websites with clean, maintainable code. From landing pages to full web applications.','tags'=>['HTML/CSS','JavaScript','React','Node.js']],
                        ['icon'=>'palette','title'=>'Graphic Design','desc'=>'Creating visually striking designs that communicate ideas clearly. From posters and social media to complete visual systems.','tags'=>['Photoshop','Illustrator','Figma','InDesign']],
                        ['icon'=>'devices','title'=>'UI/UX Design','desc'=>'Designing intuitive, user-centered interfaces that look beautiful and feel effortless to navigate.','tags'=>['Figma','Wireframing','Prototyping','User Research']],
                        ['icon'=>'branding_watermark','title'=>'Brand Identity','desc'=>"Building cohesive brand identities that tell a story \u2014 logos, color systems, typography, and brand guidelines that stick.",'tags'=>['Logo Design','Brand Guidelines','Typography','Color Theory']]
                    ],
                    'gallery_label' => 'Behind the Work',
                    'gallery_heading' => 'A Glimpse <span class="highlight">Into My World</span>',
                    'philosophy_quote' => "I don't just build websites or design graphics \u2014 I craft experiences. Every pixel, every line of code, every color choice is intentional. My goal is to make digital products that people remember and love to use.",
                    'philosophy_author' => 'Deep Design Hubs',
                    'philosophy_role' => 'Developer & Designer',
                    'cta_heading' => 'Have a project in mind?',
                    'cta_text' => "I'm always open to new opportunities and creative collaborations.",
                    'cta_btn1' => 'Get in Touch',
                    'cta_btn2' => 'View My Work'
                ], JSON_UNESCAPED_SLASHES),
                'service' => json_encode([
                    'hero_label' => 'What I Offer',
                    'hero_heading' => 'Services Built Around <span class="highlight">Your Vision</span>',
                    'hero_text' => "From concept to launch \u2014 I deliver end-to-end design and development solutions tailored to your goals.",
                    'services' => [
                        ['name'=>'Web Development','desc'=>'Fast, responsive, modern websites built with clean code.','icon'=>'code','items'=>['Custom responsive website design & development','Single-page applications (SPA) & multi-page sites','Cross-browser & device testing','Performance optimization & fast load times','SEO-ready semantic HTML structure','Contact forms, CMS integration, APIs'],'tools'=>['HTML5','CSS3','JavaScript','React','Vue.js','Node.js','PHP','MySQL','REST APIs','Git','Netlify','Vercel'],'delivery'=>'1\u20133 weeks'],
                        ['name'=>'Graphic Design','desc'=>'Striking visuals that communicate ideas and capture attention.','icon'=>'palette','items'=>['Social media graphics & content packs','Posters, flyers, brochures & print materials','Presentations & pitch decks','Infographics & data visualizations','Photo editing & manipulation','Custom illustrations & icons'],'tools'=>['Photoshop','Illustrator','InDesign','Figma','After Effects','Procreate'],'delivery'=>'3\u20137 days'],
                        ['name'=>'UI/UX Design','desc'=>'Intuitive, beautiful interfaces that users love to navigate.','icon'=>'devices','items'=>['User research & persona development','Wireframing & information architecture','High-fidelity UI mockups','Interactive prototypes & click-through demos','Design system & component library','Usability testing & iteration'],'tools'=>['Figma','Adobe XD','Sketch','Miro','FigJam','InVision'],'delivery'=>'1\u20134 weeks'],
                        ['name'=>'Brand Identity','desc'=>'Cohesive brand systems that tell your story and stick.','icon'=>'branding_watermark','items'=>['Logo design (primary, secondary, icon)','Color palette & typography system','Brand guidelines document','Business cards, letterheads, envelopes','Social media brand kit','Brand strategy & positioning'],'tools'=>['Illustrator','Photoshop','Figma','InDesign'],'delivery'=>'2\u20134 weeks'],
                        ['name'=>'Motion Graphics','desc'=>'Dynamic animations that bring ideas to life on screen.','icon'=>'animation','items'=>['Logo animations & intros/outros','Social media animated content','Explainer video animations','UI micro-interactions & transitions','Animated presentations','Lottie/JSON animations for web'],'tools'=>['After Effects','Premiere Pro','Lottie','Rive','CSS Animations','GSAP'],'delivery'=>'1\u20133 weeks']
                    ],
                    'process_label' => 'How I Work',
                    'process_heading' => 'From Idea to <span class="highlight-dark">Launch</span>',
                    'process_steps' => [
                        ['number'=>'01','title'=>'Discover','desc'=>"We discuss your goals, audience, and vision. I research your industry and competitors."],
                        ['number'=>'02','title'=>'Design','desc'=>"Wireframes, mockups, and prototypes. You see the concept before a single line of code."],
                        ['number'=>'03','title'=>'Develop','desc'=>"Clean, performant code. Every page is tested across devices and browsers."],
                        ['number'=>'04','title'=>'Launch','desc'=>"Final review, deployment, and handoff. Your project goes live \u2014 and I'm still here if you need me."]
                    ],
                    'pricing_heading' => 'Pricing That Fits Your Budget',
                    'pricing_text' => "Every project is different. I offer flexible pricing based on scope, timeline, and complexity. Whether it's a small brand refresh or a full web build \u2014 let's talk and find what works for you.",
                    'pricing_btn' => 'Get a Quote'
                ], JSON_UNESCAPED_SLASHES),
                'contact' => json_encode([
                    'hero_label' => 'Get in Touch',
                    'hero_heading' => "Let's Build Something <span class=\"highlight\">Great Together</span>",
                    'hero_text' => "Have a project in mind? Whether it's web development, graphic design, UI/UX, or brand identity \u2014 I'd love to hear about it. I respond within 24 hours.",
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
                    'social_text' => "Stay updated with my latest work, design tips, and creative projects. Let's build a connection beyond this page."
                ], JSON_UNESCAPED_SLASHES)
            ];
            foreach ($pages as $slug => $json) {
                $stmt = $db->prepare("INSERT INTO pages (page_slug, content) VALUES (?, ?) ON DUPLICATE KEY UPDATE content = ?");
                $stmt->execute([$slug, $json, $json]);
            }
            $msg = 'All pages seeded with default content.'; $msgType = 'success';
        }

        // ========== PORTFOLIO ACTIONS ==========
        if ($action === 'change_credentials') {
            $currentPass = $_POST['current_pass'] ?? '';
            $newUser = trim($_POST['new_user'] ?? '');
            $newPass = $_POST['new_pass'] ?? '';
            if ($currentPass !== getAdminPass()) {
                $msg = 'Current password is incorrect.'; $msgType = 'error';
            } else {
                if (!empty($newUser)) setAdminSetting('admin_user', $newUser);
                if (!empty($newPass)) setAdminSetting('admin_pass', $newPass);
                $msg = 'Credentials updated successfully.'; $msgType = 'success';
            }
        }
        if ($action === 'add_portfolio') {
            $slug = trim($_POST['slug'] ?? '');
            if (empty($slug)) $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($_POST['title'])), '-'));
            $db->prepare("INSERT INTO portfolio (title, slug, description, image, category, tags, project_url, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
               ->execute([trim($_POST['title']), $slug, trim($_POST['description']), trim($_POST['image']), trim($_POST['category']), trim($_POST['tags']), trim($_POST['project_url']), (int)($_POST['sort_order']??0)]);
            $msg = 'Project added.'; $msgType = 'success';
        }
        if ($action === 'update_portfolio') {
            $slug = trim($_POST['slug'] ?? '');
            if (empty($slug)) $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($_POST['title'])), '-'));
            $db->prepare("UPDATE portfolio SET title=?, slug=?, description=?, image=?, category=?, tags=?, project_url=?, sort_order=?, is_visible=? WHERE id=?")
               ->execute([trim($_POST['title']), $slug, trim($_POST['description']), trim($_POST['image']), trim($_POST['category']), trim($_POST['tags']), trim($_POST['project_url']), (int)$_POST['sort_order'], isset($_POST['is_visible'])?1:0, (int)$_POST['id']]);
            $msg = 'Project updated.'; $msgType = 'success';
        }
        if ($action === 'delete_portfolio') {
            $id = (int)$_POST['id'];
            $row = $db->prepare("SELECT image FROM portfolio WHERE id = ?");
            $row->execute([$id]);
            $p = $row->fetch();
            if ($p && strpos($p['image'], 'uploads/') === 0) {
                $file = __DIR__ . '/' . $p['image'];
                if (file_exists($file)) unlink($file);
            }
            $db->prepare("DELETE FROM portfolio WHERE id = ?")->execute([$id]);
            $msg = 'Project deleted.'; $msgType = 'success';
        }
        if ($action === 'upload_portfolio_image') {
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['image'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','gif','webp']) && $file['size'] <= 10*1024*1024) {
                    $filename = 'proj_'.uniqid().'.'.$ext;
                    move_uploaded_file($file['tmp_name'], __DIR__.'/uploads/'.$filename);
                    echo json_encode(['success'=>true, 'url'=>'uploads/'.$filename]);
                    exit;
                }
                echo json_encode(['success'=>false, 'message'=>'Invalid file type or too large']);
                exit;
            }
            echo json_encode(['success'=>false, 'message'=>'No file uploaded']);
            exit;
        }
        if ($action === 'upload_gallery_image') {
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['image'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','gif','webp']) && $file['size'] <= 10*1024*1024) {
                    $filename = 'gal_'.uniqid().'.'.$ext;
                    move_uploaded_file($file['tmp_name'], __DIR__.'/uploads/'.$filename);
                    echo json_encode(['success'=>true, 'url'=>'uploads/'.$filename]);
                    exit;
                }
                echo json_encode(['success'=>false, 'message'=>'Invalid file type or too large']);
                exit;
            }
            echo json_encode(['success'=>false, 'message'=>'No file uploaded']);
            exit;
        }
        if ($action === 'seed_portfolio') {
            $projects = [
                ['E-Commerce Platform','A full-stack online store with product management, cart system, and secure checkout.','uploads/proj_ecommerce.jpg','web','React, Node.js, Stripe','',1],
                ['Luxe Brand Identity','Complete brand system for a luxury lifestyle brand — logo, color palette, typography.','uploads/proj_branding.jpg','branding','Illustrator, Photoshop, InDesign','',2],
                ['FinTrack Dashboard','Modern financial analytics dashboard with real-time data visualization.','uploads/proj_dashboard.jpg','uiux','Figma, Prototyping, Design System','',3],
                ['Art Festival Campaign','Visual identity and promotional materials for an annual art festival.','uploads/proj_festival.jpg','graphic','Photoshop, Illustrator, Print','',4],
                ['DevConnect Platform','Developer community platform with profiles, project showcases, and messaging.','uploads/proj_devconnect.jpg','web','Vue.js, Firebase, Tailwind','',5],
                ['GreenLeaf Rebrand','Brand refresh for eco-friendly products — logo, packaging, social media kit.','uploads/proj_greenleaf.jpg','branding','Logo Design, Packaging, Guidelines','',6],
                ['MediCare App','Healthcare appointment booking app with patient profiles and telehealth.','uploads/proj_medicare.jpg','uiux','Figma, Wireframing, User Research','',7],
                ['Brand Packaging Set','Product packaging design collection for a gourmet food brand.','uploads/proj_packaging.jpg','graphic','Illustrator, Photoshop, Print','',8],
                ['PropertyFinder Website','Real estate listing platform with advanced search and interactive maps.','uploads/proj_property.jpg','web','React, PHP, MySQL','',9],
            ];
            $stmt = $db->prepare("INSERT IGNORE INTO portfolio (title, description, image, category, tags, project_url, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($projects as $p) $stmt->execute($p);
            $msg = 'Portfolio seeded with sample projects.'; $msgType = 'success';
        }
    } catch (Exception $e) {
        $msg = 'Error: '.$e->getMessage(); $msgType = 'error';
    }
}

// ========== STATS ==========
$stats = ['contacts'=>0,'unread'=>0,'subscribers'=>0,'images'=>0,'newsletters'=>0,'projects'=>0];
try {
    $stats['contacts'] = $db->query("SELECT COUNT(*) FROM contacts")->fetchColumn();
    $stats['unread'] = $db->query("SELECT COUNT(*) FROM contacts WHERE is_read=0")->fetchColumn();
    $stats['subscribers'] = $db->query("SELECT COUNT(*) FROM subscribers WHERE is_active=1")->fetchColumn();
    $stats['images'] = $db->query("SELECT COUNT(*) FROM gallery_images")->fetchColumn();
    $stats['newsletters'] = $db->query("SELECT COUNT(*) FROM newsletter_log")->fetchColumn();
    $stats['projects'] = $db->query("SELECT COUNT(*) FROM portfolio")->fetchColumn();
} catch (Exception $e) {}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Deep Design Hubs — Admin Panel</title>
<link rel="icon" type="image/png" href="https://deep-design.netlify.app/assets/imgs/logo/favicon.png">
<link rel="shortcut icon" href="https://deep-design.netlify.app/assets/imgs/logo/favicon.png">
<link rel="apple-touch-icon" sizes="180x180" href="https://deep-design.netlify.app/assets/imgs/logo/favicon.png">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:#0a0a0a;color:#e5e5e5;min-height:100vh}
a{text-decoration:none;color:inherit}

/* ===== HEADER ===== */
.admin-header{position:fixed;top:0;left:0;right:0;height:56px;background:#111;border-bottom:1px solid #222;display:flex;align-items:center;padding:0 24px;z-index:100;gap:16px}
.admin-header .logo{display:flex;align-items:center;gap:10px;font-size:16px;font-weight:700;color:#fff;letter-spacing:-0.5px;white-space:nowrap}
.admin-header .logo svg{width:22px;height:22px}
.admin-header .logo span{color:#666;font-weight:400;font-size:14px;margin-left:4px}
.admin-header .header-search{flex:1;max-width:360px;margin:0 auto}
.admin-header .header-search input{width:100%;padding:8px 14px;background:#1a1a1a;border:1px solid #333;border-radius:8px;color:#fff;font-size:13px;font-family:inherit}
.admin-header .header-search input:focus{outline:none;border-color:#555}
.admin-header .header-search input::placeholder{color:#555}
.admin-header .header-right{display:flex;align-items:center;gap:12px;white-space:nowrap}
.admin-header .header-right .site-link{font-size:12px;color:#666;padding:6px 12px;border:1px solid #333;border-radius:6px;transition:all .15s}
.admin-header .header-right .site-link:hover{color:#fff;border-color:#666}
.admin-header .header-right .admin-avatar{width:32px;height:32px;background:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#000}
.admin-header .header-right .logout-btn{font-size:12px;color:#666;cursor:pointer;padding:6px 12px;border:1px solid #333;border-radius:6px;background:none;font-family:inherit;transition:all .15s}
.admin-header .header-right .logout-btn:hover{color:#ef4444;border-color:#7f1d1d}

/* ===== LAYOUT ===== */
.layout{display:flex;min-height:100vh;padding-top:56px}

/* ===== SIDEBAR ===== */
.sidebar{width:220px;background:#111;border-right:1px solid #222;position:fixed;top:56px;left:0;bottom:0;display:flex;flex-direction:column;z-index:50}
.sidebar-nav{flex:1;padding:12px 0}
.sidebar-nav a{display:flex;align-items:center;gap:10px;padding:10px 18px;font-size:13px;color:#777;transition:all .15s;border-left:2px solid transparent}
.sidebar-nav a:hover{color:#ddd;background:#161616}
.sidebar-nav a.active{color:#fff;background:#1a1a1a;border-left-color:#fff}
.sidebar-nav a .nav-icon{width:18px;text-align:center;font-size:15px}
.sidebar-nav a .badge{margin-left:auto;background:#fff;color:#000;font-size:10px;font-weight:700;padding:2px 7px;border-radius:8px;min-width:18px;text-align:center}
.sidebar-section{padding:16px 18px 8px;font-size:10px;font-weight:600;color:#444;text-transform:uppercase;letter-spacing:1.5px}
.main{margin-left:220px;flex:1;padding:28px 32px;min-height:calc(100vh - 56px)}

/* ===== ALERTS ===== */
.alert{padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:13px;display:flex;align-items:center;gap:10px}
.alert-success{background:#052e16;border:1px solid #166534;color:#4ade80}
.alert-error{background:#450a0a;border:1px solid #991b1b;color:#fca5a5}
.alert-dismiss{margin-left:auto;cursor:pointer;opacity:.5;font-size:16px}
.alert-dismiss:hover{opacity:1}

/* ===== PAGE HEADER ===== */
.page-header{margin-bottom:28px;display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap}
.page-header h1{font-size:24px;font-weight:700;color:#fff}
.page-header p{font-size:13px;color:#666;margin-top:2px}

/* ===== STATS ===== */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:28px}
.stat-card{background:#111;border:1px solid #222;border-radius:10px;padding:20px}
.stat-card .stat-num{font-size:32px;font-weight:700;color:#fff;line-height:1}
.stat-card .stat-label{font-size:11px;color:#666;text-transform:uppercase;letter-spacing:1px;margin-top:6px}
.stat-card .stat-sub{font-size:11px;color:#4ade80;margin-top:3px}

/* ===== CARDS ===== */
.card{background:#111;border:1px solid #222;border-radius:10px;padding:22px;margin-bottom:20px}
.card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px}
.card-header h2{font-size:15px;font-weight:600;color:#fff}

/* ===== TABLES ===== */
table{width:100%;border-collapse:collapse}
th{text-align:left;padding:8px 12px;font-size:10px;font-weight:600;color:#555;text-transform:uppercase;letter-spacing:1px;border-bottom:1px solid #222}
td{padding:8px 12px;font-size:13px;color:#bbb;border-bottom:1px solid #1a1a1a}
tr:hover td{background:#141414}
tr.unread td{color:#fff}
tr.unread td:first-child{border-left:3px solid #4ade80}

/* ===== BUTTONS ===== */
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer;border:none;font-family:inherit;transition:all .12s;white-space:nowrap}
.btn-primary{background:#fff;color:#000}.btn-primary:hover{background:#e5e5e5}
.btn-sm{padding:4px 10px;font-size:11px}
.btn-ghost{background:transparent;color:#888;border:1px solid #333}.btn-ghost:hover{color:#fff;border-color:#555}
.btn-danger{background:transparent;color:#ef4444;border:1px solid #7f1d1d}.btn-danger:hover{background:#7f1d1d;color:#fff}
.btn-success{background:transparent;color:#4ade80;border:1px solid #166534}.btn-success:hover{background:#166534;color:#fff}
.btn-group{display:flex;gap:8px;flex-wrap:wrap}

/* ===== FORMS ===== */
.form-group{margin-bottom:14px}
.form-group label{display:block;font-size:11px;font-weight:600;color:#666;text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px}
.form-input{width:100%;padding:9px 12px;background:#0a0a0a;border:1px solid #333;border-radius:6px;color:#fff;font-family:inherit;font-size:13px;transition:border .12s}
.form-input:focus{outline:none;border-color:#555}
textarea.form-input{resize:vertical;min-height:70px}
select.form-input{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 10 10'%3E%3Cpath fill='%23666' d='M5 7L0 2h10z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 10px center}
select.form-input option{background:#111;color:#fff}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.form-row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px}
.form-check{display:flex;align-items:center;gap:6px;font-size:12px;color:#bbb}
.form-check input[type=checkbox]{width:15px;height:15px;accent-color:#fff}

/* ===== GALLERY GRID ===== */
.gallery-admin-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px}
.gallery-item{background:#0a0a0a;border:1px solid #222;border-radius:8px;overflow:hidden}
.gallery-item img{width:100%;height:140px;object-fit:cover;display:block}
.gallery-item .gi-info{padding:10px}
.gallery-item .gi-info h4{font-size:12px;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gallery-item .gi-info p{font-size:10px;color:#555;margin-top:2px}
.gallery-item .gi-badge{position:absolute;top:6px;right:6px;background:rgba(0,0,0,.7);color:#4ade80;font-size:9px;font-weight:600;padding:2px 6px;border-radius:3px;text-transform:uppercase}
.gallery-item .gi-badge.hidden{color:#555}
.gallery-item .gi-actions{display:flex;gap:4px;padding:0 10px 10px}
.gallery-item{position:relative}

/* ===== PORTFOLIO GRID ===== */
.portfolio-admin-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px}
.portfolio-item{background:#0a0a0a;border:1px solid #222;border-radius:10px;overflow:hidden;display:flex;flex-direction:column}
.portfolio-item .pi-image{height:160px;overflow:hidden;position:relative}
.portfolio-item .pi-image img{width:100%;height:100%;object-fit:cover;display:block}
.portfolio-item .pi-badge{position:absolute;top:8px;left:8px;background:rgba(0,0,0,.7);color:#fff;font-size:10px;font-weight:600;padding:3px 8px;border-radius:4px;text-transform:uppercase}
.portfolio-item .pi-body{padding:14px;flex:1;display:flex;flex-direction:column}
.portfolio-item .pi-body h4{font-size:14px;font-weight:600;color:#fff;margin-bottom:4px}
.portfolio-item .pi-body p{font-size:11px;color:#888;line-height:1.5;margin-bottom:8px;flex:1;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.portfolio-item .pi-tags{display:flex;flex-wrap:wrap;gap:4px;margin-bottom:10px}
.portfolio-item .pi-tags span{font-size:9px;padding:2px 7px;background:#1a1a1a;border:1px solid #333;border-radius:4px;color:#888}
.portfolio-item .pi-actions{display:flex;gap:6px;padding-top:8px;border-top:1px solid #222}

/* ===== MODAL ===== */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:200;align-items:center;justify-content:center;padding:20px}
.modal-overlay.active{display:flex}
.modal{background:#111;border:1px solid #333;border-radius:12px;width:100%;max-width:520px;max-height:90vh;overflow-y:auto;padding:24px}
.modal h3{font-size:16px;font-weight:600;color:#fff;margin-bottom:18px}
.modal-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:20px}

/* ===== MESSAGE VIEW ===== */
.msg-view{background:#0a0a0a;border:1px solid #222;border-radius:8px;padding:16px;margin-bottom:12px}
.msg-view .msg-meta{display:flex;gap:12px;margin-bottom:10px;font-size:11px;color:#555;flex-wrap:wrap}
.msg-view .msg-meta strong{color:#ccc}
.msg-view .msg-body{font-size:13px;color:#aaa;line-height:1.7;white-space:pre-wrap}

/* ===== EMPTY ===== */
.empty{text-align:center;padding:40px 16px;color:#444}
.empty .empty-icon{font-size:40px;margin-bottom:10px;opacity:.3}
.empty p{font-size:13px}

/* ===== TABS ===== */
.tab-bar{display:flex;gap:0;border-bottom:1px solid #222;margin-bottom:20px}
.tab-bar a{padding:8px 16px;font-size:12px;font-weight:500;color:#555;border-bottom:2px solid transparent;transition:all .12s}
.tab-bar a:hover{color:#bbb}
.tab-bar a.active{color:#fff;border-bottom-color:#fff}

/* ===== SCROLLBAR ===== */
::-webkit-scrollbar{width:5px}::-webkit-scrollbar-track{background:transparent}::-webkit-scrollbar-thumb{background:#333;border-radius:3px}

@media(max-width:768px){
    .sidebar{display:none}.main{margin-left:0;padding:16px}
    .form-row,.form-row-3{grid-template-columns:1fr}
    .admin-header .header-search{display:none}
}
</style>
</head>
<body>

<!-- ===== HEADER ===== -->
<header class="admin-header">
    <div class="logo">
        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
        Deep Design Hubs <span>Admin</span>
    </div>
    <div class="header-search">
        <input type="text" placeholder="Search..." id="globalSearch" onkeyup="handleSearch(this.value)">
    </div>
    <div class="header-right">
        <a href="../" target="_blank" class="site-link">View Site &rarr;</a>
        <div class="admin-avatar"><?= strtoupper(substr(ADMIN_USER,0,1)) ?></div>
        <a href="?logout" class="logout-btn">Logout</a>
    </div>
</header>

<div class="layout">
    <!-- ===== SIDEBAR ===== -->
    <aside class="sidebar">
        <nav class="sidebar-nav">
            <div class="sidebar-section">Content</div>
            <a href="?tab=dashboard" class="<?= $tab==='dashboard'?'active':'' ?>"><span class="nav-icon">&#9632;</span>Dashboard</a>
            <a href="?tab=portfolio" class="<?= $tab==='portfolio'?'active':'' ?>"><span class="nav-icon">&#9636;</span>Portfolio<?php if($stats['projects']): ?><span class="badge"><?= $stats['projects'] ?></span><?php endif; ?></a>
            <a href="?tab=gallery" class="<?= $tab==='gallery'?'active':'' ?>"><span class="nav-icon">&#9638;</span>Gallery<?php if($stats['images']): ?><span class="badge"><?= $stats['images'] ?></span><?php endif; ?></a>
            <div class="sidebar-section">Communication</div>
            <a href="?tab=contacts" class="<?= $tab==='contacts'?'active':'' ?>"><span class="nav-icon">&#9993;</span>Contacts<?php if($stats['unread']): ?><span class="badge"><?= $stats['unread'] ?></span><?php endif; ?></a>
            <a href="?tab=subscribers" class="<?= $tab==='subscribers'?'active':'' ?>"><span class="nav-icon">&#10084;</span>Subscribers<?php if($stats['subscribers']): ?><span class="badge"><?= $stats['subscribers'] ?></span><?php endif; ?></a>
            <a href="?tab=newsletter" class="<?= $tab==='newsletter'?'active':'' ?>"><span class="nav-icon">&#9998;</span>Newsletter</a>
            <div class="sidebar-section">Pages</div>
            <a href="?tab=page-home" class="<?= $tab==='page-home'?'active':'' ?>"><span class="nav-icon">&#8962;</span>Home</a>
            <a href="?tab=page-about" class="<?= $tab==='page-about'?'active':'' ?>"><span class="nav-icon">&#9734;</span>About</a>
            <a href="?tab=page-service" class="<?= $tab==='page-service'?'active':'' ?>"><span class="nav-icon">&#9881;</span>Services</a>
            <a href="?tab=page-contact" class="<?= $tab==='page-contact'?'active':'' ?>"><span class="nav-icon">&#9993;</span>Contact</a>
            <div class="sidebar-section">System</div>
            <a href="?tab=settings" class="<?= $tab==='settings'?'active':'' ?>"><span class="nav-icon">&#9881;</span>Settings</a>
        </nav>
    </aside>

    <main class="main">
    <?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?><span class="alert-dismiss" onclick="this.parentElement.remove()">&times;</span></div>
    <?php endif; ?>

<?php if ($tab === 'dashboard'): ?>
        <div class="page-header"><div><h1>Dashboard</h1><p>Welcome back, <?= htmlspecialchars($_SESSION['admin_user'] ?? 'Admin') ?></p></div></div>
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-num"><?= $stats['projects'] ?></div><div class="stat-label">Projects</div></div>
            <div class="stat-card"><div class="stat-num"><?= $stats['images'] ?></div><div class="stat-label">Gallery Images</div></div>
            <div class="stat-card"><div class="stat-num"><?= $stats['contacts'] ?></div><div class="stat-label">Contacts</div><?php if($stats['unread']): ?><div class="stat-sub"><?= $stats['unread'] ?> unread</div><?php endif; ?></div>
            <div class="stat-card"><div class="stat-num"><?= $stats['subscribers'] ?></div><div class="stat-label">Subscribers</div></div>
            <div class="stat-card"><div class="stat-num"><?= $stats['newsletters'] ?></div><div class="stat-label">Newsletters Sent</div></div>
        </div>
        <div class="card">
            <div class="card-header"><h2>Recent Contacts</h2><a href="?tab=contacts" class="btn btn-ghost btn-sm">View All</a></div>
            <table><thead><tr><th>Name</th><th>Email</th><th>Subject</th><th>Type</th><th>Date</th><th></th></tr></thead><tbody>
            <?php foreach ($db->query("SELECT * FROM contacts ORDER BY created_at DESC LIMIT 5")->fetchAll() as $r): ?>
            <tr class="<?= $r['is_read']?'':'unread' ?>"><td><?= htmlspecialchars($r['name']) ?></td><td><?= htmlspecialchars($r['email']) ?></td><td><?= htmlspecialchars($r['subject']?:'—') ?></td><td><?= ucfirst($r['type']) ?></td><td><?= date('M j, g:i a', strtotime($r['created_at'])) ?></td><td><?php if(!$r['is_read']): ?><form method="POST" style="display:inline"><input type="hidden" name="action" value="mark_read"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-ghost btn-sm">Read</button></form><?php endif; ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
        </div>

<?php elseif ($tab === 'portfolio'): ?>
        <div class="page-header"><div><h1>Portfolio</h1><p>Manage your project showcase</p></div></div>
        <div class="btn-group" style="margin-bottom:20px">
            <button class="btn btn-primary" onclick="openPortfolioModal()">+ Add Project</button>
            <form method="POST" style="display:inline"><input type="hidden" name="action" value="seed_portfolio"><button class="btn btn-ghost" onclick="return confirm('Add 9 sample projects?')">Seed Sample Projects</button></form>
        </div>
        <div class="portfolio-admin-grid">
        <?php foreach ($db->query("SELECT * FROM portfolio ORDER BY sort_order ASC, id DESC")->fetchAll() as $p): ?>
            <div class="portfolio-item">
                <div class="pi-image">
                    <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['title']) ?>" loading="lazy" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22200%22%3E%3Crect fill=%22%23222%22 width=%22400%22 height=%22200%22/%3E%3Ctext fill=%22%23555%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 font-size=%2214%22%3ENo Image%3C/text%3E%3C/svg%3E'">
                    <span class="pi-badge"><?= htmlspecialchars($p['category']) ?></span>
                    <?php if (!$p['is_visible']): ?><span class="pi-badge" style="left:8px;right:auto;background:#7f1d1d;color:#fca5a5">Hidden</span><?php endif; ?>
                </div>
                <div class="pi-body">
                    <h4><?= htmlspecialchars($p['title']) ?></h4>
                    <p><?= htmlspecialchars($p['description']) ?></p>
                    <div class="pi-tags"><?php foreach (array_filter(explode(',',$p['tags'])) as $t): ?><span><?= htmlspecialchars(trim($t)) ?></span><?php endforeach; ?></div>
                    <div class="pi-actions">
                        <button class="btn btn-ghost btn-sm" onclick='editPortfolio(<?= json_encode($p) ?>)'>Edit</button>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this project?')"><input type="hidden" name="action" value="delete_portfolio"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="btn btn-danger btn-sm">Delete</button></form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$db->query("SELECT COUNT(*) FROM portfolio")->fetchColumn()): ?>
            <div class="empty" style="grid-column:1/-1"><div class="empty-icon">&#9636;</div><p>No projects yet. Add one or seed sample data.</p></div>
        <?php endif; ?>
        </div>

        <!-- Portfolio Add/Edit Modal -->
        <div class="modal-overlay" id="portfolioModal">
            <div class="modal">
                <h3 id="portfolioModalTitle">Add Project</h3>
                <form method="POST" id="portfolioForm">
                    <input type="hidden" name="action" value="add_portfolio" id="portfolioAction">
                    <input type="hidden" name="id" id="pfId">
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" id="pfTitle" class="form-input" placeholder="Project title" required oninput="autoSlug()">
                    </div>
                    <div class="form-group">
                        <label>URL Slug <span style="color:#555;font-weight:400;text-transform:none;letter-spacing:0">(auto-generated, editable)</span></label>
                        <div style="display:flex;align-items:center;gap:0">
                            <span style="padding:9px 10px;background:#1a1a1a;border:1px solid #333;border-right:none;border-radius:6px 0 0 6px;font-size:13px;color:#555;white-space:nowrap">/portfolio/</span>
                            <input type="text" name="slug" id="pfSlug" class="form-input" placeholder="project-url-slug" style="border-radius:0 6px 6px 0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="pfDesc" class="form-input" rows="2" placeholder="Brief description"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Image</label>
                        <div style="display:flex;gap:8px;align-items:center">
                            <input type="text" name="image" id="pfImage" class="form-input" placeholder="Image URL or uploads/file.jpg" style="flex:1">
                            <label class="btn btn-ghost" style="cursor:pointer;margin:0">Upload<input type="file" accept="image/*" style="display:none" onchange="uploadProjImage(this)"></label>
                        </div>
                        <div id="pfImagePreview" style="margin-top:8px;display:none"><img src="" style="max-width:100%;max-height:120px;border-radius:6px;border:1px solid #333"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category" id="pfCategory" class="form-input">
                                <option value="web">Web Development</option>
                                <option value="branding">Branding</option>
                                <option value="uiux">UI/UX</option>
                                <option value="graphic">Graphic Design</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Sort Order</label>
                            <input type="number" name="sort_order" id="pfSort" class="form-input" value="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Tags (comma separated)</label>
                        <input type="text" name="tags" id="pfTags" class="form-input" placeholder="React, Node.js, Stripe">
                    </div>
                    <div class="form-group">
                        <label>External URL <span style="color:#555;font-weight:400;text-transform:none;letter-spacing:0">(optional live demo link)</span></label>
                        <input type="url" name="project_url" id="pfUrl" class="form-input" placeholder="https://example.com">
                    </div>
                    <div class="form-group" id="pfVisibleGroup" style="display:none">
                        <label class="form-check"><input type="checkbox" name="is_visible" id="pfVisible" value="1"> Visible on site</label>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-ghost" onclick="closePortfolioModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="portfolioSubmitBtn">Add Project</button>
                    </div>
                </form>
            </div>
        </div>

<?php elseif ($tab === 'gallery'): ?>
        <div class="page-header"><div><h1>Gallery</h1><p>Manage portfolio images</p></div></div>
        <div class="btn-group" style="margin-bottom:20px">
            <button class="btn btn-primary" onclick="document.getElementById('addUrlModal').classList.add('active')">+ Add URL</button>
            <button class="btn btn-primary" onclick="document.getElementById('uploadModal').classList.add('active')">+ Upload</button>
            <form method="POST" style="display:inline"><input type="hidden" name="action" value="seed_gallery"><button class="btn btn-ghost">Seed Samples</button></form>
        </div>
        <div class="gallery-admin-grid">
        <?php foreach ($db->query("SELECT * FROM gallery_images ORDER BY sort_order ASC, id DESC")->fetchAll() as $img): ?>
            <div class="gallery-item">
                <img src="<?= htmlspecialchars($img['src']) ?>" alt="" loading="lazy" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22300%22 height=%22200%22%3E%3Crect fill=%22%23222%22 width=%22300%22 height=%22200%22/%3E%3Ctext fill=%22%23555%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 font-size=%2212%22%3ENot found%3C/text%3E%3C/svg%3E'">
                <span class="gi-badge <?= $img['is_visible']?'':'hidden' ?>"><?= $img['is_visible']?$img['category']:'Hidden' ?></span>
                <div class="gi-info"><h4><?= htmlspecialchars($img['title']?:'Untitled') ?></h4><p><?= htmlspecialchars($img['description']?:'') ?></p></div>
                <div class="gi-actions">
                    <button class="btn btn-ghost btn-sm" onclick='editGallery(<?= json_encode($img) ?>)'>Edit</button>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')"><input type="hidden" name="action" value="delete_gallery"><input type="hidden" name="id" value="<?= $img['id'] ?>"><button class="btn btn-danger btn-sm">Del</button></form>
                </div>
            </div>
        <?php endforeach; if (!$db->query("SELECT COUNT(*) FROM gallery_images")->fetchColumn()): ?>
            <div class="empty" style="grid-column:1/-1"><div class="empty-icon">&#9638;</div><p>No images yet.</p></div>
        <?php endif; ?>
        </div>

        <div class="modal-overlay" id="addUrlModal"><div class="modal"><h3>Add Image URL</h3>
            <form method="POST"><input type="hidden" name="action" value="add_gallery_url">
                <div class="form-group"><label>URL</label><input type="url" name="src" class="form-input" placeholder="https://..." required></div>
                <div class="form-row"><div class="form-group"><label>Title</label><input type="text" name="title" class="form-input"></div><div class="form-group"><label>Category</label><select name="category" class="form-input"><option value="web">Web</option><option value="graphic">Graphic</option><option value="branding">Branding</option><option value="uiux">UI/UX</option><option value="photography">Photography</option></select></div></div>
                <div class="form-group"><label>Description</label><textarea name="description" class="form-input" rows="2"></textarea></div>
                <div class="form-group"><label>Sort</label><input type="number" name="sort_order" class="form-input" value="0" style="width:80px"></div>
                <div class="modal-actions"><button type="button" class="btn btn-ghost" onclick="this.closest('.modal-overlay').classList.remove('active')">Cancel</button><button type="submit" class="btn btn-primary">Add</button></div>
            </form>
        </div></div>

        <div class="modal-overlay" id="uploadModal"><div class="modal"><h3>Upload Image</h3>
            <form method="POST" enctype="multipart/form-data" action="gallery-upload.php">
                <div class="form-group"><label>File</label><input type="file" name="image" class="form-input" accept="image/*" required></div>
                <div class="form-row"><div class="form-group"><label>Title</label><input type="text" name="title" class="form-input"></div><div class="form-group"><label>Category</label><select name="category" class="form-input"><option value="web">Web</option><option value="graphic">Graphic</option><option value="branding">Branding</option><option value="uiux">UI/UX</option></select></div></div>
                <div class="form-group"><label>Description</label><textarea name="description" class="form-input" rows="2"></textarea></div>
                <div class="form-group"><label>Sort</label><input type="number" name="sort_order" class="form-input" value="0" style="width:80px"></div>
                <div class="modal-actions"><button type="button" class="btn btn-ghost" onclick="this.closest('.modal-overlay').classList.remove('active')">Cancel</button><button type="submit" class="btn btn-primary">Upload</button></div>
            </form>
        </div></div>

        <div class="modal-overlay" id="editGalleryModal"><div class="modal"><h3>Edit Image</h3>
            <form method="POST"><input type="hidden" name="action" value="update_gallery"><input type="hidden" name="id" id="egId">
                <div class="form-group">
                    <label>Image</label>
                    <div style="display:flex;gap:8px;align-items:center">
                        <input type="text" name="src" id="egSrc" class="form-input" placeholder="Image URL" style="flex:1">
                        <label class="btn btn-ghost" style="cursor:pointer;margin:0">Upload<input type="file" accept="image/*" style="display:none" onchange="uploadGalleryEditImage(this)"></label>
                    </div>
                    <div id="egImagePreview" style="margin-top:8px;display:none"><img src="" style="max-width:100%;max-height:120px;border-radius:6px;border:1px solid #333"></div>
                </div>
                <div class="form-group"><label>Title</label><input type="text" name="title" id="egTitle" class="form-input"></div>
                <div class="form-row"><div class="form-group"><label>Category</label><select name="category" id="egCategory" class="form-input"><option value="web">Web</option><option value="graphic">Graphic</option><option value="branding">Branding</option><option value="uiux">UI/UX</option><option value="photography">Photography</option></select></div><div class="form-group"><label>Sort</label><input type="number" name="sort_order" id="egSort" class="form-input"></div></div>
                <div class="form-group"><label>Description</label><textarea name="description" id="egDesc" class="form-input" rows="2"></textarea></div>
                <div class="form-group"><label class="form-check"><input type="checkbox" name="is_visible" id="egVisible" value="1"> Visible</label></div>
                <div class="modal-actions"><button type="button" class="btn btn-ghost" onclick="this.closest('.modal-overlay').classList.remove('active')">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
            </form>
        </div></div>

<?php elseif ($tab === 'contacts'): ?>
        <div class="page-header"><div><h1>Contacts</h1><p>All messages and project requests</p></div></div>
        <?php
        $filter = $_GET['filter'] ?? 'all';
        $where = '';
        if ($filter === 'unread') $where = ' WHERE is_read=0';
        elseif ($filter === 'requests') $where = " WHERE type='request'";
        $rows = $db->query("SELECT * FROM contacts $where ORDER BY created_at DESC")->fetchAll();
        ?>
        <div class="tab-bar">
            <a href="?tab=contacts&filter=all" class="<?= $filter==='all'?'active':'' ?>">All (<?= $stats['contacts'] ?>)</a>
            <a href="?tab=contacts&filter=unread" class="<?= $filter==='unread'?'active':'' ?>">Unread (<?= $stats['unread'] ?>)</a>
            <a href="?tab=contacts&filter=requests" class="<?= $filter==='requests'?'active':'' ?>">Requests</a>
        </div>
        <?php if (empty($rows)): ?><div class="empty"><div class="empty-icon">&#9993;</div><p>No messages.</p></div><?php endif; ?>
        <?php foreach ($rows as $r): ?>
        <div class="msg-view">
            <div class="msg-meta"><span><strong><?= htmlspecialchars($r['name']) ?></strong></span><span><?= htmlspecialchars($r['email']) ?></span><span><?= ucfirst($r['type']) ?></span><span><?= date('M j, Y g:i a', strtotime($r['created_at'])) ?></span><?php if($r['budget']): ?><span>Budget: <?= htmlspecialchars($r['budget']) ?></span><?php endif; ?></div>
            <?php if($r['subject']): ?><strong style="font-size:13px;color:#fff"><?= htmlspecialchars($r['subject']) ?></strong><?php endif; ?>
            <div class="msg-body"><?= htmlspecialchars($r['message']) ?></div>
            <div class="btn-group">
                <?php if(!$r['is_read']): ?><form method="POST" style="display:inline"><input type="hidden" name="action" value="mark_read"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-success btn-sm">Mark Read</button></form><?php endif; ?>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')"><input type="hidden" name="action" value="delete_contact"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-danger btn-sm">Delete</button></form>
                <a href="mailto:<?= htmlspecialchars($r['email']) ?>?subject=Re: Your inquiry at Deep Design Hubs" class="btn btn-ghost btn-sm">Open in Mail</a>
            </div>
            <div class="reply-box" style="margin-top:14px;border-top:1px solid #222;padding-top:14px;">
                <form method="POST">
                    <input type="hidden" name="action" value="reply_contact">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <div class="form-group"><label>Quick Reply to <?= htmlspecialchars($r['name']) ?></label><textarea name="reply_message" class="form-input" rows="3" placeholder="Type your reply..." required></textarea></div>
                    <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Send reply to <?= htmlspecialchars($r['email']) ?>?')">Send Reply</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>

<?php elseif ($tab === 'subscribers'): ?>
        <div class="page-header"><div><h1>Subscribers</h1><p>Newsletter subscribers</p></div></div>
        <?php $rows = $db->query("SELECT * FROM subscribers ORDER BY subscribed_at DESC")->fetchAll(); ?>
        <?php if (empty($rows)): ?><div class="empty"><div class="empty-icon">&#10084;</div><p>No subscribers yet.</p></div><?php else: ?>
        <div class="card"><table><thead><tr><th>Email</th><th>Status</th><th>Subscribed</th><th></th></tr></thead><tbody>
        <?php foreach ($rows as $r): ?>
        <tr><td><?= htmlspecialchars($r['email']) ?></td><td style="color:<?= $r['is_active']?'#4ade80':'#555' ?>"><?= $r['is_active']?'Active':'Inactive' ?></td><td><?= date('M j, Y', strtotime($r['subscribed_at'])) ?></td><td><form method="POST" style="display:inline" onsubmit="return confirm('Remove?')"><input type="hidden" name="action" value="delete_subscriber"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-danger btn-sm">Remove</button></form></td></tr>
        <?php endforeach; ?>
        </tbody></table></div><?php endif; ?>

<?php elseif ($tab === 'newsletter'): ?>
        <div class="page-header"><div><h1>Newsletter</h1><p>Send emails to subscribers</p></div></div>
        <div class="card"><form method="POST">
            <input type="hidden" name="action" value="send_newsletter">
            <div class="form-group"><label>Subject</label><input type="text" name="subject" class="form-input" placeholder="Subject" required></div>
            <div class="form-group"><label>Body (HTML, use {{email}} for personalization)</label><textarea name="body" class="form-input" rows="8" placeholder="Write newsletter..." required></textarea></div>
            <button type="submit" class="btn btn-primary" onclick="return confirm('Send to <?= $stats['subscribers'] ?> subscribers?')">Send to <?= $stats['subscribers'] ?> subscribers</button>
        </form></div>
        <div class="card"><div class="card-header"><h2>History</h2></div>
            <table><thead><tr><th>Subject</th><th>Sent To</th><th>Date</th></tr></thead><tbody>
            <?php foreach ($db->query("SELECT * FROM newsletter_log ORDER BY sent_at DESC LIMIT 20")->fetchAll() as $l): ?>
            <tr><td><?= htmlspecialchars($l['subject']) ?></td><td><?= $l['total_sent'] ?></td><td><?= date('M j, Y g:i a', strtotime($l['sent_at'])) ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
        </div>

<?php elseif ($tab === 'settings'): ?>
        <div class="page-header"><div><h1>Settings</h1><p>Configuration and diagnostics</p></div></div>
        <div class="card"><div class="card-header"><h2>Change Admin Credentials</h2></div>
            <form method="POST">
                <input type="hidden" name="action" value="change_credentials">
                <div class="form-row">
                    <div class="form-group"><label>Username</label><input type="text" name="new_user" class="form-input" value="<?= htmlspecialchars(getAdminUser()) ?>" required></div>
                    <div class="form-group"><label>New Password</label><input type="password" name="new_pass" class="form-input" placeholder="Leave blank to keep current"></div>
                </div>
                <div class="form-group"><label>Current Password (required to save)</label><input type="password" name="current_pass" class="form-input" placeholder="Enter current password" required></div>
                <button type="submit" class="btn btn-primary">Save Credentials</button>
            </form>
        </div>
        <div class="card"><div class="card-header"><h2>Site Info & Social Links</h2><p style="color:#666;margin-top:4px;font-size:13px">Edit contact info and social media links shown on the site</p></div>
            <?php
            $socialLinks = getAdminSetting('social_links', json_encode([
                ['label' => 'LinkedIn', 'url' => 'https://linkedin.com/in/', 'icon' => 'fab fa-linkedin'],
                ['label' => 'Twitter', 'url' => 'https://x.com/', 'icon' => 'fab fa-x-twitter'],
                ['label' => 'GitHub', 'url' => 'https://github.com/', 'icon' => 'fab fa-github'],
                ['label' => 'Dribbble', 'url' => 'https://dribbble.com/', 'icon' => 'fab fa-dribbble'],
                ['label' => 'Instagram', 'url' => 'https://instagram.com/', 'icon' => 'fab fa-instagram'],
                ['label' => 'Behance', 'url' => 'https://behance.net/', 'icon' => 'fab fa-behance'],
                ['label' => 'CodePen', 'url' => 'https://codepen.io/', 'icon' => 'fab fa-codepen'],
                ['label' => 'Figma', 'url' => 'https://figma.com/', 'icon' => 'fab fa-figma'],
            ]));
            $socialArr = json_decode($socialLinks, true);
            if (!is_array($socialArr)) $socialArr = [];
            $contactEmail = getAdminSetting('contact_email', 'abubakarmusa0987@gmail.com');
            $contactLocation = getAdminSetting('contact_location', 'Available for remote work worldwide');
            $contactResponse = getAdminSetting('contact_response', 'I\'ll respond within 24 hours');
            $whatsapp = getAdminSetting('whatsapp_number', '');
            ?>
            <form method="POST">
                <input type="hidden" name="action" value="save_site_info">
                <h3 style="font-size:14px;color:#888;margin-bottom:12px;text-transform:uppercase;letter-spacing:1px">Contact Information</h3>
                <div class="form-row">
                    <div class="form-group"><label>Email Address</label><input type="email" name="contact_email" class="form-input" value="<?= htmlspecialchars($contactEmail) ?>" required></div>
                    <div class="form-group"><label>Location / Tagline</label><input type="text" name="contact_location" class="form-input" value="<?= htmlspecialchars($contactLocation) ?>"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Response Time Text</label><input type="text" name="contact_response" class="form-input" value="<?= htmlspecialchars($contactResponse) ?>"></div>
                    <div class="form-group"><label>WhatsApp Number (digits only, e.g. 923001234567)</label><input type="text" name="whatsapp_number" class="form-input" value="<?= htmlspecialchars($whatsapp) ?>" placeholder="Leave empty to hide WhatsApp button"></div>
                </div>

                <h3 style="font-size:14px;color:#888;margin:20px 0 12px;text-transform:uppercase;letter-spacing:1px">Social Links</h3>
                <div id="social-links-list">
                <?php foreach ($socialArr as $i => $s): ?>
                    <div class="form-row" style="margin-bottom:6px" data-social-row="<?= $i ?>">
                        <div class="form-group" style="flex:1.5"><label>Label</label><input type="text" name="social[<?= $i ?>][label]" class="form-input" value="<?= htmlspecialchars($s['label'] ?? '') ?>"></div>
                        <div class="form-group" style="flex:2.5"><label>URL</label><input type="url" name="social[<?= $i ?>][url]" class="form-input" value="<?= htmlspecialchars($s['url'] ?? '') ?>"></div>
                        <div class="form-group" style="flex:1.5"><label>Icon Class</label><input type="text" name="social[<?= $i ?>][icon]" class="form-input" value="<?= htmlspecialchars($s['icon'] ?? '') ?>"></div>
                        <div class="form-group" style="flex:0;padding-top:22px"><button type="button" class="btn btn-ghost" style="padding:6px 10px;color:#f44" onclick="this.closest('[data-social-row]').remove()">✕</button></div>
                    </div>
                <?php endforeach; ?>
                </div>
                <input type="hidden" name="social_links" id="social_links_json">
                <button type="button" class="btn btn-ghost" onclick="addSocialRow()" style="margin-bottom:16px">+ Add Social Link</button>
                <br>
                <button type="submit" class="btn btn-primary" onclick="serializeSocialLinks()">Save Site Info</button>
            </form>
            <script>
            function addSocialRow() {
                var list = document.getElementById('social-links-list');
                var idx = list.querySelectorAll('[data-social-row]').length;
                var div = document.createElement('div');
                div.className = 'form-row';
                div.style.marginBottom = '6px';
                div.setAttribute('data-social-row', idx);
                div.innerHTML = '<div class="form-group" style="flex:1.5"><label>Label</label><input type="text" name="social[' + idx + '][label]" class="form-input" placeholder="LinkedIn"></div>' +
                    '<div class="form-group" style="flex:2.5"><label>URL</label><input type="url" name="social[' + idx + '][url]" class="form-input" placeholder="https://linkedin.com/in/"></div>' +
                    '<div class="form-group" style="flex:1.5"><label>Icon Class</label><input type="text" name="social[' + idx + '][icon]" class="form-input" placeholder="fab fa-linkedin"></div>' +
                    '<div class="form-group" style="flex:0;padding-top:22px"><button type="button" class="btn btn-ghost" style="padding:6px 10px;color:#f44" onclick="this.closest(\'[data-social-row]\').remove()">✕</button></div>';
                list.appendChild(div);
            }
            function serializeSocialLinks() {
                var rows = document.querySelectorAll('[data-social-row]');
                var arr = [];
                rows.forEach(function(r) {
                    var label = r.querySelector('input[name$="[label]"]').value;
                    var url = r.querySelector('input[name$="[url]"]').value;
                    var icon = r.querySelector('input[name$="[icon]"]').value;
                    if (label || url) arr.push({label: label, url: url, icon: icon});
                });
                document.getElementById('social_links_json').value = JSON.stringify(arr);
            }
            </script>
        </div>
        <div class="card"><div class="card-header"><h2>Diagnostics</h2></div>
            <div class="btn-group">
                <form method="POST" style="display:inline"><input type="hidden" name="action" value="seed_gallery"><button class="btn btn-ghost">Seed Gallery</button></form>
                <form method="POST" style="display:inline"><input type="hidden" name="action" value="seed_portfolio"><button class="btn btn-ghost">Seed Portfolio</button></form>
                <form method="POST" style="display:inline"><input type="hidden" name="action" value="seed_pages"><button class="btn btn-ghost" onclick="return confirm('Seed all page content? This will overwrite existing page data.')">Seed Pages</button></form>
                <form method="POST" style="display:inline"><input type="hidden" name="action" value="test_email"><button class="btn btn-ghost">Test Email</button></form>
            </div>
        </div>
        <div class="card"><div class="card-header"><h2>Configuration</h2></div>
            <table>
                <tr><td style="color:#555;width:140px">Environment</td><td><?= $isLocal ? 'Local (XAMPP)' : 'Production' ?></td></tr>
                <tr><td style="color:#555">Database</td><td><?= DB_NAME ?> @ <?= DB_HOST ?></td></tr>
                <tr><td style="color:#555">SMTP</td><td><?= SMTP_HOST ?>:<?= SMTP_PORT ?></td></tr>
                <tr><td style="color:#555">Admin Email</td><td><?= ADMIN_EMAIL ?></td></tr>
                <tr><td style="color:#555">PHP</td><td><?= phpversion() ?></td></tr>
            </table>
        </div>
        <div class="card"><div class="card-header"><h2>Database Tables</h2></div>
            <?php
            $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo '<div style="display:flex;gap:6px;flex-wrap:wrap">';
            foreach ($tables as $t) {
                $count = $db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
                echo '<span style="background:#1a1a1a;border:1px solid #333;padding:5px 12px;border-radius:5px;font-size:12px;color:#bbb">'.$t.' <strong style="color:#4ade80">'.$count.'</strong></span>';
            }
            echo '</div>';
            ?>
        </div>
<?php elseif (in_array($tab, ['page-home','page-about','page-service','page-contact'])): ?>
<?php
    $pageSlug = str_replace('page-', '', $tab);
    $pageRow = $db->prepare("SELECT content FROM pages WHERE page_slug = ?");
    $pageRow->execute([$pageSlug]);
    $pageData = $pageRow->fetch();
    $c = $pageData ? json_decode($pageData['content'], true) : [];
    $pageLabels = ['home'=>'Home Page','about'=>'About Page','service'=>'Services Page','contact'=>'Contact Page'];
?>
        <div class="page-header"><div><h1><?= $pageLabels[$pageSlug] ?> Content</h1><p>Edit the content shown on the public <?= $pageSlug ?> page</p></div></div>
        <form method="POST" id="pageContentForm">
            <input type="hidden" name="action" value="save_page_content">
            <input type="hidden" name="page_slug" value="<?= $pageSlug ?>">

            <div class="card"><div class="card-header"><h2>SEO / Meta Tags</h2><p style="color:#666;margin-top:4px;font-size:13px">Controls how this page appears in Google search and social media previews</p></div>
                <?php $meta = $c['meta'] ?? []; ?>
                <div class="form-group"><label>Page Title (shown in browser tab & Google)</label><input type="text" name="meta_title" class="form-input" value="<?= htmlspecialchars($meta['title']??'') ?>" placeholder="Page Name \u2014 Deep Design Hubs"></div>
                <div class="form-group"><label>Meta Description (shown in Google results, 150-160 chars ideal)</label><textarea name="meta_description" class="form-input" rows="2" placeholder="Describe this page for search engines..."><?= htmlspecialchars($meta['description']??'') ?></textarea></div>
                <div class="form-group"><label>Keywords (comma separated)</label><input type="text" name="meta_keywords" class="form-input" value="<?= htmlspecialchars($meta['keywords']??'') ?>" placeholder="keyword1, keyword2, keyword3"></div>
                <div class="form-row">
                    <div class="form-group"><label>OG Title (social media share title)</label><input type="text" name="meta_og_title" class="form-input" value="<?= htmlspecialchars($meta['og_title']??'') ?>"></div>
                    <div class="form-group"><label>OG Description (social media share text)</label><textarea name="meta_og_desc" class="form-input" rows="2"><?= htmlspecialchars($meta['og_desc']??'') ?></textarea></div>
                </div>
            </div>

<?php if ($pageSlug === 'home'): ?>
            <div class="card"><div class="card-header"><h2>Hero Section</h2></div>
                <div class="form-group"><label>Hero Title (HTML allowed)</label><textarea name="hero_title" class="form-input" rows="2"><?= htmlspecialchars($c['hero_title']??'') ?></textarea></div>
                <div class="form-group"><label>Hero Subtitle</label><textarea name="hero_subtitle" class="form-input" rows="2"><?= htmlspecialchars($c['hero_subtitle']??'') ?></textarea></div>
                <div class="form-row"><div class="form-group"><label>CTA Button Text</label><input type="text" name="hero_cta" class="form-input" value="<?= htmlspecialchars($c['hero_cta']??'') ?>"></div><div class="form-group"><label>CTA Link</label><input type="text" name="hero_cta_link" class="form-input" value="<?= htmlspecialchars($c['hero_cta_link']??'') ?>"></div></div>
            </div>
            <div class="card"><div class="card-header"><h2>Services Preview</h2></div>
                <div class="form-row"><div class="form-group"><label>Section Label</label><input type="text" name="services_label" class="form-input" value="<?= htmlspecialchars($c['services_label']??'') ?>"></div><div class="form-group"><label>Section Heading (HTML)</label><input type="text" name="services_heading" class="form-input" value="<?= htmlspecialchars($c['services_heading']??'') ?>"></div></div>
                <?php $services = $c['services'] ?? []; for ($i=0; $i<4; $i++): $s = $services[$i] ?? ['icon'=>'','title'=>'','desc'=>'']; ?>
                <div style="background:#0a0a0a;border:1px solid #222;border-radius:8px;padding:14px;margin-bottom:10px">
                    <div class="form-row-3">
                        <div class="form-group"><label>Icon (Material name)</label><input type="text" name="svc_<?=$i?>_icon" class="form-input" value="<?= htmlspecialchars($s['icon']) ?>"></div>
                        <div class="form-group"><label>Title</label><input type="text" name="svc_<?=$i?>_title" class="form-input" value="<?= htmlspecialchars($s['title']) ?>"></div>
                        <div class="form-group"><label>Description</label><input type="text" name="svc_<?=$i?>_desc" class="form-input" value="<?= htmlspecialchars($s['desc']) ?>"></div>
                    </div>
                </div>
                <?php endfor; ?>
                <div class="form-row"><div class="form-group"><label>Button Text</label><input type="text" name="services_btn" class="form-input" value="<?= htmlspecialchars($c['services_btn']??'') ?>"></div><div class="form-group"><label>Button Link</label><input type="text" name="services_btn_link" class="form-input" value="<?= htmlspecialchars($c['services_btn_link']??'') ?>"></div></div>
            </div>
            <div class="card"><div class="card-header"><h2>About Preview</h2></div>
                <div class="form-row"><div class="form-group"><label>Section Label</label><input type="text" name="about_label" class="form-input" value="<?= htmlspecialchars($c['about_label']??'') ?>"></div><div class="form-group"><label>Heading (HTML)</label><input type="text" name="about_heading" class="form-input" value="<?= htmlspecialchars($c['about_heading']??'') ?>"></div></div>
                <div class="form-group"><label>Text</label><textarea name="about_text" class="form-input" rows="3"><?= htmlspecialchars($c['about_text']??'') ?></textarea></div>
                <div class="form-group"><label>Button Text</label><input type="text" name="about_btn" class="form-input" value="<?= htmlspecialchars($c['about_btn']??'') ?>"></div>
                <?php $stats = $c['about_stats'] ?? []; for ($i=0; $i<3; $i++): $st = $stats[$i] ?? ['number'=>'','label'=>'']; ?>
                <div class="form-row-3" style="margin-bottom:8px">
                    <div class="form-group"><label>Stat <?= $i+1 ?> Number</label><input type="text" name="about_stat_<?=$i?>_number" class="form-input" value="<?= htmlspecialchars($st['number']) ?>"></div>
                    <div class="form-group"><label>Stat <?= $i+1 ?> Label</label><input type="text" name="about_stat_<?=$i?>_label" class="form-input" value="<?= htmlspecialchars($st['label']) ?>"></div>
                </div>
                <?php endfor; ?>
            </div>
            <div class="card"><div class="card-header"><h2>CTA Section</h2></div>
                <div class="form-group"><label>Heading</label><input type="text" name="cta_heading" class="form-input" value="<?= htmlspecialchars($c['cta_heading']??'') ?>"></div>
                <div class="form-group"><label>Text</label><textarea name="cta_text" class="form-input" rows="2"><?= htmlspecialchars($c['cta_text']??'') ?></textarea></div>
                <div class="form-row"><div class="form-group"><label>Button 1 Text</label><input type="text" name="cta_btn1" class="form-input" value="<?= htmlspecialchars($c['cta_btn1']??'') ?>"></div><div class="form-group"><label>Button 1 Link</label><input type="text" name="cta_btn1_link" class="form-input" value="<?= htmlspecialchars($c['cta_btn1_link']??'') ?>"></div></div>
                <div class="form-row"><div class="form-group"><label>Button 2 Text</label><input type="text" name="cta_btn2" class="form-input" value="<?= htmlspecialchars($c['cta_btn2']??'') ?>"></div><div class="form-group"><label>Button 2 Link</label><input type="text" name="cta_btn2_link" class="form-input" value="<?= htmlspecialchars($c['cta_btn2_link']??'') ?>"></div></div>
            </div>

<?php elseif ($pageSlug === 'about'): ?>
            <div class="card"><div class="card-header"><h2>Story Section</h2></div>
                <div class="form-row"><div class="form-group"><label>Label</label><input type="text" name="story_label" class="form-input" value="<?= htmlspecialchars($c['story_label']??'') ?>"></div><div class="form-group"><label>Heading (HTML)</label><input type="text" name="story_heading" class="form-input" value="<?= htmlspecialchars($c['story_heading']??'') ?>"></div></div>
                <div class="form-group"><label>Paragraph 1</label><textarea name="story_p1" class="form-input" rows="3"><?= htmlspecialchars($c['story_p1']??'') ?></textarea></div>
                <div class="form-group"><label>Paragraph 2</label><textarea name="story_p2" class="form-input" rows="3"><?= htmlspecialchars($c['story_p2']??'') ?></textarea></div>
                <div class="form-group"><label>Image Path</label><input type="text" name="story_image" class="form-input" value="<?= htmlspecialchars($c['story_image']??'') ?>"></div>
                <?php $stats = $c['story_stats'] ?? []; for ($i=0; $i<3; $i++): $st = $stats[$i] ?? ['number'=>'','label'=>'']; ?>
                <div class="form-row-3" style="margin-bottom:8px">
                    <div class="form-group"><label>Stat <?= $i+1 ?> Number</label><input type="text" name="story_stat_<?=$i?>_number" class="form-input" value="<?= htmlspecialchars($st['number']) ?>"></div>
                    <div class="form-group"><label>Stat <?= $i+1 ?> Label</label><input type="text" name="story_stat_<?=$i?>_label" class="form-input" value="<?= htmlspecialchars($st['label']) ?>"></div>
                </div>
                <?php endfor; ?>
            </div>
            <div class="card"><div class="card-header"><h2>Skills Section</h2></div>
                <div class="form-row"><div class="form-group"><label>Label</label><input type="text" name="skills_label" class="form-input" value="<?= htmlspecialchars($c['skills_label']??'') ?>"></div><div class="form-group"><label>Heading (HTML)</label><input type="text" name="skills_heading" class="form-input" value="<?= htmlspecialchars($c['skills_heading']??'') ?>"></div></div>
                <?php $skills = $c['skills'] ?? []; for ($i=0; $i<4; $i++): $sk = $skills[$i] ?? ['icon'=>'','title'=>'','desc'=>'','tags'=>[]]; ?>
                <div style="background:#0a0a0a;border:1px solid #222;border-radius:8px;padding:14px;margin-bottom:10px">
                    <div class="form-row"><div class="form-group"><label>Icon</label><input type="text" name="skill_<?=$i?>_icon" class="form-input" value="<?= htmlspecialchars($sk['icon']) ?>"></div><div class="form-group"><label>Title</label><input type="text" name="skill_<?=$i?>_title" class="form-input" value="<?= htmlspecialchars($sk['title']) ?>"></div></div>
                    <div class="form-group"><label>Description</label><textarea name="skill_<?=$i?>_desc" class="form-input" rows="2"><?= htmlspecialchars($sk['desc']) ?></textarea></div>
                    <div class="form-group"><label>Tags (comma separated)</label><input type="text" name="skill_<?=$i?>_tags" class="form-input" value="<?= htmlspecialchars(implode(', ', $sk['tags'] ?? [])) ?>"></div>
                </div>
                <?php endfor; ?>
            </div>
            <div class="card"><div class="card-header"><h2>Philosophy / Quote</h2></div>
                <div class="form-group"><label>Quote</label><textarea name="philosophy_quote" class="form-input" rows="3"><?= htmlspecialchars($c['philosophy_quote']??'') ?></textarea></div>
                <div class="form-row"><div class="form-group"><label>Author Name</label><input type="text" name="philosophy_author" class="form-input" value="<?= htmlspecialchars($c['philosophy_author']??'') ?>"></div><div class="form-group"><label>Author Role</label><input type="text" name="philosophy_role" class="form-input" value="<?= htmlspecialchars($c['philosophy_role']??'') ?>"></div></div>
            </div>
            <div class="card"><div class="card-header"><h2>CTA Section</h2></div>
                <div class="form-group"><label>Heading</label><input type="text" name="cta_heading" class="form-input" value="<?= htmlspecialchars($c['cta_heading']??'') ?>"></div>
                <div class="form-group"><label>Text</label><textarea name="cta_text" class="form-input" rows="2"><?= htmlspecialchars($c['cta_text']??'') ?></textarea></div>
                <div class="form-row"><div class="form-group"><label>Button 1 Text</label><input type="text" name="cta_btn1" class="form-input" value="<?= htmlspecialchars($c['cta_btn1']??'') ?>"></div><div class="form-group"><label>Button 2 Text</label><input type="text" name="cta_btn2" class="form-input" value="<?= htmlspecialchars($c['cta_btn2']??'') ?>"></div></div>
            </div>

<?php elseif ($pageSlug === 'service'): ?>
            <div class="card"><div class="card-header"><h2>Hero Section</h2></div>
                <div class="form-group"><label>Label</label><input type="text" name="hero_label" class="form-input" value="<?= htmlspecialchars($c['hero_label']??'') ?>"></div>
                <div class="form-group"><label>Heading (HTML)</label><input type="text" name="hero_heading" class="form-input" value="<?= htmlspecialchars($c['hero_heading']??'') ?>"></div>
                <div class="form-group"><label>Text</label><textarea name="hero_text" class="form-input" rows="2"><?= htmlspecialchars($c['hero_text']??'') ?></textarea></div>
            </div>
            <?php $svcs = $c['services'] ?? []; $svcCount = max(count($svcs), 5); for ($si=0; $si<$svcCount; $si++): $sv = $svcs[$si] ?? ['icon'=>'','name'=>'','desc'=>'','items'=>[],'tools'=>[],'delivery'=>'']; ?>
            <div class="card"><div class="card-header"><h2>Service <?= $si+1 ?><?= !empty($sv['name']) ? ': '.htmlspecialchars($sv['name']) : '' ?></h2></div>
                <div class="form-row"><div class="form-group"><label>Icon</label><input type="text" name="svc_<?=$si?>_icon" class="form-input" value="<?= htmlspecialchars($sv['icon']??'') ?>"></div><div class="form-group"><label>Name</label><input type="text" name="svc_<?=$si?>_name" class="form-input" value="<?= htmlspecialchars($sv['name']??'') ?>"></div></div>
                <div class="form-group"><label>Description</label><input type="text" name="svc_<?=$si?>_desc" class="form-input" value="<?= htmlspecialchars($sv['desc']??'') ?>"></div>
                <div class="form-group"><label>What's Included (one per line)</label><textarea name="svc_<?=$si?>_items" class="form-input" rows="6"><?= htmlspecialchars(implode("\n", $sv['items']??[])) ?></textarea></div>
                <div class="form-group"><label>Tools (comma separated)</label><input type="text" name="svc_<?=$si?>_tools" class="form-input" value="<?= htmlspecialchars(implode(', ', $sv['tools']??[])) ?>"></div>
                <div class="form-group"><label>Typical Delivery</label><input type="text" name="svc_<?=$si?>_delivery" class="form-input" value="<?= htmlspecialchars($sv['delivery']??'') ?>"></div>
            </div>
            <?php endfor; ?>
            <div class="card"><div class="card-header"><h2>Process Steps</h2></div>
                <?php $steps = $c['process_steps'] ?? []; for ($i=0; $i<4; $i++): $st = $steps[$i] ?? ['number'=>'0'.($i+1),'title'=>'','desc'=>'']; ?>
                <div style="background:#0a0a0a;border:1px solid #222;border-radius:8px;padding:14px;margin-bottom:10px">
                    <div class="form-row-3">
                        <div class="form-group"><label>Number</label><input type="text" name="step_<?=$i?>_number" class="form-input" value="<?= htmlspecialchars($st['number']) ?>"></div>
                        <div class="form-group"><label>Title</label><input type="text" name="step_<?=$i?>_title" class="form-input" value="<?= htmlspecialchars($st['title']) ?>"></div>
                        <div class="form-group"><label>Description</label><input type="text" name="step_<?=$i?>_desc" class="form-input" value="<?= htmlspecialchars($st['desc']) ?>"></div>
                    </div>
                </div>
                <?php endfor; ?>
                <div class="form-row"><div class="form-group"><label>Process Label</label><input type="text" name="process_label" class="form-input" value="<?= htmlspecialchars($c['process_label']??'') ?>"></div><div class="form-group"><label>Process Heading (HTML)</label><input type="text" name="process_heading" class="form-input" value="<?= htmlspecialchars($c['process_heading']??'') ?>"></div></div>
            </div>
            <div class="card"><div class="card-header"><h2>Pricing Section</h2></div>
                <div class="form-group"><label>Heading</label><input type="text" name="pricing_heading" class="form-input" value="<?= htmlspecialchars($c['pricing_heading']??'') ?>"></div>
                <div class="form-group"><label>Text</label><textarea name="pricing_text" class="form-input" rows="3"><?= htmlspecialchars($c['pricing_text']??'') ?></textarea></div>
                <div class="form-group"><label>Button Text</label><input type="text" name="pricing_btn" class="form-input" value="<?= htmlspecialchars($c['pricing_btn']??'') ?>"></div>
            </div>

<?php elseif ($pageSlug === 'contact'): ?>
            <div class="card"><div class="card-header"><h2>Hero Section</h2></div>
                <div class="form-group"><label>Label</label><input type="text" name="hero_label" class="form-input" value="<?= htmlspecialchars($c['hero_label']??'') ?>"></div>
                <div class="form-group"><label>Heading (HTML)</label><input type="text" name="hero_heading" class="form-input" value="<?= htmlspecialchars($c['hero_heading']??'') ?>"></div>
                <div class="form-group"><label>Text</label><textarea name="hero_text" class="form-input" rows="2"><?= htmlspecialchars($c['hero_text']??'') ?></textarea></div>
            </div>
            <div class="card"><div class="card-header"><h2>Info Cards</h2></div>
                <?php $cards = $c['info_cards'] ?? []; for ($i=0; $i<4; $i++): $cd = $cards[$i] ?? ['icon'=>'','title'=>'','value'=>'']; ?>
                <div style="background:#0a0a0a;border:1px solid #222;border-radius:8px;padding:14px;margin-bottom:10px">
                    <div class="form-row-3">
                        <div class="form-group"><label>Icon</label><input type="text" name="info_<?=$i?>_icon" class="form-input" value="<?= htmlspecialchars($cd['icon']) ?>"></div>
                        <div class="form-group"><label>Title</label><input type="text" name="info_<?=$i?>_title" class="form-input" value="<?= htmlspecialchars($cd['title']) ?>"></div>
                        <div class="form-group"><label>Value</label><input type="text" name="info_<?=$i?>_value" class="form-input" value="<?= htmlspecialchars($cd['value']) ?>"></div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
            <div class="card"><div class="card-header"><h2>Newsletter & Social</h2></div>
                <div class="form-row"><div class="form-group"><label>Newsletter Title</label><input type="text" name="newsletter_title" class="form-input" value="<?= htmlspecialchars($c['newsletter_title']??'') ?>"></div><div class="form-group"><label>Newsletter Text</label><input type="text" name="newsletter_text" class="form-input" value="<?= htmlspecialchars($c['newsletter_text']??'') ?>"></div></div>
                <div class="form-row"><div class="form-group"><label>Social Label</label><input type="text" name="social_label" class="form-input" value="<?= htmlspecialchars($c['social_label']??'') ?>"></div><div class="form-group"><label>Social Heading (HTML)</label><input type="text" name="social_heading" class="form-input" value="<?= htmlspecialchars($c['social_heading']??'') ?>"></div></div>
                <div class="form-group"><label>Social Text</label><textarea name="social_text" class="form-input" rows="2"><?= htmlspecialchars($c['social_text']??'') ?></textarea></div>
            </div>
<?php endif; ?>

            <div style="padding:10px 0 30px"><button type="submit" class="btn btn-primary" style="padding:10px 24px;font-size:13px">Save <?= $pageLabels[$pageSlug] ?> Content</button></div>
        </form>

<?php endif; ?>
    </main>
</div>

<script>
/* ===== MODAL CONTROLS ===== */
document.querySelectorAll('.modal-overlay').forEach(function(m){m.addEventListener('click',function(e){if(e.target===m)m.classList.remove('active')})});

function editGallery(img){
    document.getElementById('egId').value=img.id;
    document.getElementById('egSrc').value=img.src||'';
    document.getElementById('egTitle').value=img.title||'';
    document.getElementById('egCategory').value=img.category||'';
    document.getElementById('egDesc').value=img.description||'';
    document.getElementById('egSort').value=img.sort_order||0;
    document.getElementById('egVisible').checked=!!img.is_visible;
    var pv=document.getElementById('egImagePreview');
    if(img.src){pv.style.display='block';pv.querySelector('img').src=img.src;}else{pv.style.display='none';}
    document.getElementById('editGalleryModal').classList.add('active');
}

/* ===== PORTFOLIO MODAL ===== */
function autoSlug(){
    var t=document.getElementById('pfTitle').value;
    var s=t.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
    document.getElementById('pfSlug').value=s;
}
function openPortfolioModal(data){
    var m=document.getElementById('portfolioModal');
    var f=document.getElementById('portfolioForm');
    f.reset();
    document.getElementById('pfVisibleGroup').style.display='none';
    document.getElementById('portfolioModalTitle').textContent='Add Project';
    document.getElementById('portfolioSubmitBtn').textContent='Add Project';
    document.getElementById('portfolioAction').value='add_portfolio';
    document.getElementById('pfId').value='';
    document.getElementById('pfImagePreview').style.display='none';
    document.getElementById('downloadFields').style.display='none';
    document.getElementById('pfDownloadPreview').textContent='';
    m.classList.add('active');
}
function editPortfolio(p){
    openPortfolioModal();
    document.getElementById('portfolioModalTitle').textContent='Edit Project';
    document.getElementById('portfolioSubmitBtn').textContent='Save Changes';
    document.getElementById('portfolioAction').value='update_portfolio';
    document.getElementById('pfId').value=p.id;
    document.getElementById('pfTitle').value=p.title||'';
    document.getElementById('pfSlug').value=p.slug||'';
    document.getElementById('pfDesc').value=p.description||'';
    document.getElementById('pfLongDesc').value=p.long_description||'';
    document.getElementById('pfImage').value=p.image||'';
    document.getElementById('pfCategory').value=p.category||'web';
    document.getElementById('pfSort').value=p.sort_order||0;
    document.getElementById('pfTags').value=(p.tags||'').replace(/,/g,', ');
    document.getElementById('pfUrl').value=p.project_url||'';
    document.getElementById('pfVisibleGroup').style.display='block';
    document.getElementById('pfVisible').checked=!!p.is_visible;
    document.getElementById('pfDownloadable').checked=!!p.is_downloadable;
    document.getElementById('pfPrice').value=p.price||'0';
    document.getElementById('pfDownloadFile').value=p.download_file||'';
    toggleDownloadFields();
    if(p.download_file){document.getElementById('pfDownloadPreview').textContent='Current: '+p.download_file;}
    if(p.image){var pv=document.getElementById('pfImagePreview');pv.style.display='block';pv.querySelector('img').src=p.image;}
}
function closePortfolioModal(){document.getElementById('portfolioModal').classList.remove('active')}
function toggleDownloadFields(){
    var c=document.getElementById('pfDownloadable').checked;
    document.getElementById('downloadFields').style.display=c?'block':'none';
}

/* ===== UPLOAD PORTFOLIO IMAGE ===== */
function uploadProjImage(input){
    if(!input.files[0])return;
    var fd=new FormData();
    fd.append('image',input.files[0]);
    fd.append('action','upload_portfolio_image');
    fetch('index.php',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){
        if(d.success){
            document.getElementById('pfImage').value=d.url;
            var pv=document.getElementById('pfImagePreview');pv.style.display='block';pv.querySelector('img').src=d.url;
        } else {alert(d.message||'Upload failed');}
    }).catch(function(){alert('Upload failed');});
}

/* ===== UPLOAD DOWNLOAD FILE ===== */
function uploadDownloadFile(input){
    if(!input.files[0])return;
    var fd=new FormData();
    fd.append('file',input.files[0]);
    fd.append('action','upload_download_file');
    fetch('index.php',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){
        if(d.success){
            document.getElementById('pfDownloadFile').value=d.url;
            document.getElementById('pfDownloadPreview').textContent='Uploaded: '+d.name+' ('+d.url+')';
        } else {alert(d.message||'Upload failed');}
    }).catch(function(){alert('Upload failed');});
}

/* ===== UPLOAD HERO IMAGE ===== */
function uploadHeroImage(input, fieldId){
    if(!input.files[0])return;
    var fd=new FormData();
    fd.append('image',input.files[0]);
    fd.append('action','upload_hero_image');
    fetch('index.php',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){
        if(d.success){
            document.getElementById(fieldId).value=d.url;
            var pv=document.getElementById(fieldId+'Preview');
            if(pv){pv.style.display='block';pv.querySelector('img').src=d.url;}
        } else {alert(d.message||'Upload failed');}
    }).catch(function(){alert('Upload failed');});
}

/* ===== UPLOAD GENERIC IMAGE (for about story, etc.) ===== */
function uploadGenericImage(input, fieldId){
    if(!input.files[0])return;
    var fd=new FormData();
    fd.append('image',input.files[0]);
    fd.append('action','upload_hero_image');
    fetch('index.php',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){
        if(d.success){
            document.getElementById(fieldId).value=d.url;
        } else {alert(d.message||'Upload failed');}
    }).catch(function(){alert('Upload failed');});
}

/* ===== UPLOAD GALLERY EDIT IMAGE ===== */
function uploadGalleryEditImage(input){
    if(!input.files[0])return;
    var fd=new FormData();
    fd.append('image',input.files[0]);
    fd.append('action','upload_gallery_image');
    fetch('index.php',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){
        if(d.success){
            document.getElementById('egSrc').value=d.url;
            var pv=document.getElementById('egImagePreview');pv.style.display='block';pv.querySelector('img').src=d.url;
        } else {alert(d.message||'Upload failed');}
    }).catch(function(){alert('Upload failed');});
}

/* ===== SEARCH ===== */
var searchTimer;
function handleSearch(q){
    clearTimeout(searchTimer);
    searchTimer=setTimeout(function(){
        q=q.toLowerCase().trim();
        // Search portfolio items
        document.querySelectorAll('.portfolio-item').forEach(function(el){
            var text=el.textContent.toLowerCase();
            el.style.display=(!q||text.indexOf(q)!==-1)?'':'none';
        });
        // Search gallery items
        document.querySelectorAll('.gallery-item').forEach(function(el){
            var text=el.textContent.toLowerCase();
            el.style.display=(!q||text.indexOf(q)!==-1)?'':'none';
        });
        // Search contact messages
        document.querySelectorAll('.msg-view').forEach(function(el){
            var text=el.textContent.toLowerCase();
            el.style.display=(!q||text.indexOf(q)!==-1)?'':'none';
        });
        // Search table rows (subscribers, dashboard recent contacts)
        document.querySelectorAll('table tbody tr').forEach(function(el){
            var text=el.textContent.toLowerCase();
            el.style.display=(!q||text.indexOf(q)!==-1)?'':'none';
        });
    },200);
}
</script>
</body>
</html>
