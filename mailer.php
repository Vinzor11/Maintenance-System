
<?php
// mailer.php (require PHPMailer via Composer or direct!)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

function sendMail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.provider.com'; // set your mail host
        $mail->SMTPAuth = true;
        $mail->Username = 'youremail@provider.com';
        $mail->Password = 'yourpass';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('noreply@yourdomain.com', 'Maintenance System');
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
    } catch (Exception $e) { /* log error if needed */ }
}
