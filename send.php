<?php
// Basic anti-empty checks
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  exit;
}

// Helper: redirect to thank-you page relative to current directory
function redirect_thank_you(): void {
  $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
  $target = ($base === '' ? '' : $base) . '/thank-you.html';
  header('Location: ' . $target, true, 303);
  exit;
}

// Load server-only mail configuration (DO NOT COMMIT THIS FILE)
$searchPaths = [
  __DIR__ . '/config.php',
];
if (!empty($_SERVER['DOCUMENT_ROOT'])) {
  $searchPaths[] = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/config.php';
}

$configPath = null;
foreach ($searchPaths as $p) {
  if (is_file($p)) {
    $configPath = $p;
    break;
  }
}

if ($configPath === null) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=UTF-8');
  echo "Mail config missing on server.\n";
  echo "Looked for:\n- " . implode("\n- ", $searchPaths) . "\n\n";
  echo "Debug:\n";
  echo "__DIR__ = " . __DIR__ . "\n";
  echo "SCRIPT_FILENAME = " . ($_SERVER['SCRIPT_FILENAME'] ?? '') . "\n";
  echo "DOCUMENT_ROOT = " . ($_SERVER['DOCUMENT_ROOT'] ?? '') . "\n";
  echo "\nIf the file exists, common causes are: wrong filename/case (config.php), not readable permissions, or it was uploaded somewhere else.\n";
  exit;
}

if (!is_readable($configPath)) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=UTF-8');
  echo "Mail config found but NOT readable: {$configPath}\n";
  echo "Fix permissions so PHP can read it (typically 644).\n";
  exit;
}

/** @var array{smtp_host:string,smtp_username:string,smtp_password:string,smtp_port:int,smtp_secure:string,mail_from:string,mail_from_name:string,mail_to:string} $MAIL_CONFIG */
$MAIL_CONFIG = require $configPath;

// Basic spam protection (honeypot)
$honeypot = trim($_POST["website"] ?? "");
if ($honeypot !== "") {
  redirect_thank_you();
}

$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$interest = trim($_POST["interest"] ?? "");
$message = trim($_POST["message"] ?? "");

if ($name === "" || $email === "" || $message === "" || $interest === "") {
  http_response_code(400);
  exit("Missing fields.");
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  exit("Invalid email.");
}

// PHPMailer (this repo uses phpmailer/*.php, not phpmailer/src/*)
require __DIR__ . "/phpmailer/Exception.php";
require __DIR__ . "/phpmailer/PHPMailer.php";
require __DIR__ . "/phpmailer/SMTP.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
  $mail->isSMTP();
  $mail->Host = $MAIL_CONFIG['smtp_host'];
  $mail->SMTPAuth = true;
  $mail->Username = $MAIL_CONFIG['smtp_username'];
  $mail->Password = $MAIL_CONFIG['smtp_password'];

  // Some hosts require explicit auth type
  if (!empty($MAIL_CONFIG['smtp_auth_type'])) {
    $mail->AuthType = $MAIL_CONFIG['smtp_auth_type'];
  }

  // smtp_secure: 'tls' or 'ssl'
  if ($MAIL_CONFIG['smtp_secure'] === 'ssl') {
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
  } else {
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  }

  $mail->Port = (int)$MAIL_CONFIG['smtp_port'];
  $mail->CharSet = 'UTF-8';

  // Email headers/content
  $mail->setFrom($MAIL_CONFIG['mail_from'], $MAIL_CONFIG['mail_from_name']);
  $mail->addAddress($MAIL_CONFIG['mail_to']);
  $mail->addReplyTo($email, $name);

  $mail->Subject = "New Contact Form Submission";

  $bodyLines = [
    "Name: {$name}",
    "Email: {$email}",
    $phone !== "" ? "Phone: {$phone}" : "Phone: (not provided)",
    "Interest: {$interest}",
    "",
    "Message:",
    $message,
  ];
  $mail->Body = implode("\n", $bodyLines);

  $mail->send();

  // Redirect to confirmation page for a visible result
  redirect_thank_you();
} catch (Exception $e) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=UTF-8');

  // Temporary diagnostics: show the actual reason sending failed.
  // After it's fixed, revert to a generic message.
  echo "Mailer Error: " . ($mail->ErrorInfo ?: $e->getMessage());

  // Also log details to the server error log (Hostinger -> Errors).
  error_log('PHPMailer ErrorInfo: ' . $mail->ErrorInfo);
  error_log('PHPMailer Exception: ' . $e->getMessage());
}