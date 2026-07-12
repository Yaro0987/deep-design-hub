<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    jsonResponse(false, 'Invalid request body', 400);
}

$subject = trim($input['subject'] ?? '');
$body    = trim($input['body'] ?? '');
$type    = trim($input['type'] ?? 'html'); // 'html' or 'text'

if (empty($subject) || empty($body)) {
    jsonResponse(false, 'Subject and body are required', 400);
}

try {
    $db = getDB();

    // Get all active subscribers
    $stmt = $db->query("SELECT email FROM subscribers WHERE is_active = 1 ORDER BY subscribed_at DESC");
    $subscribers = $stmt->fetchAll();

    if (empty($subscribers)) {
        jsonResponse(false, 'No active subscribers found');
    }

    $sent = 0;
    $failed = 0;
    $failedEmails = [];

    $plainBody = strip_tags($body);

    foreach ($subscribers as $sub) {
        $to = $sub['email'];

        $personalizedBody = str_replace('{{email}}', $to, $body);
        $personalizedPlain = str_replace('{{email}}', $to, $plainBody);

        $result = sendSMTP($to, $subject, $personalizedBody, $personalizedPlain);

        if ($result) {
            $sent++;
        } else {
            $failed++;
            $failedEmails[] = $to;
        }
    }

    // Log the newsletter
    $stmt = $db->prepare("INSERT INTO newsletter_log (subject, body, total_sent) VALUES (?, ?, ?)");
    $stmt->execute([$subject, $body, $sent]);

    // Send a copy to admin
    $adminCopyHtml = str_replace('{{email}}', ADMIN_EMAIL, $body);
    sendSMTP(ADMIN_EMAIL, $subject . ' (Admin Copy)', $adminCopyHtml);

} catch (Exception $e) {
    jsonResponse(false, 'Failed to send newsletter: ' . $e->getMessage(), 500);
}

$response = [
    'success' => true,
    'message' => "Newsletter sent. $sent delivered" . ($failed > 0 ? ", $failed failed" : ""),
    'sent' => $sent,
    'failed' => $failed,
];

if (!empty($failedEmails)) {
    $response['failed_emails'] = $failedEmails;
}

http_response_code(200);
echo json_encode($response);
?>