<?php
// Basic anti-empty checks
if ($_SERVER["REQUEST_METHOD"] !== "POST") { http_response_code(405); exit; }

$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$message = trim($_POST["message"] ?? "");

if ($name === "" || $email === "" || $message === "") {
  http_response_code(400);
  exit("Missing fields.");
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  exit("Invalid email.");
}

// PHPMailer (assuming you uploaded PHPMailer into /phpmailer/)
require __DIR__ . "/phpmailer/src/Exception.php";
require __DIR__ . "/phpmailer/src/PHPMailer.php";
require __DIR__ . "/phpmailer/src/SMTP.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
  // SMTP settings (Hostinger email)
  $mail->isSMTP();
  $mail->Host       = "smtp.hostinger.com";   // common for Hostinger
  $mail->SMTPAuth   = true;
  $mail->Username   = "your-email@yourdomain.com";
  $mail->Password   = "YOUR_EMAIL_PASSWORD";
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port       = 587;

  // Email headers/content
  $mail->setFrom("your-email@yourdomain.com", "Website Contact Form");
  $mail->addAddress("your-email@yourdomain.com"); // where you receive it
  $mail->addReplyTo($email, $name);

  $mail->Subject = "New Contact Form Submission";
  $mail->Body    = "Name: $name\nEmail: $email\n\nMessage:\n$message";

  $mail->send();
  echo "OK";
} catch (Exception $e) {
  http_response_code(500);
  echo "Mailer Error";
}