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
	exit;
}

$post_id = StaysailIO::getInt('id');
$Post = new Post($post_id);

$Post->bumpBy(new Member($member_id));

print $Post->countBumps();