<?php
// send_anniversary_email.php

require __DIR__ . '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$config = include "config.php";
$email  = include "build_email.php";

$mail = new PHPMailer(true);

try {
    // SMTP configuration
    $mail->isSMTP();
    $mail->Host       = $config['smtp_host'];         // mail.mittal.blog
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['smtp_username'];     // akash@mittal.blog
    $mail->Password   = $config['smtp_password'];     // your password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;  // for port 465 (SSL)
    $mail->Port       = $config['smtp_port'];         // 465

    // From / Reply-To
    $mail->setFrom($config['email_from'], $config['fellowship_name']);
    $mail->addReplyTo($config['email_reply_to'], $config['fellowship_name']);

    // Multiple recipients from comma-separated list
    $recipients = explode(',', $email['to']);
    foreach ($recipients as $recipient) {
        $recipient = trim($recipient);
        if ($recipient !== '') {
            $mail->addAddress($recipient);
        }
    }

// Email content – HTML + plain text fallback
    $mail->isHTML(true);
    $mail->Subject = $email['subject'];

    // Convert newlines to <br> and wrap in <pre> to keep structure
    $htmlBody  = nl2br(htmlspecialchars($email['body'], ENT_QUOTES, 'UTF-8'));

    $css = file_get_contents("style.css");

    $mail->Body =
        '<style>' . $css . '</style>' .
        '<pre style="font-family: Arial, sans-serif; white-space: pre-wrap;">'
        . nl2br(htmlspecialchars($email['body'], ENT_QUOTES, 'UTF-8'))
        . '</pre>';

    // Plain text version for older clients + WhatsApp copy
    $mail->AltBody = $email['body'];
    // Send the email
    $mail->send();

    echo "✅ Anniversary email sent successfully." . PHP_EOL . PHP_EOL;

    echo "----- EMAIL BODY (for WhatsApp copy) -----" . PHP_EOL . PHP_EOL;
    echo $email['body'] . PHP_EOL;
    echo "------------------------------------------" . PHP_EOL;

} catch (Exception $e) {
    echo "❌ Failed to send anniversary email." . PHP_EOL;
    echo "Mailer Error: " . $mail->ErrorInfo . PHP_EOL;
}