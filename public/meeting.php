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

$entertainer_id = StaysailIO::getInt('id');
$Entertainer = new Entertainer($entertainer_id);
$time = time();

$WebShow = null;
if (StaysailIO::get('ws')) {
	$WebShow = $Entertainer->showInProgress();
}

if (StaysailIO::get('p')) {
	$comment = StaysailIO::post('comment');
	$comment = strip_tags($comment);
	$Member = new Member($member_id);
	$post_as = $Member->name;

	$comment = str_replace("\n", "</br>\n", $comment);
	$MeetingPost = new MeetingPost();
	$class = ($Member->id == $Entertainer->Member->id) ? 'entertainer' : '';

	$content = "<div class=\"{$class}\"><strong>{$post_as}</strong> @%TIME% : {$comment}</div>";
	$updates = array('Member_id' => $Member->id,
					 'Entertainer_id' => $Entertainer->id,
				     'content' => $content,
					 'post_time' => date('Y-m-d H:i:s'),
				    );
	if ($WebShow) {
		$updates['WebShow_id'] = $WebShow->id;
	}
	$MeetingPost->update($updates);
	$MeetingPost->save();
	
//	if ($Member->getRole() == Member::ROLE_FAN) {
//		$Entertainer_Member = $Entertainer->Member;
//		if (!$Entertainer_Member->isOnline()) {
//			// If an Entertainer is the recipient, and she's not online, send an SMS
//			require '../private/tools/SMSSender.php';
//			$from = 'LocalCityScene';
//			$message = "{$Member->name}: {$comment}";
//			$SMSSender = new SMSSender($Entertainer_Member, $from);
//			$SMSSender->truncate();
//			$SMSSender->send($message);
//		}
//	}
	
	$since = $MeetingPost->id;
	$posts = $Entertainer->getChatSince($since - 1, true, $WebShow);
	print "{$time}|||||0|||||{$posts}";
	exit;	
}

$since = StaysailIO::getInt('s');
if (!$since) {$since = 0;}
$posts = $Entertainer->getChatSince($since, true, $WebShow);

print "{$time}|||||0|||||{$posts}";
exit;