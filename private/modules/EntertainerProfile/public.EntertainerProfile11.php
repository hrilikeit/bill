<?php

require '../private/views/PostView.php';
require '../private/views/SummaryView.php';
require '../private/views/EventsView.php';
require '../private/views/LiveChatView.php';
require '../private/views/SubscriptionListView.php';
require '../private/views/TwitterView.php';
require '../private/views/FriendsView.php';
require '../private/views/SeemsLikeView.php';
require '../private/views/MostPopularView.php';


class EntertainerProfile extends StaysailPublic
{
    protected $page, $settings, $categories;
    protected $framework;

    public $Member;
    public $Entertainer;

    public function __construct($dbc = '')
    {
        $this->valid = false;
        $this->framework = StaysailIO::engage();
        $member_id = StaysailIO::session('Member.id');
        $this->Member = new Member($member_id);
        if (StaysailIO::session('Entertainer.id')) {
            $this->Entertainer = new Entertainer(StaysailIO::session('Entertainer.id'));
        } else {
            $this->Entertainer = $this->Member->getAccountOfType('Entertainer');
        }

        if (Member_Docs::hasMemberDocs($this->framework, $this->Member->id) && $this->Entertainer->checkContract() == true){
            if ($this->Member->address){
                $this->Member->requiredEmailVerify();
            }
        }
    }

