<?php

require '../private/views/WebShowView.php';
require '../private/views/LiveChatView.php';
require '../private/tools/WebCamDyte.php';
require '../private/domain/class.MailSend.php';

class WebShowModule extends StaysailPublic
{
    protected $page, $settings, $categories;
    protected $_framework;

    public $Member;
    public $Entertainer;
    public $WebShow;

    public function __construct($dbc = '')
    {
        $this->valid = false;

        $this->_framework = StaysailIO::engage();

        $member_id = StaysailIO::session('Member.id');
        $this->Member = new Member($member_id);

        if (StaysailIO::session('Entertainer.id')) {
            $this->Entertainer = new Entertainer(StaysailIO::session('Entertainer.id'));
        } else {
            $this->Entertainer = $this->Member->getAccountOfType('Entertainer');
        }
    }

    public function getHTML()
    {
        $job = StaysailIO::get('job');
        $id = StaysailIO::get('id');

        // Handle banning
//        if ($this->Member->getRole() == Member::ROLE_FAN) {
//            $Fan = $this->Member->getAccountOfType('Fan');
//            if ($Fan->isBannedFrom($this->Entertainer)) {
//                header("Location:?mode=FanHome&da={$this->Entertainer->id}");
//                exit;
//            }
//        }

        $map = Maps::getWebShowMap();

        $left_content = '';
        switch ($job) {
            case 'prestart_show':
                $left_content = $this->collectMetadata();
                break;

            case 'start_show':
                $left_content = $this->startShow();
                break;

            case 'add_tip':
                $left_content = $this->addTip();
                break;

            case 'delete_tip':
                $left_content = $this->deleteTip();
                break;

            case 'add_fan_participant':
                $left_content = $this->addFanParticipant();
                break;

            case 'stream_live':
                $left_content = $this->streamLive();
                break;

            case 'resume_show':
                $left_content = $this->resumeShow();
                break;

            case 'purchase_show':
                $left_content = $this->purchaseShow();
                break;

            case 'join_show':
                $left_content = $this->joinShow();
                break;

            case 'end_show':
                $left_content = $this->endShow();
                break;

            case 'create_goal':
                $left_content = $this->createGoal();
                break;

             case 'delete_goal':
                 $left_content = $this->deleteGoal();
                 break;

             case 'goal_status':
                 $left_content = $this->goalStatus();
                 break;

            default:
                $left_content = $this->joinShow();
                break;
        }

        $header = new HeaderView();
        $footer = new FooterView();
        $action = new ActionsView($this->Member);
        $fan_cam_html = $this->getFanCamHTML();
        $containers = array(new StaysailContainer('H', 'header', $header->getHTML()),
            new StaysailContainer('F', 'footer', $footer->getHTML()),
            new StaysailContainer('A', 'action', $action->getHTML()),
        );
        $chat = new LiveChatView($this->Member);
        if ($this->WebShow) {
            $private = $this->WebShow->isPrivate();
        } else {
            $private = false;
        }
        $containers[] = new StaysailContainer('L', 'webshow', $left_content . $fan_cam_html);
//        $containers[] = new StaysailContainer('R', 'chat', $chat->getHTML($private));
        $layout = new StaysailLayout($map, $containers);

        return $layout->getHTML();
    }

