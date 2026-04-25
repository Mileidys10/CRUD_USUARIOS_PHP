<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../Application/Ports/Out/NotificationPort.php';
require_once __DIR__ . '/../../../Domain/ValueObjects/UserEmail.php';

require_once __DIR__ . '/../../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

final class PhpMailerAdapter implements NotificationPort
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $fromEmail;
    private string $fromName;

    public function __construct()
    {
      
        $this->host = 'smtp.gmail.com'; 
        $this->port = 587;
        $this->username = 'classyexperiences123@gmail.com';
        $this->password = 'mcyw aenh oajh vxtp';
        $this->fromEmail = 'classyexperiences123@gmail.com';
        $this->fromName = 'Mi App CRUD';
    }

    public function sendEmail(UserEmail $email, string $subject, string $body): void
    {
     
        $mail = new PHPMailer(true);

        try {
          
            $mail->isSMTP();
            $mail->Host       = $this->host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->username;
            $mail->Password   = $this->password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $this->port;

            // Destinatarios
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($email->value());

            // Contenido
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
        } catch (Exception $e) {
            error_log("Error al enviar email: {$mail->ErrorInfo}");
        }
        
        
   
    }
}
