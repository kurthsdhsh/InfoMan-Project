<?php
require __DIR__ . '/../PHPMailer/src/Exception.php';
require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';
require __DIR__ . '/../config/mail_config.php';

define('MAIL_HOST', 'sandbox.smtp.mailtrap.io');
define('MAIL_PORT', 2525);
define('MAIL_USER', 'PASTE_SANDBOX_USERNAME');
define('MAIL_PASS', 'PASTE_SANDBOX_PASSWORD');
define('MAIL_FROM', 'noreply@myshop.test');
define('BASE_URL',  'http://localhost/InfoMan-Project');

   function sendMail($toEmail, $subject, $htmlBody, array $attachments = [])
{
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host     = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username = MAIL_USER;
        //$mail->Password   = '08f7d417c4ffcc';
        // $mail->Username   = 'c6936cafdcc61a';
        $mail->Password = MAIL_PASS;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port     = MAIL_PORT;

        $mail->setFrom(MAIL_FROM, 'KURDIELLA\'S BAGUIO TREASURES');
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody);
        foreach ($attachments as $a) {
       $mail->addStringAttachment($a['data'], $a['name'], 'base64', $a['type'] ?? 'application/pdf');
   }
        $mail->send();
        return true;
    } catch (\Exception $e) {
        return false;
    }
}