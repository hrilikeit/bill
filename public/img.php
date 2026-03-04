<?php
// Get an image from the private directory, checking user access and image status

session_start();
require '../private/config.php';
require '../private/staysail/Staysail.php';
require '../private/interfaces/interface.AccountType.php';
StaysailIO::engage(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$library_id = StaysailIO::getInt('id');
$web = StaysailIO::getInt('w'); // If this is set, the web-sized image will be delivered

if (!$library_id) {
	showNullImage();
	exit;
}

$Library = new Library($library_id);
if (!$Library->id) {
	showNullImage();
	exit;
}

$member_id = StaysailIO::session('Member.id');
$Member = ($member_id) ? new Member($member_id) : null;

if ($Library->hasAccess($Member)) {
	$Library->toBrowser($web);
} else {
	$image = file_get_contents(DATAROOT . '/public/site_img/no_access.jpg');
	header("Content-Type:image/jpg");
	print $image;
}

exit;


function showNullImage()
{
	$image = file_get_contents(DATAROOT . '/public/site_img/null.png');
	header("Content-Type:image/png");
	print $image;
}
