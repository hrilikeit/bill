<?php

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

require_once '/home/sumu22fe/public_html/stage_yourfanslive/private/class.MailSend.php';
//require '../private/class.MailSend.php';
$mail = new MailSend();
$mail->send('ichbinzp90@gmail.com', 'Test Email', '<b>Hello World</b>');
//
echo "Hello World12!";
//echo 'Email sent (or failed — check php-error.log)';
