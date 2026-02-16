<?php
// send_anniversary_email.php

require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// ────────────────────────────────────────────────
// Load .env very early
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();  // will throw if .env is missing

// ────────────────────────────────────────────────
// Build configuration
$config = [
    'csv_url'         => $_ENV['CSV_URL']         ?? die("Missing CSV_URL in .env\n"),
    'smtp_host'       => $_ENV['SMTP_HOST']       ?? die("Missing SMTP_HOST in .env\n"),
    'smtp_username'   => $_ENV['SMTP_USERNAME']   ?? die("Missing SMTP_USERNAME in .env\n"),
    'smtp_password'   => $_ENV['SMTP_PASSWORD']   ?? die("Missing SMTP_PASSWORD in .env\n"),
    'smtp_port'       => (int) ($_ENV['SMTP_PORT'] ?? 465),
    'smtp_secure'     => strtolower($_ENV['SMTP_SECURE'] ?? 'ssl'),
    'email_from'      => $_ENV['EMAIL_FROM']      ?? die("Missing EMAIL_FROM in .env\n"),
    'email_reply_to'  => $_ENV['EMAIL_REPLY_TO']  ?? $_ENV['EMAIL_FROM'],
    'fellowship_name' => $_ENV['FELLOWSHIP_NAME'] ?? 'NICA Fellowship',
];

// Early validation
$requiredKeys = ['smtp_host', 'smtp_username', 'smtp_password', 'email_from'];
foreach ($requiredKeys as $key) {
    if (empty($config[$key])) {
        die("CRITICAL: Required config key '$key' is empty or missing.\n");
    }
}

// ────────────────────────────────────────────────
// Load email content
$email = include __DIR__ . '/build_email.php';

if (!is_array($email) || empty($email['to']) || empty($email['subject']) || empty($email['body'])) {
    echo "❌ Error: Could not generate email content (check build_email.php and CSV data)\n";
    exit(1);
}

// ────────────────────────────────────────────────
// Initialize PHPMailer
$mail = new PHPMailer(true);

try {
    // Debug: show config right before SMTP usage (this will prove if $config is intact)
    echo "DEBUG - Config right before SMTP setup:\n";
    var_export($config);
    echo "\n\n";

    $mail->SMTPDebug  = 2;
    $mail->Debugoutput = 'html';

    $mail->isSMTP();
    $mail->Host       = $config['smtp_host']       ?? throw new Exception("smtp_host missing at runtime");
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['smtp_username']   ?? throw new Exception("smtp_username missing at runtime");
    $mail->Password   = $config['smtp_password']   ?? throw new Exception("smtp_password missing at runtime");
    $mail->Timeout    = 30;

    $secure = $config['smtp_secure'] ?? 'ssl';
    $port   = $config['smtp_port']   ?? 465;

    if ($secure === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = $port ?: 465;
    } elseif (in_array($secure, ['tls', 'starttls'])) {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $port ?: 587;
    } else {
        $mail->SMTPSecure = '';
        $mail->Port       = $port ?: 25;
    }

    $mail->setFrom($config['email_from'], $config['fellowship_name']);
    $mail->addReplyTo($config['email_reply_to'], $config['fellowship_name']);

    $recipients = array_map('trim', explode(',', $email['to']));
    foreach ($recipients as $recipient) {
        if ($recipient !== '' && filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $mail->addAddress($recipient);
        }
    }

    if (empty($mail->getToAddresses())) {
        throw new Exception("No valid recipient email addresses found");
    }

    $mail->isHTML();
    $mail->Subject = $email['subject'];

    $css = file_exists(__DIR__ . '/style.css') ? file_get_contents(__DIR__ . '/style.css') : '';

    $htmlBody = '<!DOCTYPE html><html lang=""><head><meta charset="UTF-8">'
        . '<style>' . $css . '</style></head><body>'
        . '<pre style="font-family: Arial, sans-serif; white-space: pre-wrap; word-wrap: break-word; margin: 0; padding: 16px;">'
        . nl2br(htmlspecialchars($email['body'], ENT_QUOTES, 'UTF-8'))
        . '</pre></body></html>';

    $mail->Body    = $htmlBody;
    $mail->AltBody = $email['body'];

    $mail->send();

    echo "✅ Anniversary email sent successfully.\n\n";
    echo "----- EMAIL BODY (for WhatsApp copy) -----\n\n";
    echo $email['body'] . "\n";
    echo "------------------------------------------\n";

} catch (Exception $e) {
    echo "❌ Failed to send anniversary email.\n";
    echo "Mailer ErrorInfo: " . $mail->ErrorInfo . "\n";
    echo "Exception message: " . $e->getMessage() . "\n";
    echo "\nSee PHPMailer debug output above for details.\n";
}