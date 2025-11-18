<?php
// Import PHPMailer classes manually
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/../../vendor/PHPMailer.php';
require_once __DIR__ . '/../../vendor/SMTP.php';
require_once __DIR__ . '/../../vendor/Exception.php';

class Mailer {
    
    public static function send($to, $subject, $message) {
        $mail = new PHPMailer(true);

        // --- 1. ROBUST CREDENTIAL LOADING ---
        // Try to load credentials from multiple sources
        $username = getenv('SMTP_USER');
        if (!$username) $username = $_ENV['SMTP_USER'] ?? ($_SERVER['SMTP_USER'] ?? null);

        $password = getenv('SMTP_PASS');
        if (!$password) $password = $_ENV['SMTP_PASS'] ?? ($_SERVER['SMTP_PASS'] ?? null);

        $fromName = getenv('SMTP_FROM_NAME');
        if (!$fromName) $fromName = $_ENV['SMTP_FROM_NAME'] ?? ($_SERVER['SMTP_FROM_NAME'] ?? 'BPC Attendance System');

        // --- 2. CRITICAL FIX: STRIP SPACES ---
        // Google App Passwords often have spaces (e.g., "abcd efgh") that must be removed
        if ($password) {
            $password = str_replace(' ', '', $password);
        }

        // Debug: Log if credentials are missing
        if (empty($username) || empty($password)) {
            self::logError("CRITICAL ERROR: Credentials missing. Check .env file.");
            return false;
        }

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $username;
            $mail->Password   = $password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom($username, $fromName);
            $mail->addAddress($to);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $message;
            $mail->AltBody = strip_tags($message);

            $mail->send();
            
            // Success Log
            self::logError("SUCCESS: Email sent to $to | Subject: $subject");
            return true;

        } catch (Exception $e) {
            // Failure Log
            self::logError("MAILER ERROR: {$mail->ErrorInfo} | To: $to");
            return false;
        }
    }

    private static function logError($msg) {
        // Logs to 'email_debug.log' in the root folder
        $logFile = __DIR__ . '/../../email_debug.log';
        $entry = date('Y-m-d H:i:s') . " - " . $msg . "\n";
        file_put_contents($logFile, $entry, FILE_APPEND);
    }
}
?>