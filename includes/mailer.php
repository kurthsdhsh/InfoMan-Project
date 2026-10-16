<?php
require __DIR__ . '/../PHPMailer/src/Exception.php';
require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';

define('MAIL_HOST', 'sandbox.smtp.mailtrap.io');
define('MAIL_PORT', 2525);
define('MAIL_USER', 'PASTE_SANDBOX_USERNAME');
define('MAIL_PASS', 'PASTE_SANDBOX_PASSWORD');
define('MAIL_FROM', 'noreply@myshop.test');
define('BASE_URL',  'http://localhost/InfoMan-Project');

function sendMail($toEmail, $subject, $htmlBody)
{
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'sandbox.smtp.mailtrap.io';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'c6936cafdcc61a';
        $mail->Password   = '77682cc3ef3793';
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 2525;

        $mail->setFrom(MAIL_FROM, 'My Shop');
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->send();
        return true;
    } catch (\Exception $e) {
        return false;
    }
}