    public function getHTML()
    {

        if (false && $this->Member->getRole() == Member::ROLE_FAN) {
            $Fan = $this->Member->getAccountOfType('Fan');
            if (!$Fan->isSubscribedTo($this->Entertainer)) {
                $clubs = $this->Entertainer->getClubs();
                if (sizeof($clubs)) {
                    header("Location:/?mode=ClubProfile&club_id=" . $clubs[0]->id);
                } else {
                    header("Location:?mode=FanHome");
                }
                exit;
            }

            if ($Fan->isBannedFrom($this->Entertainer)) {
                header("Location:?mode=FanHome&da={$this->Entertainer->id}");
                exit;
            }
        }
        $job = StaysailIO::get('job');
        $id = StaysailIO::get('id');
        $left_side_override = '';
        $reminder = false; // Do we need to remind the Entertainer to sign the agreement again?

        $map = Maps::getEntertainerProfileMap();
        $whole_screen_map = false;
//        $this->Member->checkStanding(); // Make sure the member is paid, if it's a Fan


        switch ($job) {
            case 'post':
                $text = StaysailIO::post('name');
                $postPhoto = !empty($_FILES['image']['name']) ? $_FILES['image'] : [];
                $postVideo = !empty($_FILES['video']['name']) ? $_FILES['video'] : [];

                if (isset($postPhoto['size'][0]) && $postPhoto['size'][0] != 0){
                    Post::uploadImage($this->Member, $this->Entertainer);
                } elseif (isset($postVideo['size']) && $postVideo['size'] != 0){
                   Post::uploadVideo($this->Member);
//                }elseif (!empty($text) && empty($postPhoto['name']) && empty($postVideo['name'])){
                }elseif (!empty($text)){
                    $Post = new Post($id);
                    $Post->postReply($this->Member, '<p class="post_text">'.$text.'</p>', $this->Entertainer);
                }
                header('Location: /?mode=EntertainerProfile');
                exit;

            case 'unsubscribe':
                $fanId = $this->Member->getAccountOfType('Fan')->id;
                $entertainerId = StaysailIO::post('entertainer_id');
                $this->unsubscribe($fanId, $entertainerId);

                header('Location: /?mode=EntertainerProfile');
                exit;
                break;

            case 'delete_acc':
                $id = $this->Member->id;
                if (!$id) {
                    continue;
                }
                $sql = "INSERT INTO `deletion_requests`
					(member_id)
					VALUES ({$id})";
                $this->framework->query($sql, StaysailIO::DISCARD_RESULT);



//
//                    $id = $this->Member->id;
//                    if (!$id) {
//                        continue;
//                    }
//                    $photos = $this->Member->getPhotos();
//                    $videos = $this->Member->getVideos();
//                    foreach ($photos as $photo){
//                        $photoName = DATAROOT.'/private/library/'.$photo->image;
//                        if (file_exists($photoName)){
//                            unlink($photoName);
//                        }
//                    }
//                    foreach ($videos as $video){
//                        $videoName = DATAROOT.'/private/library/'.$video->image;
//                        if (file_exists($videoName)){
//                            unlink($videoName);
//                        }
//                    }
//                    $filter = new Filter(Filter::Match, array('Member_id' => $id));
//                    $libraries = $this->framework->getSubset('Library', $filter);
//                    foreach ($libraries as $library){
//                        $lib = new Library($library->id);
//                        $lib->delete_Job();
//                    }
//
//                    $filter = new Filter(Filter::Match, array('Member_id' => $id));
//                    $posts = $this->framework->getSubset('Post', $filter);
//                    foreach ($posts as $post){
//                        $pos = new Post($post->id);
//                        $pos->delete_Job();
//                    }
//
//                    $Fan = $this->Member->getAccountOfType('Fan');
//                    $Entertainer = $this->Member->getAccountOfType('Entertainer');
//                    if ($Fan){
//                        $avatar = DATAROOT . "/private/avatars/avatar{$this->Member->id}";
//                        foreach(['png','jpg', 'jpeg'] as $format) {
//                            if (file_exists($avatar . '.' . $format)) {
//                                unlink($avatar . '.' . $format);
//                            };
//                        }
//                        $displayPhoto = DATAROOT . "/private/avatars/displayPhoto{$this->Member->id}";
//                        foreach(['png','jpg', 'jpeg'] as $format) {
//                            if (file_exists($displayPhoto . '.' . $format)) {
//                                unlink($displayPhoto . '.' . $format);
//                            };
//                        }
//                        $Fan->delete_Job();
//                    }elseif($Entertainer){
//                        $entertainerAvatar = DATAROOT . "/private/avatars/entertainerAvatar{$this->Member->id}";
//                        foreach(['png','jpg', 'jpeg'] as $format) {
//                            if (file_exists($entertainerAvatar . '.' . $format)) {
//                                unlink($entertainerAvatar . '.' . $format);
//                            };
//                        }
//                        $avatar = DATAROOT . "/private/avatars/avatar{$this->Member->id}";
//                        foreach(['png','jpg', 'jpeg'] as $format) {
//                            if (file_exists($avatar . '.' . $format)) {
//                                unlink($avatar . '.' . $format);
//                            };
//                        }
//                        $entertainerDisplayPhoto = DATAROOT . "/private/avatars/entertainerDisplayPhoto{$this->Member->id}";
//                        foreach(['png','jpg', 'jpeg'] as $format) {
//                            if (file_exists($entertainerDisplayPhoto . '.' . $format)) {
//                                unlink($entertainerDisplayPhoto . '.' . $format);
//                            };
//                        }
//
//                        $Entertainer->delete_Job();
//                    }

                    $Fan = $this->Member->getAccountOfType('Fan');
                    $Entertainer = $this->Member->getAccountOfType('Entertainer');
                    if ($Fan){
                        header("Location:/?mode=FanProfile&job=update_bio");
                    }elseif($Entertainer){
                        header("Location:/?mode=EntertainerProfile&job=update_bio");
                    }

//                    header("Location:/?mode=Login&amp;job=signout");
//                    $this->Member->delete_Job();
                exit;
                break;

            case 'reviews':
                $left_side_override = $this->getReviews();
                break;

            case 'events':
                if (StaysailIO::get('yy')) {
                    $left_side_override = $this->selectEventOnDate();
                } else {
                    $left_side_override = $this->editEvent($id);
                }
                break;

            case 'post_show':
                $this->postShow($id);
                header('Location: /?mode=EntertainerProfile');
                exit;
//                break;

            case 'update_bio':
                $left_side_override = $this->updateBioForm();
                break;

            case 'upload_avatar':
                $this->Member->uploadEntertainerAvatar();
                $this->updateBioForm();

                header('Location: /?mode=EntertainerProfile&job=update_bio');
                exit;
                break;

            case 'upload_display_photo':
                $backUrl = $_SERVER['HTTP_REFERER'];
                $back = explode("?", $backUrl);
                $this->Member->uploadEntertainerDisplayPhoto();
                $left = $this->updateBioForm();
                if ($back[1] == 'mode=EntertainerProfile&job=update_bio'){
                    header('Location: /?mode=EntertainerProfile&job=update_bio');
                    exit;
                }
                else{
                    header('Location: /?mode=EntertainerProfile');
                    exit;
                }
                break;

            case 'crop_avatar':
                $left_side_override = $this->cropAvatar();
                $whole_screen_map = true;
                break;

            case 'set_avatar':
                $this->setAvatar();
                $left = $this->updateBioForm();
                header('Location: /?mode=EntertainerProfile');
                exit;

            case 'post_bio':
                $this->postBio();
                if ($this->Entertainer->requiredFields() == true){
                    $this->Member->requiredEmailVerify();
                }
                header('Location: /?mode=EntertainerProfile&job=update_bio');
                exit;

            case 'post_sms':
                $this->postSMS();
                break;

            case 'connections':
                $left_side_override = $this->connectionsReport();
                break;

            case 'fans':
                $left_side_override = $this->fanReport();
                break;

            case 'delete_post':
                $this->deletePost($id);
                $entertainerId = StaysailIO::post('entertainer_id');
                header('Location: /?mode=EntertainerProfile&entertainer_id='.$entertainerId);
                exit;
                break;

            case 'add_club':
                $left_side_override = $this->addClub();
                break;

            case 'assign_new_club':
                $left_side_override = $this->assignClub();
                $left_side_override .= $this->addClub();
                break;

            case 'notify':
                // Notify fans that she is online
                $this->Entertainer->notifyFansOfOnline();
                StaysailIO::setSession('notify_fans', 1);
//                break;
                header('Location: /?mode=EntertainerProfile');
                exit;

            case 'esign_agreement':
                $this->signAgreement();
                break;

            case 'ack_instructions':
                // After having read the instructions for submitting DL and Social Security card,
                // set the Active Time and email the instructions.
                $this->acknowledgeInstructions();
                header('Location: /?mode=EntertainerProfile&job=update_bio');
//                break;
                exit;

            case 'upload_collaborator_docs':
                // After having read the instructions for submitting DL and Social Security card,
                // set the Active Time and email the instructions.
                $this->uploadCollaboratorDocs();
                header('Location: /?mode=EntertainerProfile&job=update_bio');
                exit;

            case 'email_verify':
                $left_side_override = $this->emailVerify();
                break;
        }
        // Entertainer checks.  These screens are for newly signed-up Entertainers, and the
        // checks are done in the order that the sceens should be completed after sign-up.
        // After the initial Member information, the Entertainer needs to provide see:
        // (1) The Profile Form (SSN and stage name)
        // (2) The Entertainer Agreement (contract signing)
        // (3) Instructions for sending social security card and drivers license
        // (4) Selection of SMS preferences
        $addShow = '';
        if ($this->Member->getRole() == Member::ROLE_ENTERTAINER) {
//            var_dump($this->Member->checkActive());
//
//var_dump($this->Member->checkActive());
//var_dump($this->framework->getRowByField('Member_Docs', 'Member_id', $this->Member->id));
//var_dump($_SESSION);
            $addShow = '<a href="?mode=EntertainerProfile&job=events" class="button">Add a Show</a>';
            if (!$this->Entertainer->stage_name or !$this->Entertainer->ssn) {
                $form = $this->Entertainer->getProfileForm('Login', 'save_profile');
                $left_side_override = $form->getHTML();
            } elseif ($job !== 'update_bio' && (!$this->Entertainer->birth_date || !$this->Member->address )) {
                header("Location:?mode=EntertainerProfile&job=update_bio");
                exit;
            } elseif (!$this->Member->checkActive() && !$memberDocs = $this->framework->getRowByField('Member_Docs', 'Member_id', $this->Member->id)) {
                $whole_screen_map = true;
                $left_side_override = $this->getDocumentScreen();
            } elseif (!$this->Entertainer->checkContract()) {
                $whole_screen_map = true;
                $left_side_override = $this->getContractScreen($reminder);
            }
// elseif (!$this->Member->validatePhoneNumber() or !$this->Member->cell_provider) {
//            } elseif (!$this->Member->validatePhoneNumber()) {
//                $left_side_override = $this->updateSMSForm();
//            }
            elseif ($job != 'update_bio' &&
                !$this->Member->validateAddress()) {
                $left_side_override = $this->updateBioForm();
            } elseif ($job != 'update_bio' &&  !$this->Entertainer->validateBirthdate()) {
                $left_side_override = $this->updateBioForm();
            }
            if ($job == 'document_screen') {
                $whole_screen_map = true;
                $left_side_override = $this->getDocumentScreen();
            }
        }

        // If the Member has logged in, and is paid up, see if there's a redirect
        if (StaysailIO::session('post_login_redirect')) {
            $post_login_redirect = StaysailIO::session('post_login_redirect');
            StaysailIO::setSession('post_login_redirect', null);
            StaysailIO::setSession('MemberIsCurrent', true);
            header("Location:{$post_login_redirect}");
            exit;
        }

        $header = new HeaderView();
        $footer = new FooterView();
        $action = new ActionsView($this->Member);
//        $banner = new BannerAdsView($this->Member);
        //$subscription = new SubscriptionListView($this->Member);
//        $twitter = new TwitterView();

        $containers = array(new StaysailContainer('H', 'header', $header->getHTML()),
            new StaysailContainer('F', 'footer', $footer->getHTML()),
            new StaysailContainer('A', 'action', $action->getHTML()),
//            new StaysailContainer('B', 'banner', $twitter->getHTML() . /*$subscription->getClubList() .*/ $banner->getHTML()),
        );

        $posts = new PostView($this->Member);
        $summary = new SummaryView($this->Member);
        $events = new EventsView($this->Member);
        $chat = new LiveChatView($this->Member);
        $friends = new FriendsView($this->Member);
        $SeemsLikes = new SeemsLikeView($this->Member);

        $notify_fans = '';
        if (!StaysailIO::session('notify_fans')) {
            $notify_fans = "<p></p>\n";
        }
        $disabled = $isSubscribed = '';
        if ($this->Member->id == $this->Entertainer->Member_id){
            $disabled = 'disabled';
        }
        $joinNow = '';
        if($this->Member->getRole() == Member::ROLE_FAN) {
            $Fan = $this->Member->getAccountOfType('Fan');
            $isSubscribed = $Fan->isSubscribedToEntertainer($this->Entertainer->id);

            $LastWebShowDyte = $this->Entertainer->getLastWebShowDyte();
            $LastPrivateWebShowDyte = $this->Entertainer->getLastFanWebShowDyte($Fan->id);
            $LastPublicShowDyte = $this->Entertainer->getLastPublicShow();

           // if ($LastWebShowDyte || $LastPrivateWebShowDyte || $LastPublicShowDyte) {
            if ($this->Entertainer->showInProgress()) {

            $joinNow = '<a href="?mode=WebShowModule&job=purchase_show" class="blue_unsubscription" style="
    background: #ba2978;
    margin-left: 2px;
        text-decoration: none;
">Join</a>';
            }
        }
        $left = $left_side_override ? $left_side_override : ($notify_fans . $summary->getPhotoLandscapeHTML() . $summary->getPhotoSummaryHTML() . $posts->getHTML());
        $chat_content = $left_side_override ? '' : $chat->getHTML();
        $right = $summary->getHTML() . $events->getHTML() . $chat_content;
        $hasDocs = $this->Member->getRole() != Member::ROLE_ENTERTAINER || $this->Entertainer->contract_signature;

        if ($this->Member->getRole() == Member::ROLE_FAN || $this->Member->getRole() == Member::ROLE_ENTERTAINER) {
            if (($hasDocs && $job == '' )|| $job == 'esign_agreement') {
                if ( $this->Member->getRole() == Member::ROLE_ENTERTAINER) {
                    $this->Entertainer->requiredFields();
                }

                $subscriptionButton = $isSubscribed ?
//                    '<button class="blue_subscription"  style="position: relative;top: -20px;">Subscribed $' . $this->Entertainer->subscription_pricing . '</button> :
                    '<button class="blue_unsubscription" id="unsubscribepopup">Unsubscribe</button>' :
                    '<button class="blue_subscription" id="mpopupLink" ' . $disabled . '>Subscription for $' . $this->Entertainer->subscription_pricing . '</button>';
                $html = '<div class="main-div">
                            <div class="profile-top-section">
                                <div class="display-photo">' . $summary->getPhotoLandscapeHTML() . '</div>
                                <div class="avatar-photo">' . $summary->getPhotoSummaryHTML() . '</div>
                                <div class="summary-div">
                                    <div class="summary" style="display: flex; align-items: center">
                                        ' . $summary->getHTML() .
                                                                   $subscriptionButton . $joinNow .' </div>
                                    <div class="notify-fans">' . $notify_fans . '</div>
                                </div>
                            </div>
                            <div class="profile-bottom-section">
                                <div class="left-section">
                                ' . $posts->getHTML() . '
                                </div>
                                <div class="right-section">
                                <!-- . $events->getHTML() . $chat_content . -->
                                '  . $chat_content . $SeemsLikes->getHTML() . '
                                </div>
                            </div>
                         </div>
                            <div id="myModalEvent" class="modalEvent">
                                <div class="modal-content-event">
                                    <div class="modal-header-event">
                                        <h2>Event</h2>
                                    </div>
                                    <span class="close-event">&times;</span>
                                    <div class="modal_box">
                                        ' . $events->getHTML() . '
                                    </div>
                                    ' . $addShow . '
                                </div>
                            </div>

                            <!-- Modal popup box -->
                            <div id="unsubscribepopupBox" class="mpopup">
                             <!-- Modal content -->
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h2>unSubscription</h2>
                                    </div>
                                    <div class="modal-body">
                                    <p>are you sure?</p>
                                    <form action="?mode=EntertainerProfile&job=unsubscribe" method="post">
            
                                        <input type="hidden" name="entertainer_id" value='.$this->Entertainer->id.'/>
            
                                        <button type="submit" class="btn">Yes</button>
                                        <button type="button" class="close_unsubscribe">close</button>
                                    </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal popup box -->
                            <div id="mpopupBox" class="mpopup">
                                <!-- Modal content -->
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h2>Subscription</h2>
                                    </div>
                                    <div class="modal-body">
                                    ';
                                            $writer = new StaysailWriter();
                                            $verify_form = new StaysailForm();
                                            $verify_form->setSubmit("Subscribe to  {$this->Entertainer->name}")
                                                ->setPostMethod()
                                                ->setJobAction('Purchase', 'verify')
                                                ->setDefaults(array('type' => 'Entertainer', 'id' => $this->Entertainer->id, 'payment_method_id' => StaysailIO::session('Payment_Method.id')))
                                                ->addField(StaysailForm::Hidden, '', 'type')
                                                ->addField(StaysailForm::Hidden, '', 'id');

                                            $payment_methods = $this->Member->getPaymentMethods();
                                            if (true || sizeof($payment_methods)) {
                                                $options = array();
                                                foreach ($payment_methods as $Payment_Method) {
                                                    $options[$Payment_Method->id] = $Payment_Method->name;
                                                }
                                                $verify_form->addField(StaysailForm::Select, 'Pay $' . $this->Entertainer->subscription_pricing . ' With', 'payment_method_id', '', $options);
                                            } else {
                                                header("Location:?mode=FanHome&job=new_payment_method");
                                                exit;
                                            }
                                            $verify_form->addHTML(StaysailWriter::makeJobLink('Add Payment Method', 'FanHome', 'new_payment_method', '', 'spaced button'));

                                            $writer
                                                ->start('purchase_options')
                                                ->draw($verify_form)
                                                ->end('purchase_options');
                                            $html .= $writer->getHTML() .
                                                '
                                        <div class="subscription_modal_container">
            
                                        </div>
                                    </div>
                                </div>
                            </div>';

                $containers[] = new StaysailContainer('C', 'top-section', $html);
                if ($whole_screen_map) {
                    $map = Maps::getContractMap();
                }

            } else {
                $containers[] = new StaysailContainer('L', 'posts', $left);
                if ($whole_screen_map) {
                    $map = Maps::getContractMap();
                } else {
                    $map = Maps::getEntertainerProfileUpdateMap();
//                    $containers[] = new StaysailContainer('R', 'schedule', $right);
                }
            }
        }elseif($this->Member->getRole() == Member::ROLE_ENTERTAINER ){
			$containers[] = new StaysailContainer('L', 'posts', $left);
			if ($whole_screen_map) {
				$map = Maps::getContractMap();
			}else{
				$map = Maps::getEntertainerProfileUpdateMap();
				$containers[] = new StaysailContainer('R', 'schedule', $right);
			}
		}

        $layout = new StaysailLayout($map, $containers);

        return $layout->getHTML();
    }

