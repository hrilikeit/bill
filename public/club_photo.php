<?php
// Get an image from the private directory, checking user access and image status

session_start();
require '../private/config.php';
require '../private/staysail/Staysail.php';
require '../private/interfaces/interface.AccountType.php';
StaysailIO::engage(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$club_id = StaysailIO::getInt('a');
$postfix = StaysailIO::get('t');
if ($postfix) {$postfix = "_{$postfix}";}

if (!$club_id) {showNullImage();}

$try = array('jpg', 'png');
foreach ($try as $filetype) 
{
	if (file_exists(DATAROOT . "/private/avatars/club{$club_id}.{$filetype}")) {
		$image = file_get_contents(DATAROOT . "/private/avatars/club{$club_id}{$postfix}.{$filetype}");
		header("Content-Type:image/{$filetype}");
		print $image;
		exit;
	}
}




function showNullImage()
{
	$image = file_get_contents(DATAROOT . '/public/site_img/null.png');
	header("Content-Type:image/png");
	print $image;
}