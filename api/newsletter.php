<?php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    jsonResponse(false, 'Invalid request body', 400);
}

$email = trim($input['email'] ?? '');

if (empty($email)) {
    jsonResponse(false, 'Email is required', 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Invalid email address', 400);
}

try {
    $db = getDB();

    // Check if already subscribed and active
    $stmt = $db->prepare("SELECT id, is_active FROM subscribers WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch();

    if ($existing && $existing['is_active'] == 1) {
        jsonResponse(true, 'Already subscribed');
    }

    if ($existing && $existing['is_active'] == 0) {
        // Re-subscribe
        $stmt = $db->prepare("UPDATE subscribers SET is_active = 1, unsubscribed_at = NULL WHERE id = ?");
        $stmt->execute([$existing['id']]);
    } else {
        // New subscriber
        $stmt = $db->prepare("INSERT INTO subscribers (email) VALUES (?)");
        $stmt->execute([$email]);
    }
} catch (Exception $e) {
    jsonResponse(false, 'Subscription failed', 500);
}

$siteUrl = 'https://deep-design.netlify.app';
$logoUrl = $siteUrl . '/assets/imgs/logo/white-deep.png';

// Send welcome email to subscriber
$welcomeHtml = '<html><body style="font-family:\'Segoe UI\',Arial,sans-serif;color:#1a1a1a;max-width:600px;margin:0 auto;padding:20px;">
<div style="background:#000;padding:30px;border-radius:16px 16px 0 0;text-align:center;">
    <img src="' . $logoUrl . '" alt="Deep Design" style="height:40px;margin-bottom:8px;">
    <h1 style="margin:0;font-size:20px;color:#fff;font-weight:600;">Welcome to Deep Design!</h1>
    <p style="color:#888;font-size:12px;margin:6px 0 0;text-transform:uppercase;letter-spacing:1px;">You\'re now part of the community</p>
</div>
<div style="border:1px solid #e5e5e5;border-top:none;padding:32px;border-radius:0 0 16px 16px;">
    <p style="font-size:15px;line-height:1.7;">Hey there,</p>
    <p style="font-size:15px;line-height:1.7;">Thanks for subscribing to the Deep Design newsletter! You\'ll be the first to know about new projects, design tips, and creative insights.</p>
    <div style="text-align:center;margin:28px 0;">
        <a href="' . $siteUrl . '/portfolio" style="display:inline-block;background:#000;color:#fff;padding:14px 32px;border-radius:50px;text-decoration:none;font-weight:600;font-size:14px;">Check Out My Work</a>
    </div>
    <p style="font-size:15px;line-height:1.7;">Stay creative,</p>
    <p style="font-size:15px;font-weight:700;">Deep Design</p>
    <hr style="border:none;border-top:1px solid #f0f0f0;margin:24px 0;">
    <p style="font-size:11px;color:#aaa;text-align:center;">You received this because you subscribed at deep-design.netlify.app</p>
</div>
</body></html>';

sendSMTP($email, 'Welcome to Deep Design!', $welcomeHtml);

// Notify admin of new subscriber
$adminHtml = '<html><body style="font-family:\'Segoe UI\',Arial,sans-serif;color:#1a1a1a;max-width:600px;margin:0 auto;padding:20px;">
<div style="background:#000;padding:24px;border-radius:16px 16px 0 0;text-align:center;">
    <img src="' . $logoUrl . '" alt="Deep Design" style="height:32px;margin-bottom:6px;">
    <h1 style="margin:0;font-size:18px;color:#fff;font-weight:600;">New Subscriber</h1>
</div>
<div style="border:1px solid #e5e5e5;border-top:none;padding:24px;border-radius:0 0 16px 16px;">
    <table style="width:100%;border-collapse:collapse;">
        <tr><td style="padding:10px 0;color:#999;font-weight:600;font-size:13px;width:100px;">Email</td><td style="padding:10px 0;font-size:14px;">' . htmlspecialchars($email) . '</td></tr>
        <tr><td style="padding:10px 0;color:#999;font-weight:600;font-size:13px;">Date</td><td style="padding:10px 0;font-size:14px;">' . date('F j, Y \a\t g:i A') . '</td></tr>
    </table>
</div>
</body></html>';

notifyAdmin('New Subscriber: ' . $email, $adminHtml);

jsonResponse(true, 'Subscribed successfully');
?>
