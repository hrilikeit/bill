<?php
session_start();
require '../private/config.php';
require '../private/staysail/Staysail.php';
require '../private/interfaces/interface.AccountType.php';
require '../private/interfaces/interface.AccountPublic.php';
require '../private/tools/WebCamDyte.php';

$framework = StaysailIO::engage(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$member_id = StaysailIO::session('Member.id');
if (!$member_id) {
    print '';
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['show_type'];
    $email = $_POST['goal_status'];
    $email = $_POST['fan_id'];

//    $member_id = StaysailIO::session('Member.id');
    $member = new Member($member_id);

    if (StaysailIO::session('Entertainer.id')) {
        $entertainer = new Entertainer(StaysailIO::session('Entertainer.id'));
    } else {
        $entertainer = $member->getAccountOfType('Entertainer');
    }

    if ($member->getRole() != Member::ROLE_ENTERTAINER) {
        return '';
    }
//$memberId = $this->Member->id;
    $name = $member->name;
    $EntertainerId = $entertainer->id;
    if (!$name) {
        $name = $entertainer->stage_name;
    }
    if (StaysailIO::post('show_type') == 1 || StaysailIO::post('show_type') == 0 || StaysailIO::post('show_type') == 2) {
        if (StaysailIO::post('show_type') == 2) {
            $webShowSubscribers = $framework->getAllIdsRowsByConditions('WebShowDyte',
                [
                    'Member_id' => $member->id,
                    'show_type' => 1
                ]
            );
            foreach ($webShowSubscribers as $webShowSubscriber) {
                $w = new WebShowDyte($webShowSubscriber[0]);
                $w->delete_Job();
            }
        }
        $webShowPrivate = $framework->getAllIdsRowsByConditions('WebShowDyte',
            [
                'Member_id' => $member->id,
                'show_type' => 0
            ]
        );
        foreach ($webShowPrivate as $webShowPrivateRow) {
            $w = new WebShowDyte($webShowPrivateRow[0]);
            $w->delete_Job();
        }
    }
    $webShowDyte = new WebShowDyte();
    $WebShow = new WebCamDyte();
    $meetingData = $WebShow->createMeeting($entertainer->name);
    $meetingId = $meetingData['id'];
    if (!$meetingData) {
        return "<h1>Sorry...</h1><p>The system has failed to create the show at this time.</p>";
    }
    $webShowDyte->Member_id = $member->id;
    $webShowDyte->Entertainer_id = $entertainer->id;
    $webShowDyte->Fan_id = StaysailIO::post('show_type') == 0 ? StaysailIO::post('fan_id') : null;
    $webShowDyte->title = $meetingData['title'];
    $webShowDyte->meeting_id = $meetingId;
    $webShowDyte->show_type = StaysailIO::post('show_type');
    if (StaysailIO::post('show_type') < 3) {
        $webShowDyte->public_live_status = 1;
        $webShowDyte->update_at = strtotime("now");
    } else {
        $webShowDyte->public_live_status = 0;
        $webShowDyte->update_at = 0;
    }
    $webShowDyte->like_count = 0;
    $webShowDyte->view_count = 0;
//echo"<pre>";
//var_dump($webShowDyte);
//die();

    $webShowDyte->save();
    $participantData = $WebShow->createParticipant($webShowDyte, $member);
    $createdAt = new DateTime($participantData['created_at'], new DateTimeZone('UTC'));
    $updatedAt = new DateTime($participantData['updated_at'], new DateTimeZone('UTC'));
////
    $DyteParticipantModel = new DyteParticipant();
    $DyteParticipantModel->Member_id = $member->id;
    $DyteParticipantModel->name = $participantData['name'];
    $DyteParticipantModel->picture = $participantData['picture'];
    $DyteParticipantModel->participant_id = $member->getAccountOfType('Fan') ? $member->getAccountOfType('Fan')->id : $member->id;
    $DyteParticipantModel->preset_name = $member->getAccountOfType('Fan') ? 'livestream_host' : 'livestream_participant';
    $DyteParticipantModel->created_at = $createdAt->format('Y-m-d H:i:s');
    $DyteParticipantModel->updated_at = $updatedAt->format('Y-m-d H:i:s');
    $DyteParticipantModel->token = $participantData['token'];
    $DyteParticipantModel->WebShowDyte_id = $webShowDyte->id;
    $DyteParticipantModel->save();

    if (StaysailIO::post('goal_status')) {
        $goalStatus = StaysailIO::post('goal_status');
        $filters = array(
            new Filter(Filter::Where, "Member_id = $member->id"),
        );
        $goal = $framework->getSingle('Goal', $filters);
        $goal->status = $goalStatus;
        $goal->save();

    }

    $token = $DyteParticipantModel->token;
    $domain = $_SERVER['SERVER_NAME'];
    $typeShow = $webShowDyte->show_type;
//////        $link = "<script>window.open(`/liveStream.php?authToken=$token&Entertainer_id=$EntertainerId&meeting_id=$meetingId&goal_status=$goalStatus`)</script>";
    $link = "/liveStream.php?authToken=$token&Entertainer_id=$EntertainerId&meeting_id=$meetingId&typeOfShow=$typeShow";

    $fans = $entertainer->getSubscribers();
    if ($fans) {
        foreach ($fans as $Fan_Subscription) {
            $Fan = $Fan_Subscription->Fan;
            $fanMember = new Member($Fan->Member_id);
            $message = "{$name} is now LIVE! Join the STREAM $domain";
            $subject = "LIVE!";
            $MailSend = new MailSend($fanMember);
            //$MailSend->send($fanMember->email, $subject, $message, 0, false);
        }
    }

////        if ($domain == 'yourfanslive.com' && class_exists('SMSSender')) {
////            $fans = $this->Entertainer->getSubscribers();
////            if ($fans) {
////                foreach ($fans as $Fan_Subscription) {
////                    $Fan = $Fan_Subscription->Fan;
////                    if ($Fan->Member && $Fan->Member->phone && $Fan->Member->sms_optout == 0) {
////                        $SMSSender = new SMSSender($Fan->Member, 'LocalCityScene');
////                        $message = "{$name} is now LIVE! Join the STREAM $domain";
////                        $SMSSender->send($message);
////                    }
////                }
////            }
////        }
///
//$link = 'ccc';
    echo json_encode(['url' => $link]);
}