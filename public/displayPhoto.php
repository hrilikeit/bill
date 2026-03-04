<?php
// Get an image from the private directory, checking user access and image status

session_start();
require '../private/config.php';
require '../private/staysail/Staysail.php';
require '../private/interfaces/interface.AccountType.php';
StaysailIO::engage(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$avatar_member_id = StaysailIO::getInt('a');
$member_id = StaysailIO::session('Member.id');
$web = StaysailIO::getInt('w'); // If this is set, the web-sized image will be delivered

//if ((!$member_id and !StaysailIO::session('inviter_entertainer_id') and !StaysailIO::session('inviter_club_id')) or !$avatar_member_id) {showNullImage();}

$try = array('jpg', 'png');
foreach ($try as $filetype) 
{
	if (file_exists(DATAROOT . "/private/avatars/displayPhoto{$avatar_member_id}.{$filetype}")) {
		//if ($web) {
			//print toBrowserResized($avatar_member_id, $filetype);
		//} else {
			header("Content-Type:image/{$filetype}");
			$image = file_get_contents(DATAROOT . "/private/avatars/displayPhoto{$avatar_member_id}.{$filetype}");
			print $image;
		//}
		exit;
	}
}

$image = file_get_contents(DATAROOT . "/public/site_img/generic_avatar.png");
header("Content-Type:image/png");
print $image;
exit;




function showNullImage()
{
	$image = file_get_contents(DATAROOT . '/public/site_img/null.png');
	header("Content-Type:image/png");
	print $image;
}

function toBrowserResized($avatar_member_id, $filetype)
{
	$path = DATAROOT . "/private/avatars/displayPhoto{$avatar_member_id}.{$filetype}";
	$new_width = 800;
    	
    $size = GetImageSize($path);
    $new_height = (int)(($new_width/$size[0]) * $size[1]);
	$smaller = ImageCreateTrueColor($new_width, $new_height);
	

	switch ($size['mime'])
	{
		case 'image/jpeg':
			$src_img = ImageCreateFromJPEG($path);
			break;
			
		case 'image/png':
			$src_img = ImageCreateFromPNG($path);
			break;
	}
	ImageCopyResampled($smaller, $src_img, 0, 0, 0, 0, $new_width, $new_height, $size[0], $size[1] );
			
	header("Content-type:image/jpeg");
	ImageJPEG($smaller);
	ImageDestroy($smaller);    				
}
