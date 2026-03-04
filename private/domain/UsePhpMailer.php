<?php

require_once __DIR__.'/../tools/PHPMailer/src/Exception.php';
require_once __DIR__.'/../tools/PHPMailer/src/PHPMailer.php';
require_once __DIR__.'/../tools/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\PHPMailer;

class UsePhpMailer 
{
    public $mail;
    private $host = 'mail.yourfanslive.com';
    private $username = 'support@yourfanslive.com';
    private $password = '&P+U,GIOHYUd';
    private $port = 465;

    public function __construct() {

        $this->mail = new PHPMailer(true);
    }

    public function sendEmail($user_email,$subject,$message,$from,$replyTo,$cc)
    {
        try {
			//Server settings
	    	$this->mail->SMTPDebug = 0;                     
			$this->mail->isSMTP();                                            
			$this->mail->Host       = $this->host;                   
			$this->mail->SMTPAuth   = true;                                   
			$this->mail->Username   = $this->username;                   
			$this->mail->Password   = $this->password;                              
			$this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            
			$this->mail->Port       = $this->port;   

			//Recipients
			$this->mail->setFrom($from, $from);
			$this->mail->addAddress($user_email);               
			$this->mail->addReplyTo($replyTo, $replyTo);
			$this->mail->addCC($cc);

			//Content
			$this->mail->isHTML(true);                                
			$this->mail->Subject = $subject;
			$this->mail->Body    = $message;

			if ($this->mail->send()) {

				return true;
			}
			
			
		} catch (Exception $e) {

			return false;
		}
    }
		

}