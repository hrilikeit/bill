<?php
/* Database setup */
define('DB_HOST', 'localhost');
define('DB_USER', 'sumu22fe_root');
define('DB_PASS', 'Admin22!qwertyu');
define('DB_NAME', 'yourfan_stage');

/* Framework setup */
define('STAYSAIL', '/home/sumu22fe/public_html/stage_yourfanslive/private/staysail/');
define('DOCROOT', '/home/sumu22fe/public_html/stage_yourfanslive/');

/* Error reporting in code */
define('CODE_DIAGNOSTIC', true);

/* How to behave when CODE DIAGNOSTIC is on */
if (CODE_DIAGNOSTIC) {
	ini_set('display_errors', 1);
	error_reporting(E_ALL);
}

define('SHORT_DATE_FORMAT', 'm/d/y');
define('DES_KEY', 'd83298ac4300ffcc04180726');
define('DES_KEY_NEW', 'd83298ac4300ffcc04180726');
define('DES_IV', '1234567891011121');
define('MAIN_URL', 'stage.yourfanslive.com/');
define('DATAROOT', '/home/sumu22fe/public_html/stage_yourfanslive/');
define('PYTHON_PATH', '/usr/bin/python3.6');

define('MAIL_SUPPORT', 'support@yourfanslive.com');
define('MAIL_AUTHORIZATION', 'authorization@yourfanslive.com');
define('MAIL_PASSWORD', 'Lucky13321332!');
define('MAIL_HOST', 'mail.yourfanslive.com');
define('MAIL_PORT', 465);
define('MAIL_ENCRYPTION', 'ssl');