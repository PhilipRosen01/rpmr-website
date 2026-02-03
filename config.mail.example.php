<?php
/**
 * Copy this file to config.mail.php on your server (Hostinger) and fill in real credentials.
 * DO NOT COMMIT config.mail.php
 */

return [
  // SMTP
  'smtp_host' => 'smtp.hostinger.com',
  'smtp_username' => 'rpmr@pbrosen.com',
  'smtp_password' => 'fermic-1jitRa-fahhar',
  // Common ports: 587 (tls) or 465 (ssl)
  'smtp_port' => 587,
  // 'tls' or 'ssl'
  'smtp_secure' => 'tls',

  // Mail routing
  'mail_from' => 'webmaster@pbrosen.com',
  'mail_from_name' => 'Website Contact Form',
  'mail_to' => 'rpmr@pbrosen.com',
];
