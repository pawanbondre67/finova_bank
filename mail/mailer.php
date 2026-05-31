<?php
/**
 * PHPMailer wrapper - loads SMTP config from config/mail.php
 */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

$mail_config = __DIR__ . '/../config/mail.php';
if (file_exists($mail_config)) {
    require_once $mail_config;
}

function sendMail($to, $subject, $htmlBody)
{
    if (!class_exists(PHPMailer::class)) {
        return false;
    }

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = defined('MAIL_HOST') ? MAIL_HOST : 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = defined('MAIL_USER') ? MAIL_USER : '';
        $mail->Password   = defined('MAIL_PASS') ? str_replace(' ', '', MAIL_PASS) : '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = defined('MAIL_PORT') ? MAIL_PORT : 587;
        $mail->setFrom(defined('MAIL_FROM') ? MAIL_FROM : 'noreply.finovabank@gmail.com', 'Finova Bank');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        return $mail->send();
    } catch (Exception $e) {
        error_log('Finova Bank mail failed: ' . $mail->ErrorInfo);
        return false;
    }
}