    private function getReviews()
    {
        $writer = new StaysailWriter();

        $writer->h1("Reviews");
        $reviews = $this->Entertainer->getReviews();
        $alt = 'alt_row';

        if ($this->Member->getRole() == Member::ROLE_FAN) {
            $button = "<a href=\"?mode=FanHome&job=review&type=Entertainer&id={$this->Entertainer->id}\" class=\"button\">Write a Reveiw</a>";
            if (!sizeof($reviews)) {
                $writer->p("No reviews have been written about this entertainer.  Be the first to write one!");
            }
            $writer->p($button);
        } else {
            if (!sizeof($reviews)) {
                $writer->p("You do not have any reviews yet.");
            }
        }

        foreach ($reviews as $Review) {
            $alt = $alt ? '' : 'alt_row';
            $writer->start("review {$alt}")
                ->draw($Review)
                ->end();
        }
        return $writer->getHTML();
    }

    private function editEvent($Show_Schedule_id)
    {
        $writer = new StaysailWriter();
        $Show_Schedule = new Show_Schedule($Show_Schedule_id);
        if ($Show_Schedule_id and !$Show_Schedule->belongsTo($this->Member)) {
            $writer->h1('Sorry...')
                ->p("You do not have access to this show schedule");
            return $writer->getHTML();
        }

        $fans = $this->Entertainer->getSubscriberFans();
        $types = array('video' => 'Live Video', 'chat' => 'Live Chat', 'performance' => 'Club Performance');
        $show = new StaysailForm();
        $show->setJobAction('EntertainerProfile', 'post_show', $Show_Schedule_id)
            ->setSubmit($Show_Schedule_id ? 'Update Show' : 'Add Show')
            ->setPostMethod()
            ->setDefaults($Show_Schedule->info())
            ->addHTML($this->datePicker('Show Start Time', 'start_date', $Show_Schedule->start_time))
            ->addHTML($this->timePicker('&nbsp', 'start_time', $Show_Schedule->start_time))
            ->addHTML($this->timePicker('Show End Time', 'end_time', $Show_Schedule->end_time))
            ->addField(StaysailForm::Radio, 'Show Type', 'type', 'require-choice', $types)
            ->addField(StaysailForm::Line, 'Maximum Participants', 'max_viewers')
            ->addField(StaysailForm::Text, 'Description', 'description');
        if (sizeof($fans)) {
            array_unshift($fans, '--');
            $show->addField(StaysailForm::Select, 'Private Show For', 'Fan', '', $fans);
        }

        $writer->h1($Show_Schedule_id ? 'Update a Show' : 'Add a Show')
            ->p("Please enter your show information below.")
            ->draw($show);
        return $writer->getHTML();

    }

