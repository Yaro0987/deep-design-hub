<?php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    jsonResponse(false, 'Invalid request body', 400);
}

$name    = trim($input['name'] ?? '');
$email   = trim($input['email'] ?? '');
$subject = trim($input['service'] ?? $input['subject'] ?? '');
$message = trim($input['message'] ?? '');
$budget  = trim($input['budget'] ?? '');

if (empty($name) || empty($email) || empty($message)) {
    jsonResponse(false, 'Name, email, and message are required', 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Invalid email address', 400);
}

$type = !empty($budget) ? 'request' : 'contact';

try {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO contacts (name, email, subject, message, budget, type) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $email, $subject, $message, $budget, $type]);
} catch (Exception $e) {
    jsonResponse(false, 'Failed to save message', 500);
}

$typeLabel = $type === 'request' ? 'New Project Request' : 'New Contact Message';
$siteUrl = 'https://deep-design.netlify.app';
$logoUrl = $siteUrl . '/assets/imgs/logo/white-deep.png';

$html = '<html><body style="font-family:\'Segoe UI\',Arial,sans-serif;color:#1a1a1a;max-width:600px;margin:0 auto;padding:20px;">
<div style="background:#000;padding:30px;border-radius:16px 16px 0 0;text-align:center;">
    <img src="' . $logoUrl . '" alt="Deep Design Hubs" style="height:40px;margin-bottom:8px;">
    <h1 style="margin:0;font-size:20px;color:#fff;font-weight:600;">' . $typeLabel . '</h1>
    <p style="color:#888;font-size:12px;margin:6px 0 0;text-transform:uppercase;letter-spacing:1px;">' . date('F j, Y \a\t g:i A') . '</p>
</div>
<div style="border:1px solid #e5e5e5;border-top:none;padding:32px;border-radius:0 0 16px 16px;">
    <table style="width:100%;border-collapse:collapse;">
        <tr><td style="padding:12px 0;color:#999;font-weight:600;width:130px;font-size:13px;border-bottom:1px solid #f0f0f0;">Name</td><td style="padding:12px 0;font-size:14px;border-bottom:1px solid #f0f0f0;">' . htmlspecialchars($name) . '</td></tr>
        <tr><td style="padding:12px 0;color:#999;font-weight:600;font-size:13px;border-bottom:1px solid #f0f0f0;">Email</td><td style="padding:12px 0;font-size:14px;border-bottom:1px solid #f0f0f0;"><a href="mailto:' . htmlspecialchars($email) . '" style="color:#000;text-decoration:underline;">' . htmlspecialchars($email) . '</a></td></tr>
        <tr><td style="padding:12px 0;color:#999;font-weight:600;font-size:13px;border-bottom:1px solid #f0f0f0;">Service</td><td style="padding:12px 0;font-size:14px;border-bottom:1px solid #f0f0f0;">' . htmlspecialchars($subject ?: '—') . '</td></tr>'
        . ($budget ? '<tr><td style="padding:12px 0;color:#999;font-weight:600;font-size:13px;border-bottom:1px solid #f0f0f0;">Budget</td><td style="padding:12px 0;font-size:14px;border-bottom:1px solid #f0f0f0;">' . htmlspecialchars($budget) . '</td></tr>' : '')
        . '<tr><td style="padding:12px 0;color:#999;font-weight:600;font-size:13px;">Type</td><td style="padding:12px 0;font-size:14px;"><span style="background:#000;color:#fff;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;">' . ucfirst($type) . '</span></td></tr>
    </table>
    <div style="margin:24px 0;padding:20px;background:#f8f8f8;border-left:3px solid #000;border-radius:0 10px 10px 0;">
        <p style="margin:0 0 8px;color:#999;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:1px;">Message</p>
        <p style="margin:0;white-space:pre-wrap;line-height:1.7;font-size:14px;">' . htmlspecialchars($message) . '</p>
    </div>
    <div style="text-align:center;margin-top:28px;">
        <a href="mailto:' . htmlspecialchars($email) . '?subject=Re: Your inquiry at Deep Design Hubs" style="display:inline-block;background:#000;color:#fff;padding:14px 32px;border-radius:50px;text-decoration:none;font-weight:600;font-size:14px;">Reply to ' . htmlspecialchars($name) . '</a>
    </div>
    <p style="text-align:center;margin-top:16px;font-size:12px;"><a href="' . $siteUrl . '/portfolio" style="color:#666;text-decoration:underline;">View Portfolio</a></p>
</div>
<p style="text-align:center;color:#bbb;font-size:11px;margin-top:20px;">Deep Design Hubs &mdash; Website Notification</p>
</body></html>';

notifyAdmin("$typeLabel from $name", $html);

// Send confirmation email to the user
$userHtml = '<html><body style="font-family:\'Segoe UI\',Arial,sans-serif;color:#1a1a1a;max-width:600px;margin:0 auto;padding:20px;">
<div style="background:#000;padding:30px;border-radius:16px 16px 0 0;text-align:center;">
    <img src="' . $logoUrl . '" alt="Deep Design Hubs" style="height:40px;margin-bottom:8px;">
    <h1 style="margin:0;font-size:20px;color:#fff;font-weight:600;">Message Received!</h1>
    <p style="color:#888;font-size:12px;margin:6px 0 0;text-transform:uppercase;letter-spacing:1px;">We\'ll get back to you soon</p>
</div>
<div style="border:1px solid #e5e5e5;border-top:none;padding:32px;border-radius:0 0 16px 16px;">
    <p style="font-size:15px;line-height:1.7;">Hi ' . htmlspecialchars($name) . ',</p>
    <p style="font-size:15px;line-height:1.7;">Thank you for reaching out to Deep Design Hubs! I\'ve received your ' . ($type === 'request' ? 'project request' : 'message') . ' and will get back to you within 24 hours.</p>
    <div style="margin:24px 0;padding:16px;background:#f8f8f8;border-left:3px solid #000;border-radius:0 10px 10px 0;">
        <p style="margin:0 0 6px;color:#999;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:1px;">Your Submission</p>
        <p style="margin:4px 0 0;font-size:14px;"><strong>Service:</strong> ' . htmlspecialchars($subject ?: '—') . '</p>'
        . ($budget ? '<p style="margin:4px 0 0;font-size:14px;"><strong>Budget:</strong> ' . htmlspecialchars($budget) . '</p>' : '')
        . '<p style="margin:4px 0 0;font-size:14px;"><strong>Type:</strong> ' . ucfirst($type) . '</p>
    </div>
    <p style="font-size:15px;line-height:1.7;">In the meantime, feel free to explore my work or learn more about the services I offer.</p>
    <div style="text-align:center;margin-top:28px;">
        <a href="' . $siteUrl . '/portfolio" style="display:inline-block;background:#000;color:#fff;padding:14px 32px;border-radius:50px;text-decoration:none;font-weight:600;font-size:14px;">View My Work</a>
    </div>
    <p style="font-size:15px;line-height:1.7;margin-top:28px;">Best regards,<br><strong>Deep Design Hubs</strong></p>
</div>
<p style="text-align:center;color:#bbb;font-size:11px;margin-top:20px;">deep-design.netlify.app</p>
</body></html>';

sendSMTP($email, "We received your inquiry - Deep Design Hubs", $userHtml);

jsonResponse(true, 'Message received successfully');
?>
