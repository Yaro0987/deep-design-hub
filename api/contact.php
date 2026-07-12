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

$html = "
<html><body style='font-family:Arial,sans-serif;color:#333;max-width:600px;margin:0 auto;'>
<div style='background:#000;color:#fff;padding:24px 32px;border-radius:12px 12px 0 0;'>
    <h1 style='margin:0;font-size:22px;'>$typeLabel</h1>
</div>
<div style='border:1px solid #e5e5e5;border-top:none;padding:32px;border-radius:0 0 12px 12px;'>
    <p style='font-size:15px;color:#666;margin-bottom:24px;'>You received a new submission from your website.</p>
    <table style='width:100%;border-collapse:collapse;'>
        <tr><td style='padding:10px 0;color:#999;font-weight:600;width:120px;'>Name</td><td style='padding:10px 0;'>$name</td></tr>
        <tr><td style='padding:10px 0;color:#999;font-weight:600;'>Email</td><td style='padding:10px 0;'><a href='mailto:$email'>$email</a></td></tr>
        <tr><td style='padding:10px 0;color:#999;font-weight:600;'>Service</td><td style='padding:10px 0;'>$subject</td></tr>" .
        ($budget ? "<tr><td style='padding:10px 0;color:#999;font-weight:600;'>Budget</td><td style='padding:10px 0;'>$budget</td></tr>" : "") .
    "</table>
    <div style='margin-top:20px;padding:16px;background:#f5f5f5;border-radius:8px;'>
        <p style='margin:0 0 6px;color:#999;font-weight:600;font-size:12px;text-transform:uppercase;'>Message</p>
        <p style='margin:0;white-space:pre-wrap;line-height:1.6;'>$message</p>
    </div>
    <div style='margin-top:24px;text-align:center;'>
        <a href='mailto:$email?subject=Re: Your inquiry at Deep Design' style='display:inline-block;background:#000;color:#fff;padding:12px 28px;border-radius:50px;text-decoration:none;font-weight:600;font-size:14px;'>Reply to $name</a>
    </div>
</div>
<p style='text-align:center;color:#aaa;font-size:12px;margin-top:20px;'>Deep Design Hub &mdash; Website Notification</p>
</body></html>";

notifyAdmin("$typeLabel from $name", $html);

// Send confirmation email to the user
$userHtml = "
<html><body style='font-family:Arial,sans-serif;color:#333;max-width:600px;margin:0 auto;'>
<div style='background:#000;color:#fff;padding:24px 32px;border-radius:12px 12px 0 0;'>
    <h1 style='margin:0;font-size:22px;'>Message Received!</h1>
</div>
<div style='border:1px solid #e5e5e5;border-top:none;padding:32px;border-radius:0 0 12px 12px;'>
    <p style='font-size:15px;line-height:1.7;'>Hi $name,</p>
    <p style='font-size:15px;line-height:1.7;'>Thank you for reaching out to Deep Design Hub! I've received your " . ($type === 'request' ? 'project request' : 'message') . " and will get back to you within 24 hours.</p>
    <div style='margin:20px 0;padding:16px;background:#f5f5f5;border-radius:8px;'>
        <p style='margin:0 0 6px;color:#999;font-weight:600;font-size:12px;text-transform:uppercase;'>Your Submission</p>
        <p style='margin:0;font-size:14px;'><strong>Service:</strong> " . htmlspecialchars($subject) . "</p>" .
        ($budget ? "<p style='margin:4px 0 0;font-size:14px;'><strong>Budget:</strong> " . htmlspecialchars($budget) . "</p>" : "") .
    "</div>
    <p style='font-size:15px;line-height:1.7;'>In the meantime, feel free to explore my work or check out the latest design tips on the blog.</p>
    <div style='margin-top:24px;text-align:center;'>
        <a href='https://deep-design.netlify.app/?page=portfolio' style='display:inline-block;background:#000;color:#fff;padding:12px 28px;border-radius:50px;text-decoration:none;font-weight:600;font-size:14px;'>View My Work</a>
    </div>
    <p style='font-size:15px;line-height:1.7;margin-top:24px;'>Best regards,<br><strong>Deep Design Hub</strong></p>
</div>
<p style='text-align:center;color:#aaa;font-size:12px;margin-top:20px;'>This is a confirmation email from deep-design.netlify.app</p>
</body></html>";

sendSMTP($email, "We received your inquiry - Deep Design Hub", $userHtml);

jsonResponse(true, 'Message received successfully');
?>