    private function selectEventOnDate()
    {
        $writer = new StaysailWriter();
        $writer->h1("Select an Event");
        if ($this->Member->getRole() == Member::ROLE_ENTERTAINER) {
            $writer->p("<a href=\"?mode=EntertainerProfile&job=events\" class=\"button\">Add a Show</a>");
        }

        $year = StaysailIO::get('yy');
        $month = StaysailIO::get('mm');
        $day = StaysailIO::get('dd');

        $shows = $this->Entertainer->getShowsOnDay($year, $month, $day);
        $writer->start('upcoming');
        $writer->start('header');
        $writer->addHTML("<div class=\"header_image\">" . Icon::show(Icon::EVENT, Icon::SIZE_LARGE) . "</div>");
        $writer->addHTML("<div class=\"header_text\"><h2>Events</h2></div>");
        $writer->end('header');
        $writer->start('items');
        foreach ($shows as $Show_Schedule) {
            $link = "<strong><a href=\"?mode=EntertainerProfile&job=events&id={$Show_Schedule->id}\">{$Show_Schedule->getStartEnd()}</a></strong><br/>";
            $writer->p($link . $Show_Schedule->description);
        }
        $writer->end('items');
        $writer->end('upcoming');
        return $writer->getHTML();

    }

    private function postShow($show_id = null)
    {
        if (!$show_id) {
            $Show_Schedule = new Show_Schedule();
            $Show_Schedule->Entertainer = $this->Entertainer;
        } else {
            $Show_Schedule = new Show_Schedule($show_id);
            if (!$Show_Schedule->belongsTo($this->Member)) {
                return false;
            }
        }
        $fields = array('type', 'description', 'max_viewers');
        $Show_Schedule->updateFrom($fields);

        // Set the times
        $start_date = StaysailIO::post('start_date');
        $start_time = StaysailIO::post('start_time');
        $end_time = StaysailIO::post('end_time');
        $start_unix_time = strtotime("{$start_date} {$start_time}");
        $end_unix_time = strtotime("{$start_date} {$end_time}");

        if ($end_unix_time <= $start_unix_time) {
            if ($start_unix_time - $end_unix_time <= 86400) {
                // If the end time is less than a day before the start time, it will mean that the
                // event probably goes past midnight on the previous day.  For example, if the user
                // specifies 1/1/2012 8pm - 2am, the end date should probably be 1/2/2012.  In this
                // case, I'll add one day (86400 seconds) to get the correct date.
                $end_unix_time += 86400;
            } else {
                // Otherwise, just make the start and end time the same
                $end_unix_time = $start_unix_time;
            }
        }
        if ($start_unix_time and $end_unix_time) {
            $Show_Schedule->start_time = date('Y-m-d H:i:s', $start_unix_time);
            $Show_Schedule->end_time = date('Y-m-d H:i:s', $end_unix_time);
        }

        $Show_Schedule->save();
    }

    private function datePicker($label, $field, $default)
    {
        $display_default = date('m/d/Y');
        $default_time = strtotime($default);
        if ($default_time) {
            $display_default = date('m/d/Y', $default_time);
        }
        $html = <<<__END__
    		<div class="label">{$label}</div>
    		<div class="control">
	    	<script>addCalendarField('{$field}', '{$display_default}', 'date_entry required');</script>
	    	</div>
__END__;
        return $html;
    }

    private function timePicker($label, $field, $default)
    {
        $display_default = '7:00pm';
        $default_time = strtotime($default);
        if ($default_time) {
            $display_default = date('g:i a', $default_time);
        }
        $html = <<<__END__
    		<div class="label">{$label}</div>
    		<div class="control"><input type="text" name="{$field}" value="{$display_default}" class="date_entry required" /></div>
__END__;
        return $html;
    }

