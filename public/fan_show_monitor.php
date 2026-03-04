<?php
session_start();
require '../private/config.php';
require '../private/staysail/Staysail.php';
require '../private/interfaces/interface.AccountType.php';
require '../private/interfaces/interface.AccountPublic.php';
// Database info only needs to be passed the first time; StaysailIO::engage() will
// return a singleton instance henceforth.
$framework = StaysailIO::engage(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$now = StaysailIO::now();

// Get the Fan_WebShow_Status object
$member_id = StaysailIO::session('Member.id');
if (!$member_id) {print "!M";exit;}

$Member = new Member($member_id);
$Fan = $Member->getAccountOfType('Fan');
if (!$Fan) {print "!F";exit;}

$entertainer_id = StaysailIO::session('Entertainer.id');
$Entertainer = new Entertainer($entertainer_id);
$WebShow = $Entertainer->showInProgress();

if (!$WebShow) {print "!W";exit;}

$filters = array(new Filter(Filter::Match, array('Fan_id' => $Fan->id, 'WebShow_id' => $WebShow->id)));
$statuses = $framework->getSubset('Fan_WebShow_Status', $filters);
if (!sizeof($statuses)) {print "!S";exit;}

// Of the matching statuses, find one that hasn't been spent
$Fan_WebShow_Status = null;
foreach ($statuses as $webshow_status)
{
	if ($webshow_status->polls < $webshow_status->minutes_purchased) {
		$Fan_WebShow_Status = $webshow_status;
		break;
	}
}
if ($Fan_WebShow_Status == null) {print "!S";exit;}

// Has it been at least a minute since the last poll?
if ($Fan_WebShow_Status->last_poll_time != '' and $Fan_WebShow_Status->secondsSinceLastPoll() < 60) {
	$remaining = $Fan_WebShow_Status->minutes_purchased - $Fan_WebShow_Status->polls;
	if ($remaining < 0) {
		$remaining = 0;
	}
	print "T={$remaining}";
	exit;
}

// Is the show still in progress?
if (!$WebShow->getStatus()) {print "!W";exit;}

// Then increment the poll counter
$polls = intval($Fan_WebShow_Status->polls);
$updates = array('last_poll_time' => $now);
if ($polls < $Fan_WebShow_Status->minutes_purchased) {
	$updates['polls'] = $polls + 1;
}
if ($Fan_WebShow_Status->start_time == '') {
	$updates['start_time'] = $now;
}
if ($polls == $Fan_WebShow_Status->minutes_purchased) {print "EX";exit;}			
$Fan_WebShow_Status->update($updates);
$Fan_WebShow_Status->save();

// Update the filesystem log

// Return the number of minutes remaining
$remaining = $Fan_WebShow_Status->minutes_purchased - $Fan_WebShow_Status->polls;
if ($remaining < 0) {
	$remaining = 0;
}
print "T={$remaining}";