    private function collectMetadata()
    {
        $writer = new StaysailWriter();
        $group_price = $this->Entertainer->allow_custom_pricing ? '' : " - \${$this->Entertainer->group_show_price}";
        $private_price = $this->Entertainer->allow_custom_pricing ? '' : " - \${$this->Entertainer->private_show_price}";
        $show_type_selector = array('group' => "Group Show{$group_price}");
        $subscribers = $this->Entertainer->getSubscriberFans();
        $subscribersOptions = '<option value="" selected hidden>Choose Fan</option>';
        foreach ($subscribers as $id => $name) {
            $subscribersOptions .= '<option value="' . $id . '">' . $name . '</option>';
        }
        $private_requests = $this->Entertainer->getPrivateRequests();
        $default = null;
        foreach ($private_requests as $fan_id => $name) {
            if (!$default) {
                $default = $fan_id;
            }
            $show_type_selector[$fan_id] = "Private Show for {$name}{$private_price}";
        }
        $requests = '';
        if (sizeof($private_requests)) {
            $request_list = implode(', ', $private_requests);
            $verb = sizeof($private_requests) == 1 ? 'has' : 'have';
            $requests = "<span class=\"requests\">{$request_list} {$verb} requested a private show.</span>";
        }
        $edit = new StaysailForm('start_show_form');
        $edit->setJobAction(__CLASS__, 'start_show')
            ->setPostMethod()
            ->setSubmit('Go To Private Show')
            ->addHTML('<h1>Required Information</h1>')
            ->setDefaults(array('show_type' => $default, 'price' => '5.99'));
        if ($requests) {
            $edit->addHTML("<p>{$requests}</p>");
        }
        $edit->addHTML('<select name="show_type" class="select2-hidden-accessible" id="show_type_container">
                            <option value="0">Private Show</option>
                            <option value="1">Subscribers Show</option>
                            <option value="2">Public Show</option>
                        </select>
                        <input type="hidden" name="goal_status" class="goal_input">
                        <div class="show_type_container">
                        <select name="fan_id" class="select2-hidden-accessible fan-select-checked" required="required">
                             ' . $subscribersOptions . '
                        </select>
                        </div>');
        $writer->draw($edit);
        $writer->addHTML('<br/>
                          <h2 class="addTipMenuH2">Add Tip Menu</h2>
                          <form action="?mode=WebShowModule&job=add_tip" method="POST">
                              <label for="tipName">Name</label>
                              <input id="tipName" type="text" name="name" required>
                              <label for="tipPrice">Price</label>
                              <input id="tipPrice" type="number" name="price" required>
                              <button id="addTip">Add Tip</button>
                          </form>');
        $writer->addHTML('<br/>
                          <h2>Tip Menu</h2>
                          <div class="show-row">');
        $TipMenuIds = $this->_framework->getAllIdsRowsByField('TipMenu',
            'Entertainer_id', ($this->Entertainer->id));
        foreach ($TipMenuIds as $TipMenuId) {
            $TipMenuModel = new TipMenu($TipMenuId[0]);
            $writer->addHTML("<div style='display: flex;'>
                                <p style='margin-right: 20px';>{$TipMenuModel->name} - {$TipMenuModel->price}$</p>
                                <form action='?mode=WebShowModule&job=delete_tip&tip_id={$TipMenuModel->id}' class='delete-tip' method=\"POST\">
                                    <button>Delete Tip</button>
                                </form> 
                               <br>
                              </div>");
        }

        $writer->addHTML('</div>
<br/>
                          <h2 class="createGoalMenuH2">Create Goal</h2>
                          <form action="?mode=WebShowModule&job=create_goal" method="POST">
                              <label for="goalName">Name</label>
                              <input id="goalName" type="text" required value="Private Show" disabled>
                              <input type="hidden" name="name" required value="Private Show">
                              <label for="goalPrice">Price</label>
                              <input id="goalPrice" type="number" name="price" required>
                              <button id="goalTip">Create Goal</button>
                          </form>');

        $writer->addHTML('<br/>
                          <h2>Goal History</h2>
                          <div class="show-row">');
        $GoalHistoryIds = $this->_framework->getAllIdsRowsByField('Goal',
            'Entertainer_id', ($this->Entertainer->id));
        foreach ($GoalHistoryIds as $GoalHistoryId) {
            $GoalHistoryModel = new Goal($GoalHistoryId[0]);
            $writer->addHTML("<div style='display: flex;'>
                                <p style='margin-right: 20px';>{$GoalHistoryModel->name} - {$GoalHistoryModel->price}$</p>
                                <form action='?mode=WebShowModule&job=delete_goal&goal_id={$GoalHistoryModel->id}' method=\"POST\">
                                    
                                    <label>
                                        <input type='checkbox' class='goal_checkbox' checked disabled>show goal bar during the livestream
                                    </label>
                                </form>
                               <br>
                              </div>");
        }
        $writer->addHTML('</div>');
        //<button>Delete Goal</button> on 219

//      if(!$this->Entertainer->id){
//          public $running = parent::Boolean;
//          $this->running = 0;
//
//          $update = array('name' => $channel_name,
//              'Entertainer' => $this->Entertainer->id,
//              'channel_price' => $channel_price,
//          );
//
//          $WebShow = new WebShow();
//          $WebShow->update($update);
//          $WebShow->save();
//      }

        
        return $writer->getHTML();
    }


    public function getMetadataFields()
    {
        $data = Library::getMetadataTypes();
        $metadata[] = $this->Entertainer->getPrefillMetadata();
        $html = "<div id=\"picture_metadata\">";
        for ($i = 0; $i <= 8; $i++) {
            if (isset($metadata[$i])) {
                $person = $metadata[$i];
            } else {
                $person = array();
            }
            $display = $i <= sizeof($person) ? 'block' : 'none';

            $html .= "<div id=\"person{$i}\" style=\"float: left; clear: both; display:{$display}\">";
            if ($i == 0) {
                $html .= "<h2>Your Information</h2>\n";
            } else {
                $html .= "<h2>Next Person</h2>\n";
                $html .= "<p><a href=\"#\" onclick=\"removePerson({$i});return false;\">Remove</a></p>";
            }

            foreach ($data as $type) {
                $html .= '<div class="label">' . ucwords(str_replace('_', ' ', $type)) . '</div>';
                $value = isset($person[$type]) ? $person[$type] : '';
                $html .= "<div class=\"control\"><input type=\"text\" name=\"{$type}[]\" value=\"{$value}\" /></div>";
            }
            $html .= "</div>\n";
        }
        $html .= "</div>";
        $html .= "<script>person_number=" . sizeof($person) . "</script>";

        return $html;
    }

    public function startShow()
    {
        if ($this->Member->getRole() != Member::ROLE_ENTERTAINER) {
            return '';
        }
        $memberId = $this->Member->id;
        $name = $this->Member->name;
        $EntertainerId = $this->Entertainer->id;
        if (!$name) {
            $name = $this->Entertainer->stage_name;
        }
        if (StaysailIO::post('show_type') == 1 || StaysailIO::post('show_type') == 0 || StaysailIO::post('show_type') == 2) {
            if (StaysailIO::post('show_type') == 2) {
                $webShowSubscribers = $this->_framework->getAllIdsRowsByConditions('WebShowDyte',
                    [
                        'Member_id' => $this->Member->id,
                        'show_type' => 1
                    ]
                );
                foreach ($webShowSubscribers as $webShowSubscriber) {
                    $w = new x($webShowSubscriber[0]);
                    $w->delete_Job();
                }
            }
            $webShowPrivate = $this->_framework->getAllIdsRowsByConditions('WebShowDyte',
                [
                    'Member_id' => $this->Member->id,
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
        $meetingData = $WebShow->createMeeting($this->Entertainer->name);
        $meetingId = $meetingData['id'];
        if (!$meetingData) {
            return "<h1>Sorry...</h1><p>The system has failed to create the show at this time.</p>";
        }
        $webShowDyte->Member_id = $this->Member->id;
        $webShowDyte->Entertainer_id = $this->Entertainer->id;
        $webShowDyte->Fan_id = StaysailIO::post('show_type') == 0 ? StaysailIO::post('fan_id') : null;
        $webShowDyte->title = $meetingData['title'];
        $webShowDyte->meeting_id = $meetingId;
        $webShowDyte->show_type = StaysailIO::post('show_type');
        if (StaysailIO::post('show_type') < 3) {
            $webShowDyte->public_live_status = 1;
            $webShowDyte->update_at = strtotime("now");
        }
        $webShowDyte->save();
        $participantData = $WebShow->createParticipant($webShowDyte, $this->Member);

        $DyteParticipantModel = new DyteParticipant();
        $DyteParticipantModel->Member_id = $this->Member->id;
        $DyteParticipantModel->name = $participantData['name'];
        $DyteParticipantModel->picture = $participantData['picture'];
        $DyteParticipantModel->participant_id = $this->Member->getAccountOfType('Fan') ? $this->Member->getAccountOfType('Fan')->id : $this->Member->id;
        $DyteParticipantModel->preset_name = $this->Member->getAccountOfType('Fan') ? 'livestream_host' : 'livestream_participant';
        $DyteParticipantModel->created_at = $participantData['created_at'];
        $DyteParticipantModel->updated_at = $participantData['updated_at'];
        $DyteParticipantModel->token = $participantData['token'];
        $DyteParticipantModel->WebShowDyte_id = $webShowDyte->id;
        $DyteParticipantModel->save();

        if (StaysailIO::post('goal_status')){
            $goalStatus = StaysailIO::post('goal_status');
            $filters = array(
                new Filter(Filter::Where,"Member_id = $memberId"),
            );
            $goal = $this->_framework->getSingle('Goal', $filters);
            $goal->status = $goalStatus;
            $goal->save();

        }

        $token = $DyteParticipantModel->token;
        $domain = $_SERVER['SERVER_NAME'];
//        $link = "<script>window.open(`/liveStream.php?authToken=$token&Entertainer_id=$EntertainerId&meeting_id=$meetingId&goal_status=$goalStatus`)</script>";
        $link = "<script>window.open(`/liveStream.php?authToken=$token&Entertainer_id=$EntertainerId&meeting_id=$meetingId`)</script>";
      //  $link = "/liveStream.php?authToken=$token&Entertainer_id=$EntertainerId&meeting_id=$meetingId";

        $fans = $this->Entertainer->getSubscribers();
        if ($fans) {
            foreach ($fans as $Fan_Subscription) {
                $Fan = $Fan_Subscription->Fan;
                $fanMember = new Member($Fan->Member_id);
                $message = "{$name} is now LIVE! Join the STREAM $domain";
                $subject = "LIVE!";
                $MailSend = new MailSend($fanMember);
                $MailSend->send($fanMember->email, $subject, $message, 0, false);
            }
        }

//        if ($domain == 'yourfanslive.com' && class_exists('SMSSender')) {
//            $fans = $this->Entertainer->getSubscribers();
//            if ($fans) {
//                foreach ($fans as $Fan_Subscription) {
//                    $Fan = $Fan_Subscription->Fan;
//                    if ($Fan->Member && $Fan->Member->phone && $Fan->Member->sms_optout == 0) {
//                        $SMSSender = new SMSSender($Fan->Member, 'LocalCityScene');
//                        $message = "{$name} is now LIVE! Join the STREAM $domain";
//                        $SMSSender->send($message);
//                    }
//                }
//            }
//        }
//
//        $WebShow = new WebShow();
//
//       $start_time = $this->start_time = StaysailIO::now();
//       $status = $this->running = 1;
//
////        $update = array('name' => $channel_name,
////            'Entertainer' => $this->Entertainer->id,
////            'channel_price' => $channel_price,
////        );
//
//        $name = $this->Member->name;
//        $EntertainerId = $this->Entertainer->id;
//        if (!$name) {
//            $name = $this->Entertainer->stage_name;
//        }
//
//
//
//
////        $WebShow->update($update);
//        $WebShow->save();
        

        echo $link;
//        $prevUrl = $_SERVER['HTTP_REFERER'];
//        $contents = explode('=', $prevUrl);
//        if ($contents[2] == 'prestart_show') {
//            $prev = "<script>window.location.href = '/?mode=WebShowModule&job=prestart_show'</script>";
//        }
//        echo $prev;
    }

    public function getFanCamHTML()
    {
        $WebShow = null;
        if ($this->Member->getRole() == Member::ROLE_FAN) {
            $Fan = $this->Member->getAccountOfType('Fan');
            $WebShow = $this->Entertainer->privateShowInProgress($Fan);
        }
        if ($this->Member->getRole() == Member::ROLE_ENTERTAINER) {
            $WebShow = $this->_framework->getSingle('WebShow',
                new Filter(Filter::Match, array('Entertainer_id' => $this->Entertainer->id, 'running' => 1)));
        }

        if ($WebShow and $WebShow->isPrivate()) {
            $view = new WebShowView($this->Member, $WebShow);
            return $view->getFanCamHTML();
        }
        return '';
    }

    public function resumeShow()
    {
        if ($this->Member->getRole() != Member::ROLE_ENTERTAINER) {
            return '';
        }
        $WebShow = $this->Entertainer->showInProgress();
        if ($WebShow) {
            $view = new WebShowView($this->Member, $WebShow);
            $this->WebShow = $WebShow;
            return $view->getHTML();
        } else {
            $writer = new StaysailWriter();
            $writer->h1('Sorry...');
            $writer->p("You do not currently have a show in progress.");
            return $writer->getHTML();
        }
    }

    public function addFanParticipant()
    {
        $EntertainerId = $this->Entertainer->id;
        $MemberId = $this->Member->id;
        $meetingId = StaysailIO::post('meeting_id');
        $WebShow = new WebCamDyte();
        $webShowDyte = new WebShowDyte($meetingId);
        $participantData = $WebShow->createParticipant($webShowDyte, $this->Member);
        $DyteParticipantModel = new DyteParticipant();
        $DyteParticipantModel->Member_id = $this->Member->id;
        $DyteParticipantModel->name = $participantData['name'];
        $DyteParticipantModel->picture = $participantData['picture'];
        $DyteParticipantModel->participant_id = $this->Member->getAccountOfType('Fan') ? $this->Member->getAccountOfType('Fan')->id : $this->Member->id;
        $DyteParticipantModel->preset_name = $this->Member->getAccountOfType('Fan') ? 'livestream_host' : 'livestream_participant';
        $DyteParticipantModel->created_at = $participantData['created_at'];
        $DyteParticipantModel->updated_at = $participantData['updated_at'];
        $DyteParticipantModel->token = $participantData['token'];
        $DyteParticipantModel->WebShowDyte_id = $webShowDyte->id;
        $DyteParticipantModel->save();
        $token = $DyteParticipantModel->token;
        $link = "<script>window.open(`/sliveStream.php?authToken=$token&Entertainer_id=$EntertainerId&Member_id=$MemberId`)</script>";

        echo $link;

        $prevUrl = $_SERVER['HTTP_REFERER'];
        $contents = explode('=', $prevUrl);
        if ($contents[2] == 'public_live'){
            $prev = "<script>window.location.href = '/?mode=FanHome&job=public_live'</script>";
        }
        elseif ($contents[2] == 'purchase_show' || $contents[2] == 'start_show' ) {
            $prev = "<script>window.location.href = '/?mode=WebShowModule&job=purchase_show'</script>";
        }
        echo $prev;
    }

    public function purchaseShow()
    {
        if ($this->Member->getRole() != Member::ROLE_FAN) {
            return '';
        }
        $Fan = $this->Member->getAccountOfType('Fan');
        //$WebShow = $this->Entertainer->showInProgress();
        //var_dump($WebShow);
        $LastWebShowDyte = $this->Entertainer->getLastWebShowDyte();
        $LastPrivateWebShowDyte = $this->Entertainer->getLastFanWebShowDyte($Fan->id);
        $LastPublicShowDyte = $this->Entertainer->getLastPublicShow();






//        if ($WebShow) {
//            header("Location:/?mode=Purchase&job=purchase&type=WebShow&id={$WebShow->id}");
//            exit;
//        }
        $writer = new StaysailForm();
        if ($LastWebShowDyte || $LastPrivateWebShowDyte || $LastPublicShowDyte) {
            if ($LastPrivateWebShowDyte) {
                $meetingId = $LastPrivateWebShowDyte->id;
                $buttonText = 'Go To Private Show';
            } else if ($LastWebShowDyte) {
                $meetingId = $LastWebShowDyte->id;
                $buttonText = 'Go To Subscribers Show';
            }
            else if($LastPublicShowDyte){
                $meetingId = $LastPublicShowDyte->id;
                $buttonText = 'Go To Public Show';
            }

            $writer->setJobAction(__CLASS__, 'add_fan_participant')
                ->setPostMethod()
                ->addHTML('<input type="hidden" name="meeting_id" value="' . $meetingId . '">')
                ->setSubmit($buttonText);
        }

        return $writer->getHTML();
    }

    public function joinShow()
    {
        if ($this->Member->getRole() != Member::ROLE_FAN) {
            return '';
        }
        if ($this->Entertainer) {
            $WebShow = $this->Entertainer->showInProgress();
            if ($WebShow) {
                $view = new WebShowView($this->Member, $WebShow);
                return $view->getHTML();
            }
            $writer = new StaysailWriter();
            $writer->h1('Sorry...');
            $writer->p("This Entertainer does not have a show in progress.");
            return $writer->getHTML();
        }
    }

    public function endShow()
    {
        if ($this->Member->getRole() != Member::ROLE_ENTERTAINER) {
            return '';
        }
        $writer = new StaysailWriter();
        if ($this->Entertainer) {
            $WebShow = $this->Entertainer->showInProgress();
            if ($WebShow) {
                $WebShow->end();
            }
            $writer->h1('The show is over');
            $writer->p('You may start a new one by clicking ' . StaysailWriter::makeJobLink('here.', __CLASS__, 'prestart_show'));

            // Put a notification in the chat
            $MeetingPost = new MeetingPost();
            $MeetingPost->update(array('Entertainer_id' => $this->Entertainer->id,
                'Member_id' => $this->Member->id,
                'content' => "<div class=\"entertainer\"><strong>{$this->Entertainer->name}</strong> has ended a live web show!</div>",
            ));
            $MeetingPost->save();
        }

        return $writer->getHTML();
    }

    public function addTip()
    {
        $name = StaysailIO::post('name');
        $price = StaysailIO::post('price');
        $tip = new TipMenu();
        $tip->Member_id = $this->Member->id;
        $tip->Entertainer_id = $this->Entertainer->id;
        $tip->name = $name;
        $tip->price = $price;
        $tip->save();

        header("Location:/?mode=WebShowModule&job=prestart_show");
        exit;
    }

    public function deleteTip()
    {
        $tipId = StaysailIO::get('tip_id');
        $tip = new TipMenu($tipId);
        if ($tip && $tip->Entertainer_id == $this->Entertainer->id) {
            $tip->delete_Job();
        }

        header("Location:/?mode=WebShowModule&job=prestart_show");
        exit;
    }

    public function createGoal()
    {
        $name = StaysailIO::post('name');
        $price = StaysailIO::post('price');
        $memberId = $this->Member->id;
        $entertainerId = $this->Entertainer->id;
        $filters = array(
            new Filter(Filter::Where,"Member_id = $memberId and Entertainer_id = $entertainerId"),
//            new Filter(Filter::Where,"Entertainer_id = $entertainerId"),
        );
        $oldGoal = $this->_framework->getSingle('Goal', $filters);
        if ($oldGoal){
            $oldGoal->delete_job();
        }
        $goal = new Goal();
        $goal->Member_id = $this->Member->id;
        $goal->Entertainer_id = $this->Entertainer->id;
        $goal->name = $name;
        $goal->price = $price;
        $goal->current_count = 1;
        $goal->status = 0;
        $goal->save();

        header("Location:/?mode=WebShowModule&job=prestart_show");
        exit;
    }

    public function goalStatus()
    {
        die('001');
    }

    public function deleteGoal()
    {
        $goalId = StaysailIO::get('goal_id');
        $goal = new Goal($goalId);
        if ($goal && $goal->Entertainer_id == $this->Entertainer->id) {
            $goal->delete_Job();
        }

        header("Location:/?mode=WebShowModule&job=prestart_show");
        exit;
    }
}

