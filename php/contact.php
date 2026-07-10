<?php
/**
 * contact.php
 * Handles contact form submissions sent via fetch() from js/main.js.
 *
 * Flow:
 *   1. Read + decode the JSON body sent by the frontend.
 *   2. Check the honeypot field — if it's filled in, silently pretend success
 *      (this fools simple bots without tipping them off).
 *   3. Validate name / email / message server-side (never trust the client).
 *   4. Try to send an email with mail(). On most local dev setups mail()
 *      is not configured, so we also log the submission to a text file
 *      as a reliable fallback you can check.
 *   5. Return a JSON response the frontend can read.
 *
 * NOTE ON PRODUCTION EMAIL:
 *   PHP's built-in mail() function depends on the server having a working
 *   mail transport (sendmail/postfix), which many hosts don't set up well
 *   by default. For reliable delivery on a real host, consider swapping
 *   the mail() call below for PHPMailer configured with your host's SMTP
 *   credentials, or a transactional email service (e.g. SendGrid, Mailgun).
 */

header('Content-Type: application/json');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function respond($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    respond(false, 'Method not allowed.');
}

// Read and decode JSON body
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    http_response_code(400);
    respond(false, 'Invalid request format.');
}

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$message = trim($data['message'] ?? '');
$website = trim($data['website'] ?? ''); // honeypot field

// --- Honeypot check ---
// Real visitors never see or fill in this field, since it's hidden with CSS.
// If it has a value, the submission is almost certainly from a bot.
if ($website !== '') {
    // Pretend success so bots don't learn the field is being checked
    respond(true, 'Message sent — thanks for reaching out!');
}

// --- Server-side validation ---
$errors = [];

if ($name === '' || strlen($name) > 100) {
    $errors[] = 'Please provide a valid name.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please provide a valid email address.';
}

if ($message === '' || strlen($message) > 5000) {
    $errors[] = 'Please provide a message (under 5000 characters).';
}

if (!empty($errors)) {
    http_response_code(422);
    respond(false, implode(' ', $errors));
}

// --- Sanitize for safe use in email/log ---
$safeName = strip_tags($name);
$safeEmail = strip_tags($email);
$safeMessage = strip_tags($message);

$timestamp = date('Y-m-d H:i:s');

// --- Try to store the submission in the database ---
$storedInDb = false;
$pdo = get_db_connection();

if ($pdo !== null) {
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO contact_messages (name, email, message) VALUES (:name, :email, :message)'
        );
        $stmt->execute([
            ':name' => $safeName,
            ':email' => $safeEmail,
            ':message' => $safeMessage,
        ]);
        $storedInDb = true;
    } catch (PDOException $e) {
        // Table might not exist yet, or connection dropped - fall back to file log below
        error_log('Contact message DB insert failed: ' . $e->getMessage());
    }
}

// --- Always also log to a text file as a backup record ---
// (Even when the DB insert succeeds, this gives you a second copy that
// doesn't depend on database access - handy if you ever lose DB access.)
$logLine = sprintf(
    "[%s] From: %s <%s> (stored in DB: %s)\nMessage: %s\n%s\n",
    $timestamp,
    $safeName,
    $safeEmail,
    $storedInDb ? 'yes' : 'no',
    str_replace("\n", ' ', $safeMessage),
    str_repeat('-', 40)
);

$logDir = dirname(CONTACT_LOG_FILE);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
file_put_contents(CONTACT_LOG_FILE, $logLine, FILE_APPEND | LOCK_EX);

// --- Attempt to send an email notification ---
$subject = SITE_NAME . ' — New contact form message';
$body = "You received a new message from your portfolio contact form.\n\n"
      . "Name: $safeName\n"
      . "Email: $safeEmail\n\n"
      . "Message:\n$safeMessage\n";

$headers = "From: " . SITE_NAME . " <no-reply@" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ">\r\n"
         . "Reply-To: $safeEmail\r\n"
         . "Content-Type: text/plain; charset=UTF-8\r\n";

// mail() returns true/false based on whether the message was handed off
// successfully to the local mail transport — not whether it was delivered.
$mailSent = @mail(CONTACT_RECEIVER_EMAIL, $subject, $body, $headers);

// Even if mail() fails (common on localhost), the submission is already
// safely logged above, so we still tell the user it was received.
respond(true, 'Message sent — thanks for reaching out! I\'ll get back to you soon.');
