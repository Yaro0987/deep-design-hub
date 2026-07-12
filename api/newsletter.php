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

// Send welcome email to subscriber
$welcomeHtml = "
<html><body style='font-family:Arial,sans-serif;color:#333;max-width:600px;margin:0 auto;'>
<div style='background:#000;color:#fff;padding:24px 32px;border-radius:12px 12px 0 0;'>
    <h1 style='margin:0;font-size:22px;'>Welcome to Deep Design Hub!</h1>
</div>
<div style='border:1px solid #e5e5e5;border-top:none;padding:32px;border-radius:0 0 12px 12px;'>
    <p style='font-size:15px;line-height:1.7;'>Hey there,</p>
    <p style='font-size:15px;line-height:1.7;'>Thanks for subscribing to the Deep Design Hub newsletter! You'll be the first to know about new projects, design tips, and creative insights.</p>
    <p style='font-size:15px;line-height:1.7;'>Stay creative,</p>
    <p style='font-size:15px;font-weight:700;'>Deep Design Hub</p>
    <hr style='border:none;border-top:1px solid #e5e5e5;margin:24px 0;'>
    <p style='font-size:12px;color:#999;text-align:center;'>You received this because you subscribed at deep-design.netlify.app</p>
</div>
</body></html>";

sendSMTP($email, 'Welcome to Deep Design Hub!', $welcomeHtml);

// Notify admin of new subscriber
$adminHtml = "
<html><body style='font-family:Arial,sans-serif;color:#333;max-width:600px;margin:0 auto;'>
<div style='background:#000;color:#fff;padding:20px 28px;border-radius:12px 12px 0 0;'>
    <h1 style='margin:0;font-size:18px;'>New Newsletter Subscriber</h1>
</div>
<div style='border:1px solid #e5e5e5;border-top:none;padding:24px;border-radius:0 0 12px 12px;'>
    <p style='font-size:14px;'><strong>Email:</strong> $email</p>
    <p style='font-size:14px;'><strong>Date:</strong> " . date('F j, Y \a\t g:i A') . "</p>
</div>
</body></html>";

notifyAdmin('New Subscriber: ' . $email, $adminHtml);

jsonResponse(true, 'Subscribed successfully');
?>