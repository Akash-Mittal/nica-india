<?php

require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

ob_start();
include __DIR__ . '/build_email.php';
$emailHtml = ob_get_clean();

if (trim($emailHtml) === '') {
    echo "Error: build_email.php produced empty HTML output.\n";
    exit(1);
}

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = 0;

    $mail->isSMTP();
    $mail->Host = $_ENV['SMTP_HOST'] ?? '';
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['SMTP_USERNAME'] ?? '';
    $mail->Password = $_ENV['SMTP_PASSWORD'] ?? '';
    $mail->Timeout = 30;

    $secure = strtolower($_ENV['SMTP_SECURE'] ?? 'ssl');
    $port = (int) ($_ENV['SMTP_PORT'] ?? 465);

    if ($secure === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $port ?: 465;
    } elseif (in_array($secure, ['tls', 'starttls'], true)) {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $port ?: 587;
    } else {
        $mail->SMTPSecure = '';
        $mail->Port = $port ?: 25;
    }

    $fromName = $_ENV['FELLOWSHIP_NAME'] ?? 'NICA Fellowship';

    $mail->setFrom($_ENV['EMAIL_FROM'], $fromName);
    $mail->addReplyTo($_ENV['EMAIL_REPLY_TO'] ?? $_ENV['EMAIL_FROM'], $fromName);

    $recipients = array_map('trim', explode(',', $_ENV['EMAIL_RECIPIENTS'] ?? ''));

    foreach ($recipients as $recipient) {
        if ($recipient !== '' && filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $mail->addAddress($recipient);
        }
    }

    if (empty($mail->getToAddresses())) {
        throw new Exception('No valid recipients.');
    }

    $mail->isHTML(true);
    $mail->Subject = $_ENV['EMAIL_SUBJECT'] ?? 'Anniversary Update';
    $mail->Body = $emailHtml;
    $mail->AltBody = 'Please view this email in an HTML-compatible mail client.';

    $mail->send();
    echo "Anniversary email sent successfully.\n";
} catch (Exception $e) {
    echo "Failed to send anniversary email.\n";
    echo $mail->ErrorInfo . "\n";
    exit(1);
}