<?php
// Basic anti-empty checks
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  exit;
}

// Helper: redirect to thank-you page relative to current directory
function redirect_thank_you(): void {
  // send.php and thank-you.html live in the same folder in this project
  $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
  $target = ($base === '' ? '' : $base) . '/thank-you.html';
  header('Location: ' . $target, true, 303);
  exit;
}

// Load server-only mail configuration (DO NOT COMMIT THIS FILE)
$configPath = __DIR__ . '/config.mail.php';
if (!file_exists($configPath)) {
  http_response_code(500);
  exit('Mail config missing on server.');
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
  // Keep error generic on production; check Hostinger error logs for details.
  echo "Mailer Error";
}