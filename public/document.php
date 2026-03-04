<?php
// Get an image from the private directory, checking user access and image status

session_start();
require '../private/config.php';
require '../private/staysail/Staysail.php';
require '../private/interfaces/interface.AccountType.php';
StaysailIO::engage(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$Member_Docs_id = StaysailIO::getInt('id');
$Member_Docs_file_type = StaysailIO::get('type');
$web = 1;//StaysailIO::getInt('w'); // If this is set, the web-sized image will be delivered

if (!$Member_Docs_id || !$Member_Docs_file_type) {
    showNullImage();
    exit;
}

$memberDocs = new Member_Docs($Member_Docs_id);
if (!$memberDocs->id) {
    showNullImage();
    exit;
}
new Admin(StaysailIO::session('Admin.id'));
$adminId = StaysailIO::session('Admin.id');
$Admin = ($adminId) ? new Admin($adminId) : null;

$Member = new Member($memberDocs->Member_id);
$type = $Member->getAccountType();
if (!$Admin || !$Admin->has(strtolower($type))) {
    $image = file_get_contents(DATAROOT . '/public/site_img/no_access.jpg');
    header("Content-Type:image/jpg");

    print $image;
}
else {
    $memberDocs->toBrowserAdmin($Member_Docs_file_type, $web);
}

exit;

function showNullImage()
{
    $image = file_get_contents(DATAROOT . '/public/site_img/null.png');
    header("Content-Type:image/png");
    print $image;
}
