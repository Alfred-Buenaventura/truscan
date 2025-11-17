<?php
// Import PHPMailer classes manually since we aren't using Composer autoload yet
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/../../vendor/PHPMailer.php';
require_once __DIR__ . '/../../vendor/SMTP.php';
require_once __DIR__ . '/../../vendor/Exception.php';

class Mailer {
    
    public static function send($to, $subject, $message) {
        $mail = new PHPMailer(true);

        // --- CONFIGURATION ---
        // Ideally, put these in a separate config file or environment variables
        $smtp_username = 'bpcattendancemonitoringsystem@gmail.com'; 
        $smtp_password = 'bmgu yack ddsb xfzp'; // APP PASSWORD
        $smtp_from_name = 'BPC Attendance Monitoring System';

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp_username;
            $mail->Password   = $smtp_password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            //Recipients
            $mail->setFrom($smtp_username, $smtp_from_name);
            $mail->addAddress($to);

            //Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $message;
            $mail->AltBody = strip_tags($message);

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
}
?>