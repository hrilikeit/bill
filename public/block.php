<?php
session_start();
require '../private/config.php';
require '../private/staysail/Staysail.php';
require '../private/interfaces/interface.AccountType.php';
require '../private/interfaces/interface.AccountPublic.php';
// Database info only needs to be passed the first time; StaysailIO::engage() will
// return a singleton instance henceforth.
$framework = StaysailIO::engage(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Get the Member object
$member_id = StaysailIO::session('Member.id');
if (!$member_id) {exit;}
$Member = new Member($member_id);
$role = $Member->getRole();

if ($role == Member::ROLE_ENTERTAINER) {
	$Entertainer = $Member->getAccountOfType('Entertainer');
	$fan_id = StaysailIO::getInt('fid');
	$Fan = new Fan($fan_id);
	$Fan_Subscription = $Fan->isSubscribedTo($Entertainer);
	if ($Fan_Subscription) {
		$banned_until_time = time() + (12 * 60 * 60); // Twelve hours
		$Fan_Subscription->banned_until_time = date('Y-m-d H:i:s', $banned_until_time);
		$Fan_Subscription->save();
	}
}