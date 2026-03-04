<?php

require_once '../private/tools/PHPMailer/src/MailException.php';
require_once '../private/tools/PHPMailer/src/PHPMailer.php';
require_once '../private/tools/PHPMailer/src/SMTP.php';

class MailSend
{
    private $Member;
    private $Entertainer;
    protected $framework;

    public function __construct(Member $Member)
    {
        $this->Member = $Member;
        $this->framework = StaysailIO::engage();
        if (StaysailIO::session('Entertainer.id')) {
            $this->Entertainer = new Entertainer(StaysailIO::session('Entertainer.id'));
        } else {
            $this->Entertainer = $this->Member->getAccountOfType('Entertainer');
        }
    }

    public function send($email, $subject, $message, $type = 0, $cc = true)
    {
        $toName = $this->Member->name;
        $mail = new PHPMailer(true);
        $pass = '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';
        $username = 'apikey';

        if (!$type) {
            $mailMain = 'support@yourfanslive.com';
            $name = 'Support';
        } else {
            $mailMain = 'authorization@yourfanslive.com ';
            $name = 'Authorization';
        }

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.sendgrid.net';
            $mail->SMTPAuth = true;
            $mail->Username = $username;
            $mail->Password = $pass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->SMTPDebug = 0;
            $mail->CharSet = 'UTF-8';

            // Recipients
            $mail->setFrom($mailMain, $name);
            $mail->addAddress($email, $toName);     // Add a recipient
            if ($cc) {
                $mail->addCC('yourfanslive@gmail.com');
                $mail->addCC('support@yourfanslive.com');
            }
            // Headers
            $mail->addCustomHeader('X-Mailer', 'PHP/' . phpversion());
            $mail->addCustomHeader('X-Priority', '3');
            $mail->addCustomHeader('Precedence', 'bulk');
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->AltBody = strip_tags($message);

            $mail->send();
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }

        return true;
    }
}