    public function updateBioForm()
    {

//        var_dump('updateBioForm');
//        die();

        if ($this->Member->getRole() != Member::ROLE_ENTERTAINER) {
            return '';
        }

        $collaboratorSessionSuccess = StaysailIO::session('collaboratorSessionSuccess');
        StaysailIO::setSession('collaboratorSessionSuccess', 0);

        $collaboratorSessionError = StaysailIO::session('collaboratorSessionError');
        StaysailIO::setSession('collaboratorSessionError', 0);

        $displayPhotoCheck = StaysailIO::session('displayPhotoCheck');
        StaysailIO::setSession('displayPhotoCheck', 0);

        $avatarPhotoCheck = StaysailIO::session('avatarPhotoCheck');
        StaysailIO::setSession('avatarPhotoCheck', 0);

        $birthDateAddress = StaysailIO::session('birthDateAddress');
        StaysailIO::setSession('birthDateAddress', 0);


      //  $memberDocs = $this->framework->getRowByField('Member_Docs', 'Member_id', $this->Entertainer->Member_id);
       // $memberDocs = new Member_Docs(isset($memberDocs['id']) ? $memberDocs['id'] : null);
        $domain = $_SERVER['SERVER_NAME'];
        $writer = new StaysailWriter();
        $writer->h1('Edit Your Profile');

        $providers = SMSSender::getProviders();
        foreach ($providers as $key => $value) {
            $providers[$key] = $key;
        }
        $marketing = $this->Entertainer->marketing_Options();
        $plans = $this->Entertainer->subscription_pricing;
        $show_prices = array();
        for ($i = 0; $i < 15; $i++) {
            $price = "{$i}.99";
            $show_prices[$price] = $price;
        }

        $fmt['birth_date_fmt'] = $this->Entertainer->getFormattedBirthdate();
        $birthDateClass = $this->Entertainer->birth_date ? '' : 'green';
        $addressClass = $this->Member->address ? '' : 'yellow';
        $writer = new StaysailWriter();
        $writer->h1('Edit Your Profile');

        // Avatar
        $writer->h2('Upload an Avatar');
        $writer->addHTML($this->Member->getEntertainerAvatarHTML(Member::AVATAR_LITTLE));
        $avatar = new StaysailForm('profile');
        $avatar->setSubmit('Upload')
               ->setPostMethod()
               ->setJobAction('EntertainerProfile', 'upload_avatar')
               ->addField(StaysailForm::File, 'Image File', 'image');
        $writer->draw($avatar);
//        if ($this->Member->hasEntertainerAvatar()) {
//            $writer->addHTML(StaysailWriter::makeJobLink('Crop Avatar', 'EntertainerProfile', 'crop_avatar', '', 'spaced button'));
//            $writer->p('&nbsp;');
//        }
        $writer->addHTML('<p>&nbsp;</p>');
        // Display photo (Landscape)
        $writer->h2('Upload Display photo (Landscape)');
        $writer->addHTML($this->Member->getEntertainerDisplayPhotoHTML(Member::DISPLAY_LARGE));
        $display = new StaysailForm('profile');
        $display->setSubmit('Upload')
            ->setPostMethod()
            ->setJobAction('EntertainerProfile', 'upload_display_photo')
            ->addField(StaysailForm::File, 'Image File', 'displayPhoto');
        $writer->draw($display);
        if($collaboratorSessionSuccess) {
            $writer->addHTML('<h2 class="_hedging" style="color: green">'.$collaboratorSessionSuccess.'</h2>');
        }
        if($collaboratorSessionError) {
            $writer->addHTML('<h2 class="_hedging" style="color: red">'.$collaboratorSessionError.'</h2>');
        }
        if($displayPhotoCheck) {
            $writer->addHTML('<h2 class="_hedging" style="color: red">'.$displayPhotoCheck.'</h2>');
        }
        if($avatarPhotoCheck) {
            $writer->addHTML('<h2 class="_hedging" style="color: red">'.$avatarPhotoCheck.'</h2>');
        }
        if($birthDateAddress) {
            $writer->addHTML('<h2 class="_hedging" style="color: red">'.$birthDateAddress.'</h2>');
        }
        $writer->addHTML('<div>
        <div class="blok edit_blok">
            <h2 class="_hedging">Download and share 2257 Release Form</h2>
            <p class="form-label">
                Copy the link below to download the 2257 and/or email it to
                your content partner, make sure they send you back the
                filled out form and:
            </p>

            <div class="_text_center">
                <a class="_btn_success"
                    href="https://'.$domain.'/t2257.pdf"
                    target="_blank">Download File</a>
            </div>
        </div>
        <div class="blok edit_blok">
            <h2 class="_hedging">2257 and ID Upload</h2>
            <form id="Collaborator_form" enctype="multipart/form-data" method="POST" action="?mode=EntertainerProfile&job=upload_collaborator_docs">
                <div class="_mb-3">
                    <label for="signed_1" class="form-label">Photo ID and 2nd Form of ID</label>
                    <br>
                    <label class="input-file">
                        <span class="_btn-form">+ Add file</span>
                        <input type="file" id="signed_1" name="photo_and_2nd_form_id[]"  multiple required>
                    </label>
                </div>
                <div class="_mb-3">
                    <label for="signed_2" class="form-label">Headshot holding Both ID`s</label>
                    <br>
                    <label class="input-file">
                        <span class="_btn-form">+ Add file</span>
                        <input type="file" id="signed_2" name="headshot" required>
                    </label>
                </div>
                <div class="_mb-3">
                    <label for="signed_3" class="form-label">Completed 2257 Document</label>
                    <br>
                    <label class="input-file">
                        <span class="_btn-form">+ Add file</span>
                        <input type="file" id="signed_3" name="completed_2257" required>
                    </label>
                </div>
                 <div class="_mb-3">
                    <label for="release" class="form-label">Collaborator Stage Name</label>
                    <input type="text" class="form-control" id="release" name="stage_name" maxlength="60" placeholder="Name" required>
                    <div class="_text-end">0/60</div>
                </div>
                <div class="_text_center">
                    <button type="submit" class="_btn_success">UPLOAD</button>
                </div>
            </form>
        </div>
    </div>');
        if ($this->Entertainer->getMemberDocs()->allIdDocsApproved()) {
//            if ($this->Entertainer->getMemberDocs()->signed_form_file_approved) {
                $writer->p("<a id='_btn_success' class=\"button\" aria-disabled='true'>Verified</a>");
//            } else {
//                $writer->p("<span id='_btn_danger'  class=\"button\">Unverified</span>");
//            }
        } else {
            $writer->p("<a id='_btn_danger' href=\"?mode=EntertainerProfile&job=document_screen\" class=\"button\">Unverified</a>");
        }
        $writer->addHTML(
            "<div class='modal' id='myModal_delete'>
                <div style='margin: 250px auto;' class='modal-content'>
                    <div class='modal-header'>
                        <h2>Delete</h2>
                    </div>

                    <div class='modal-body'>
                            <p style='font-size:20px'>Are you sure?</p>
                    </div>
                    <div style='margin: 15px'>
                        <a href='?mode=EntertainerProfile&job=delete_acc' class='delete_acc'>delete account</a>
                        <button style='width: 100px;margin-left: 5px;' type='button' class='close_delete'>close</button>
                    </div>
                </div>
            </div>");

        $selectedFree = '';
        $selectedStandard = '';
        $selectedCustom = '';
        $selectedCustomClass = '';
        $invitationLink = '';
        $referrerAddress = $this->Entertainer->referrer_name;
        $fanUrl = $this->Entertainer->fan_url;
        if ($fanUrl){
            $invitationLink = 'https://'.$_SERVER["SERVER_NAME"]. '/' .$fanUrl ;
        }
        if ($plans == 0){
            $selectedFree = 'selected';
        }
        elseif ($plans == 4.97){
            $selectedStandard = 'selected';
        }
        else {
            $selectedCustom = 'selected';
            $selectedCustomClass = 'selectedCustom';
        }

        $bio = new StaysailForm('profile profile_form');
        $bio->setSubmit('Update Profile')
            ->setPostMethod()
            ->setDefaults($this->Entertainer->info() + $this->Member->info() + $fmt)
            ->setJobAction('EntertainerProfile', 'post_bio')
            ->addHTML('<div class="label">Referrer Name</div><p>' . $referrerAddress . '</p>')
            ->addHTML('<div class="label">Your Invitation Link</div><p>' . $invitationLink . '</p>')
            ->addField(StaysailForm::Line, 'Birth Date (d/m/Y)', 'birth_date_fmt', $birthDateClass)
            ->addHTML('<p><i>* Birth date is required for issuing your payment</i></p>')
            ->addField(StaysailForm::Text, 'About', 'bio', 'richtext')
            ->addField(StaysailForm::Boolean, 'Keep your profile private', 'private')
            ->addField(StaysailForm::Select, 'Set your marketing preference<br/><a onclick="alert(\'Your marketing preference allows you to notify fans when you add a new picture!\n\nClub: Photo updates go to your fans only.\nRegional: Photo updates go to all your fans in your state.\nNational: Photo updates go to all fans.\');">(What is this?)</a>', 'marketing', '', $marketing)
//            ->addHTML('<br/><h1>SMS Notification</h1>
//						<p>If you wish to be notified by text message when you get a new Fan, provide the
//						phone number and service provider below.  This information will not be made public,
//						and the text messages will come only from us.</p>')
//            ->addField(StaysailForm::Line, 'Phone Number', 'phone')
////			->addField(StaysailForm::Select, 'Provider', 'cell_provider', 'required', $providers)
//            ->addHTML('<div class="field required select select" id="field_plans_provider">
//                            <div class="label">Subscription</div>
//                            <div class="control">
//                                <select class="required select" id="plane_select">
//                                    <option value="0" ' . $selectedFree . '>Free</option>
//                                    <option value="4.97" ' . $selectedStandard . '>4.97$</option>
//                                    <option value="custom" ' . $selectedCustom . '>Custom</option>
//                                </select>
//                            </div>
//                            <input id="plan_input_val_hidden" type="hidden" name="subscription_pricing" min="5" value="'. $plans .'">
//                            <div class="plan_input ' . $selectedCustomClass . '">
//                                <input id="plan_input_val" type="text" min="5" value="'. $plans .'">
//                                <p>Please enter a number greater than 4.97$</p>
//                            </div>
//                        </div>
//            ')
//            ->addField(StaysailForm::Boolean, 'Do not send text messages', 'sms_optout')
            ->addField(StaysailForm::Line, 'Mailing Address', 'address', $addressClass)
            ->addField(StaysailForm::Line, '', 'address_2')
            ->addField(StaysailForm::Line, 'City', 'city')
            ->addField(StaysailForm::Line, 'State', 'state')
            ->addField(StaysailForm::Line, 'ZIP', 'zip');

        /*
        ->addField(StaysailForm::Select, 'Group Show Price (US $ per minute)', 'group_show_price', '', $show_prices)
        ->addField(StaysailForm::Select, 'Private Show Price (US $ per minute)', 'private_show_price', '', $show_prices);
        */

        $writer->draw($bio);

        $sql_is_deletion_request = "SELECT * FROM `deletion_requests` WHERE `member_id` = {$this->Member->id} 
                                  AND `status` = 1";
        $is_deletion_request = $this->framework->getSingleRow($sql_is_deletion_request);
        if ($is_deletion_request) {
            $writer->addHTML('<div style="font-size: 11px; display: block; margin-left: 235px;" >Account waiting for deletion</div>');
        } else {
            $writer->addHTML('<a style="margin-left:225px" id="myBtn_delete" class="delete_acc">delete account</a>');
        }
        return $writer->getHTML();
    }

    private function postBio()
    {
        $this->Entertainer->bio = StaysailIO::post('bio');
        $this->Entertainer->private = StaysailIO::post('private') ? 1 : 0;
        $this->Entertainer->marketing = StaysailIO::post('marketing');
        $this->Entertainer->birth_date = date('Y-m-d', strtotime(StaysailIO::post('birth_date_fmt')));
        $this->Entertainer->subscription_pricing = StaysailIO::post('subscription_pricing');

        // Pricing
        /*
        $this->Entertainer->group_show_price = StaysailIO::post('group_show_price');
        $this->Entertainer->private_show_price = StaysailIO::post('private_show_price');
        */
        $this->Entertainer->save();

//        $fields = array('phone', 'cell_provider', 'address', 'address_2', 'city', 'state', 'zip');
        $fields = array('phone', 'address', 'address_2', 'city', 'state', 'zip');
        $this->Member->updateFrom($fields);
        $this->Member->sms_optout = StaysailIO::post('sms_optout') ? 1 : 0;
        $this->Member->save();
    }

    private function updateSMSForm()
    {
        if ($this->Member->getRole() != Member::ROLE_ENTERTAINER) {
            return '';
        }

        $writer = new StaysailWriter();
        $writer->h1('Set Your SMS Preferences');

        $providers = SMSSender::getProviders();
        foreach ($providers as $key => $value) {
            $providers[$key] = $key;
        }

        if (!$this->Member->validatePhoneNumber()) {
            $phone_instructions = '<p><strong>Please confirm that your phone number contains exactly ten digits.</strong></p>';
        } else {
            $phone_instructions = '';
        }

        $bio = new StaysailForm('profile');
        $bio->setSubmit('Update Preferences')
            ->setPostMethod()
            ->setDefaults($this->Member->info())
            ->setJobAction('EntertainerProfile', 'post_sms')
            ->addHTML('<p>If you wish to be notified by text message when you get a new Fan, provide the
						phone number and service provider below.  This information will not be made public,
						and the text messages will come only from us.</p>' . $phone_instructions)
            ->addField(StaysailForm::Line, 'Phone Number', 'phone', 'required')
//            ->addField(StaysailForm::Select, 'Provider', 'cell_provider', 'required', $providers)
            ->addField(StaysailForm::Boolean, 'Do not send text messages', 'sms_optout');

        $writer->draw($bio);

        return $writer->getHTML();
    }

    private function postSMS()
    {
        $this->Member->phone = StaysailIO::post('phone');
        $this->Member->cell_provider = StaysailIO::post('cell_provider');
        $this->Member->sms_optout = StaysailIO::post('sms_optout') ? 1 : 0;
        $this->Member->save();
    }

    private function connectionsReport()
    {
    }

    private function fanReport()
    {

        if ($this->Entertainer->requiredFields() == true){
            $this->Member->requiredEmailVerify();
        }
        if ($this->Member->getRole() != Member::ROLE_ENTERTAINER) {
            return '';
        }

        $writer = new StaysailWriter();
        $writer->h1('Your Fans');
        $subscribers = $this->Entertainer->getActiveSubscribers();



        foreach ($subscribers as $Fan_Subscription) {
            $writer->addHTML($Fan_Subscription->getReport());
        }
        return $writer->getHTML();
    }

    private function deletePost($post_id)
    {
        $Post = new Post($post_id);

        if ($Post->belongsTo($this->Member)) {
            $Post->active = 0;
            $Post->save();
        }
    }

    private function addClub()
    {
        if ($this->Member->getRole() != Member::ROLE_CLUB) {
            return '';
        }
        $form = new StaysailForm();
        $form->setJobAction(__CLASS__, 'assign_new_club')
            ->setPostMethod()
            ->setSubmit('Add Club')
            ->addField(StaysailForm::Line, 'Account Number', 'account_number', 'required');

        $clubs = $this->Entertainer->getClubs();
        if (sizeof($clubs)) {
            $club_names = array();
            foreach ($clubs as $Club) {
                if ($Club->name) {
                    $club_names[] = $Club->name;
                }
            }
            $s = sizeof($clubs) == 1 ? '' : 's';
            $current_clubs = "You currently work at the following club{$s}: <strong>" . implode(', ', $club_names) . ".</strong>";
        } else {
            $current_clubs = "You do not yet have any clubs on your account.";
        }

        $writer = new StaysailWriter();
        $writer->h1('Add a Club')
            ->p($current_clubs)
            ->p("To add a new club to your account, please enter the club's code below and click \"Add Club.\"")
            ->draw($form);

        return $writer->getHTML();
    }

    private function assignClub()
    {
        if ($this->Member->getRole() != Member::ROLE_ENTERTAINER) {
            return '';
        }

        $writer = new StaysailWriter();

        $account_number = StaysailIO::post('account_number');
        if (!$account_number) {
            $writer->h1('Sorry...')
                ->p('You need to enter an account number to add a club.')
                ->addHTML($this->addClub());
            return $writer->getHTML();
        }

        $filter = new Filter(Filter::Match, array('account_number' => $account_number));
        $Club = $this->framework->getSingle('Club', $filter);
        if ($Club) {
            $this->Entertainer->assignClub($Club);
            $writer->h1('Success!')
                ->p('You have added ' . $Club->name . ' to your account!');
            return $writer->getHTML();
        }
        $writer->h1('Sorry...')
            ->p('The code you entered does not identify an existing club.')
            ->addHTML($this->addClub());
        return $writer->getHTML();
    }

    private function getContractScreen()
    {
        $writer = new StaysailWriter();
        $writer->addHTML(file_get_contents('../private/tools/entertainer_agreement.php'));

        $form = new StaysailForm();
        $form->setPostMethod()
            ->setJobAction(__CLASS__, 'esign_agreement')
            ->setSubmit('Sign Entertainer Agreement');
        $form->addField(StaysailForm::Line, 'Full Name', 'full_name', 'required', null,
            array('onchange' => 'addSignatureSlashes(this)'));
        $form->addField(StaysailForm::Line, 'Repeat Full Name', 'full_name2', 'require-match:full_name', null,
            array('onchange' => 'addSignatureSlashes(this)'));
        $form->addHTML('Date: ' . date('m-d-Y'));

        $writer->draw($form);
        $link = "<a href=\"https://yourfanslive.com/YourFansLive-Contract.pdf\" target=\"_blank\">You may download this agreement for your records by clicking here</a>";
        $writer->p($link);

        return $writer->getHTML();
    }

    private function getDocumentScreen()
    {
        $docsUploaded = StaysailIO::session('docs_uploaded');
        StaysailIO::setSession('docs_uploaded', 0);

        $memberDocs = $this->framework->getRowByField('Member_Docs', 'Member_id', $this->Entertainer->Member_id);
        $memberDocs = new Member_Docs(isset($memberDocs['id']) ? $memberDocs['id'] : null);
        if ($memberDocs && $memberDocs->allIdDocsApproved()) {
            return '';
        }
        $writer = new StaysailWriter();
        if ($docsUploaded) {
            $writer->addHTML('<h2 class="_hedging" style="color: green">Documents uploaded successfully!</h2>');
        }

        $writer
            ->addHTML('<h2 class="_hedging"> ID VERIFICATION FORM</h2>
                       <div class="_row">
                            <div class="blok">
                                <p class="form-label">
                                    images must be legible and cannot be scans or copies. Identification used must current and valid. Images must be smaller than 1gb each.
                                </p>
                                <div class="form-block">
                                    <img src="/site_img/icons/_1.png" width="100">
                                    <p class="form-label">
                                        Take photo of front of Photo ID. Acceptable IDs are: Drivers License, Identification Card or Passport
                                    </p>
                                </div>
                                <div class="form-block">
                                    <img src="/site_img/icons/_2.png" width="100">
                                    <p class="form-label">
                                        Take photo of back of Photo ID. if your ID does not have a back: Upload a second form of valid ID.
                                    </p>
                                </div>
                                <div class="form-block">
                                    <img src="/site_img/icons/_3.png" width="175">
                                    <p class="form-label">
                                        Take a photo holding your photo ID next to your face. Photo must be clear and both your face and ID must be legible. Please check your image before upload to confirm it is clear.
                                    </p>
                                </div>
                            </div>
                            <div class="blok">
                                <form id="ack_instructions" enctype="multipart/form-data" method="POST" action="?mode=EntertainerProfile&job=ack_instructions">
                                   '.
                (!$memberDocs || !$memberDocs->doc_first_page_file_approved ? '<div class="_mb-3">
                                        <label for="formFile1" class="form-label">Front of Photo ID</label>
                                        <br>
                                        <label class="input-file">
                                            <span class="_btn-form">+ Add file</span>
                                            <input type="file" id="formFile1" name="doc_first_page_file" required>
                                        </label>
                                    </div>' : '').
                (!$memberDocs || !$memberDocs->doc_second_page_file_approved ?  ' <div class="_mb-3">
                                        <label for="formFile2" class="form-label">Back of Photo ID</label>
                                        <br>
                                        <label class="input-file">
                                            <span class="_btn-form">+ Add file</span>
                                            <input type="file" id="formFile2" name="doc_second_page_file" required>
                                        </label>
                                    </div>' : '')
                . (!$memberDocs || !$memberDocs->doc_and_face_file_approved ? '<div class="_mb-3">
                                        <label for="formFile3" class="form-label">Headshot of you holding your ID</label>
                                        <br>
                                        <label class="input-file">
                                            <span class="_btn-form">+ Add file</span>
                                            <input type="file" id="formFile3" name="doc_and_face_file" required>
                                        </label>
                                    </div>' : '')
                .'

                                    <div class="_mb-3">
                                        <label for="release" class="form-label">Legal Name</label>
                                        <input type="text" class="form-control" name="release_form_ref_name" id="release" maxlength="60" placeholder="Name" required>
                                        <div class="_text-end">0/60</div>

                                    </div>
                                    <div class="_text_center">
                                        <button type="submit" class="_btn_success id_verify">SUBMIT</button>
                                    </div>
                                </form>
                            </div>
                       </div>');
        return $writer->getHTML();
    }

    private function signAgreement()
    {
        $full_name = StaysailIO::post('full_name');
        $full_name2 = StaysailIO::post('full_name2');
        if ($full_name and $this->Entertainer and $full_name == $full_name2) {
            if ($this->Entertainer->signEntertainerAgreement($full_name)) {
                header("Location:?mode=EntertainerProfile&job=update_bio");
                exit;
            }
        }
    }

    private function acknowledgeInstructions()
    {
        if ($this->Member->getRole() != Member::ROLE_ENTERTAINER) {
           return false;
        }
//var_dump($_SESSION);
        $memberDocs = $this->framework->getRowByField('Member_Docs', 'Member_id', $this->Entertainer->Member_id);
        $memberDocs = new Member_Docs(isset($memberDocs['id']) ? $memberDocs['id'] : null);
        $fields = array('doc_type', 'release_form_ref_name');
        $memberDocs->updateFrom($fields);
        $memberDocs->Member_id = $this->Entertainer->Member_id;
        $memberDocs->Entertainer_id = $this->Entertainer->id;
        $memberDocs->doc_type = 1;
        $memberDocs->doc_first_page_file =  ' ';
        $memberDocs->doc_second_page_file = ' ';
        $memberDocs->doc_and_face_file = ' ';
        $memberDocs->release_form_ref_name = ' ';
        $memberDocs->signed_form_file_1 = ' ';
        $memberDocs->signed_form_file_2 = ' ';
        $memberDocs->signed_form_file_3 = ' ';
//        $memberDocs->signed_form_file_1_approved = false;
//        $memberDocs->signed_form_file_2_approved = false;
//        $memberDocs->signed_form_file_3_approved = false;
//        $memberDocs->doc_first_page_file_approved = false;
//        $memberDocs->doc_second_page_file_approved = false;
//        $memberDocs->doc_and_face_file_approved = false;

        $memberDocs->save();
        $memberDocs->setLibraryPath('documents', DATAROOT . '/private/documents/'. $memberDocs->Member_id);

        foreach ($memberDocs->getFileNames() as $fileField => $fileName) {
            if (!empty($_FILES[$fileField]['name'])) {
                $memberDocs->$fileField = $memberDocs->uploadFile('documents', $memberDocs->id.$fileField.'.jpg', $_FILES[$fileField], '', true);

            }
        }

        $memberDocs->save();

        StaysailIO::setSession('docs_uploaded', 1);

            $message = <<<__END__
Entertainer ({$this->Member->email}) the artist has uploaded the files.
__END__;
            if ($_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
                mail('support@yourfanslive.com', "Entertainer MAIL: {$this->Entertainer->email}", $message);
            } else {
                mail('jjustian@gmail.com', "Entertainer MAIL: {$this->Entertainer->email}", $message);
            }
    }

    private function uploadCollaboratorDocs()
    {
        if ($this->Member->getRole() != Member::ROLE_ENTERTAINER) {
           return false;
        }

        $stageName = StaysailIO::post('stage_name');
        $existingCollaborator = $this->framework->getRowByConditionsString('Entertainer_Collaborator',
          "Member_id = {$this->Entertainer->Member_id} AND stage_name='{$stageName}'");

        if (isset($existingCollaborator['id'])) {
            StaysailIO::setSession('collaboratorSessionError', 'Collaborator with such Stage name already exists!');
        } else {

            $entertainerCollaborator = new Entertainer_Collaborator();

            $fields = array('stage_name');
            $entertainerCollaborator->updateFrom($fields);
            $entertainerCollaborator->Member_id = $this->Entertainer->Member_id;
            $entertainerCollaborator->Entertainer_id = $this->Entertainer->id;
            $entertainerCollaborator->save();


            $entertainerCollaborator->setLibraryPath('collaborators', DATAROOT . '/private/collaborators/'. $entertainerCollaborator->Member_id);

            $files = [];
            foreach ($_FILES['photo_and_2nd_form_id'] as $fileOptionKey => $data) {
                foreach ($data as $dataKey => $val) {
                    if (!isset($files[$dataKey])) {
                        $files[$dataKey] = [];
                    }
                    $files[$dataKey][$fileOptionKey] = $val;
                }
            }
            foreach ($files as $key => $file) {
                if (!empty($file['name'])) {
                    $entertainerCollaborator->uploadFile('collaborators', $entertainerCollaborator->id.'_photo_and_2nd_form_id_'.$key.'.jpg', $file);
                }
            }

            $entertainerCollaborator->uploadFile('collaborators', $entertainerCollaborator->id.'_headshot.jpg', $_FILES['headshot']);
            $entertainerCollaborator->uploadFile('collaborators', $entertainerCollaborator->id.'_completed_2257.jpg', $_FILES['completed_2257']);


            StaysailIO::setSession('collaboratorSessionSuccess', 'Collaborator files uploaded successfully');
        }
    }

    public function setAvatar($x = null, $y = null, $hw = null)
    {
        if (!$x || !$y || !$hw){
            $x = StaysailIO::post('x');
            $y = StaysailIO::post('y');
            $hw = StaysailIO::post('hw');
        }
        if (!$x || !$y || !$hw){
            $x = 20;
            $y = 20;
            $hw = 320;
        }

        $path = DATAROOT . "/private/avatars/entertainerAvatar{$this->Member->id}.png";
        $size = GetImageSize($path);

        $factor = $size[0] / 360; // Crop factor of image
        $x *= $factor;
        $y *= $factor;
        $hw *= $factor;

        switch ($size['mime']) {
            case 'image/jpeg':
                $src_img = ImageCreateFromJPEG($path);
                break;

            case 'image/png':
                $src_img = ImageCreateFromPNG($path);
                break;
        }

        $cropped = ImageCreateTrueColor($hw, $hw);
        ImageCopyResampled($cropped, $src_img, 0, 0, $x, $y, $hw, $hw, $hw, $hw);


        $save_path = DATAROOT . "/private/avatars/entertainerAvatar{$this->Member->id}.png";
        ImagePNG($cropped, $save_path);
    }

    public function cropAvatar()
    {
        $url = $this->Member->getEntertainerAvatarURL();

        $html = <<<__END__
        <h1>Crop your Avatar</h1>
        <p>The headshot needs to be a square.  Click the starting point of your headshot, and drag the mouse pointer while holding down the button.  When you're happy with the size and composition, click the Crop Avatar button.</p>

        <div id="squarifier">
        <div id="square"></div>
        </div>

        <form action="?mode=EntertainerProfile&job=set_avatar" method="post">
        <input type="hidden" name="x" id="x" value="20" />
        <input type="hidden" name="y" id="y" value="20" />
        <input type="hidden" name="hw" id="hw" value="320" />
        <input type="submit" value="Crop Avatar" />
        </form>

        <style>
        #squarifier {
            background-image: url("{$url}&w=1");
        }
        </style>
__END__;
        return $html;
    }

    public function unsubscribe($fanId, $entertainerId)
    {
        $filter = new Filter(Filter::Match, array('Fan_id' => $fanId, 'Entertainer_id' => $entertainerId));
        $fanSubscription = $this->framework->getSingle('Fan_Subscription', $filter);

        if ($fanSubscription ) {
            $fanSubscription->delete_Job();
        }
    }

    public function emailVerify()
    {
        $writer = new StaysailWriter('email_verify');
        $writer->addHTML('<h1>plase</h1>');

        return $writer->getHTML();
    }
}
?>