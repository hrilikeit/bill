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

// Get the Member object
$member_id = StaysailIO::session('Member.id');
if (!$member_id) {exit;}
$Member = new Member($member_id);
$Member->updateOnline();
$role = $Member->getRole();
$commands = '';

// For all types of users: get message count
$message_count = sizeof($Member->getUnreadMessages());
$commands .= "activity::Message|{$message_count}\n";

if ($role == Member::ROLE_ENTERTAINER) {
	$Entertainer = $Member->getAccountOfType('Entertainer');

    $OldWebShow = new WebShow();
    $referer = $_SERVER['HTTP_REFERER'];

    $start = strpos($referer, 'meeting_id=');
    if ($start !== false) {
        $start += strlen('meeting_id=');
        $end = strpos($referer, '&', $start);
        $channelId = substr($referer, $start, $end - $start);

        $OldWebShow->Entertainer_id =$Entertainer->id;
        $OldWebShow->channel_id = $channelId;

        $GetSingle = $framework->getSingle('WebShow', new Filter(Filter::Match, array('channel_id' => $channelId)));

        if(!$GetSingle){
            $OldWebShow->start();
        }else{
            $OldWebShow->updatePollTime($channelId);
        }

    } else {
        // Если meeting_id не найден в строке HTTP_REFERER

    }


	// Check for new reviews
	$review_count = count($Entertainer->getReviews());
	$commands .= "activity::EntertainerProfilereviews|{$review_count}\n";

    // Check for online
    $online_status = ($Member->isOnline());
    $commands .= "online::EntertainerOnline|{$online_status}\n";

	// Check for new fans
	$fan_count = count($Entertainer->getSubscribers());
	$commands .= "activity::EntertainerProfilefans|{$fan_count}\n";

	// Check for doorbell notifications
	$requests = $Entertainer->getWebShowRequests();
	if (sizeof($requests)) {
		foreach ($requests as $WebShow_Request)
		{
			$WebShow_Request->deliver();
		}
		$commands .= $WebShow_Request->getNotificationCommand();
	}

	// Check for running webshow
	$WebShow = $Entertainer->showInProgress();
	if ($WebShow) {
        $live_online = $WebShow->compareTime($WebShow->channel_id);


		//$watchers = $WebShow->getWatchers();
		//$WebShow->advancePoll();
//		foreach ($watchers as $watcher)
//		{
//			$name = $watcher['name'];
//			$last_poll_time = strtotime($watcher['last_poll_time']);
//			$status = ((time() - $last_poll_time) < 10) ? 'Watching' : 'Not Watching';
//			$Fan = new Fan($watcher['Fan_id']);
//			if (!$Fan->isOnline()) {$status = 'Offline';}
//
//			if ($status == 'Offline') {
//				$time_notice = '';
//			} else {
//				$minutes_purchased = $watcher['minutes_purchased'];
//				$seconds_used = ($watcher['polls'] - 1) * 5;
//				$seconds_left = ($minutes_purchased * 60) - $seconds_used;
//				if ($seconds_left > 0) {
//					$minutes = intval($seconds_left / 60);
//					$seconds = $seconds_left - ($minutes * 60);
//					$seconds = str_pad($seconds, 2, '0', STR_PAD_LEFT);
//					$time_notice = "{$minutes}:{$seconds} Remaining";
//				} else {
//					$time_notice = "Time has expired";
//				}
//			}
        $commands .= "activity::StreamOnline|online\n";
//		}
	}
}

if ($role == Member::ROLE_FAN)
{
	$Fan = $Member->getAccountOfType('Fan');
	
	// Check for online entertainers
	$subscriptions = $Fan->getActiveSubscriptions();
	foreach ($subscriptions as $Fan_Subscription)
	{
		$Entertainer = $Fan_Subscription->Entertainer;
		$online = $Entertainer->isOnline() ? 'yes' : 'no';
		$commands .= "online::{$Entertainer->id}|{$online}\n";
	}
	
	// Check existing web show requests
	$requests = $Fan->getWebShowRequests();
	
	// Do any of the requested entertainers have a running show (either public, or private for this fan?)
	foreach ($requests as $WebShow_Request)
	{
		$Entertainer = $WebShow_Request->Entertainer;
		$WebShow = $Entertainer->showInProgress();
		$notified = StaysailIO::session("WebShow_notified_{$WebShow->id}");
		if ($WebShow and !$notified) {
			$type = '';
			if ($WebShow->Fan_id) {
				if ($WebShow->Fan_id == $Fan->id) {
					$type = 'Private';
				}
			} else {
				$type = 'Group';
			}
			if ($type) {
				$commands .= "webshow::{$Entertainer->name} started a {$type} Show!|{$WebShow->id}";
				StaysailIO::setSession("WebShow_notified_{$WebShow->id}", true);
			}
		}
	}
	
	// Is this fan currently watching a web show?
	if (StaysailIO::get('show')) { // Looking for this to prevent polls from being advanced on non-show screens
		$entertainer_id = StaysailIO::session('Entertainer.id');
		$Entertainer = new Entertainer($entertainer_id);
		$WebShow = $Entertainer->showInProgress();
		if (!$WebShow) {
			$commands .= "showstatus::ended";
		} else {
			$filters = array(new Filter(Filter::Match, array('Fan_id' => $Fan->id, 'WebShow_id' => $WebShow->id)));
			$statuses = $framework->getSubset('Fan_WebShow_Status', $filters);
		
			// Of the matching statuses, find one that hasn't been spent
			$Fan_WebShow_Status = null;
			foreach ($statuses as $webshow_status)
			{
				if (!$webshow_status->isExpired()) {
					$Fan_WebShow_Status = $webshow_status;
				}
			}
			
			if ($Fan_WebShow_Status) {
				$webshow_last_poll = $Fan_WebShow_Status->WebShow->last_poll_time;
				if (time() - strtotime($webshow_last_poll) > 10) {
					// If the entertainer's machine hasn't polled for a while, set the fan's last poll time,
					// but do not advance the poll counter.  The fan is charged on the basis of the counter, and
					// the entertainer doesn't appear to be transmitting.
					$Fan_WebShow_Status->last_poll_time = date('Y-m-d H:i:s');
					$commands = "showstatus::noshow";
				} else {
					$time_remaining = $Fan_WebShow_Status->advancePoll();
					
					if ($Fan->isBannedFrom($Entertainer)) {
						$commands .= "showstatus::expired";
					} else {
						$commands .= "showstatus::{$time_remaining}";
					}
				}
			} else {
				$commands .= "showstatus::expired";
			}
		}
	}
}

print $commands;