<?php
session_start();
require '../private/config.php';
require '../private/staysail/Staysail.php';
require '../private/interfaces/interface.AccountType.php';
require '../private/interfaces/interface.AccountPublic.php';
// Database info only needs to be passed the first time; StaysailIO::engage() will
// return a singleton instance henceforth.
$framework = StaysailIO::engage(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$member_id = StaysailIO::session('Member.id');
if (!$member_id) {
    print '';
	exit;
}
$Member = new Member($member_id);

$entertainer_id = StaysailIO::session('Entertainer.id');
$Entertainer = new Entertainer($entertainer_id);
$private_request = StaysailIO::get('p');

$WebShow_Request = new WebShow_Request();
$WebShow_Request->makeRequest($Member, $Entertainer, $private_request);

exit;