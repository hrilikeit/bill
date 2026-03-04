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
require '../private/domain/class.MailSend.php';

class EmailVerify
{
    protected $page, $settings, $categories;
    protected $framework;

    public $Member;
    public $Entertainer;
    public $valid;

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
    }

    public function getHTML()
    {
        $job = StaysailIO::get('job');
        $id = StaysailIO::get('id');
        $left_side_override = '';
        $map = Maps::getEntertainerProfileMap();
        $whole_screen_map = false;

        switch ($job) {
            case 'email_verify':
                $left_side_override = $this->emailVerify();
                break;

            case 'resend_verify_email':
                $this->resendVerifyEmail();
                header('Location: /?mode=EmailVerify&job=email_verify');
                exit;
        }
        $addShow = '';
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
//        $action = new ActionsView($this->Member);
//        $banner = new BannerAdsView($this->Member);
        //$subscription = new SubscriptionListView($this->Member);
//        $twitter = new TwitterView();

        $containers = array(new StaysailContainer('H', 'header', $header->getHTML()),
            new StaysailContainer('F', 'footer', $footer->getHTML()),
//            new StaysailContainer('A', 'action', $action->getHTML()),
//            new StaysailContainer('B', 'banner', $twitter->getHTML() . /*$subscription->getClubList() .*/ $banner->getHTML()),
        );

        $posts = new PostView($this->Member);
        $summary = new SummaryView($this->Member);
//        $events = new EventsView($this->Member);
        $chat = new LiveChatView($this->Member);
        $friends = new FriendsView($this->Member);
        $SeemsLikes = new SeemsLikeView($this->Member);
        $MostPopular = new MostPopularView($this->Member);

        $notify_fans = '';
        if (!StaysailIO::session('notify_fans')) {
            $notify_fans = "<p></p>\n";
        }
        $disabled = $isSubscribed = '';

       
        $left = $left_side_override ? $left_side_override : ($notify_fans . $summary->getPhotoLandscapeHTML() . $summary->getPhotoSummaryHTML() . $posts->getHTML());
        $chat_content = $left_side_override ? '' : $chat->getHTML();
//        $right = $summary->getHTML() . $events->getHTML() . $chat_content;
        $right = $summary->getHTML();
        $hasDocs = $this->Member->getRole() != Member::ROLE_ENTERTAINER || $this->Entertainer->contract_signature;
        if ($this->Member->getRole() == Member::ROLE_FAN || $this->Member->getRole() == Member::ROLE_ENTERTAINER) {
            if (($hasDocs && $job == '' )|| $job == 'esign_agreement') {
                $this->Entertainer->requiredFields();
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
                    $subscriptionButton .' </div>
                                    <div class="notify-fans">' . $notify_fans . '</div>
                                </div>
                            </div>
                            <div class="profile-bottom-section">
                                <div class="left-section">
                                ' . $posts->getHTML() . '
                                </div>
                                <div class="right-section">
                                <!-- . $events->getHTML() . $chat_content . -->
                                ' . $friends->getHTML() . $chat_content . $SeemsLikes->getHTML() . $MostPopular->getHTML() . '
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
                                      <!--   . $events->getHTML() .  -->
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
        }

        $layout = new StaysailLayout($map, $containers);

        return $layout->getHTML();
    }

    public function emailVerify()
    {
        $writer = new StaysailWriter('email_verify');
        $writer->addHTML('<h1>Please verify your email address! Check your inbox or spam folder !</h1>');
        $writer->addHTML('<form method="POST" action="?mode=EmailVerify&job=resend_verify_email"><button type="submit">Resend email confirmation</button></form>');

        return $writer->getHTML();
    }

    public function resendVerifyEmail()
    {
        $email = $this->Member->email;
        $domain = $_SERVER['SERVER_NAME'];
        $activation_link = "https://$domain/?mode=PublicLive&job=activate&email=$email";
        $subject = 'Please activate your account';
        $message = "Hi,Please click the following link to activate your account: <a href='$activation_link'>here</a>";

        $MailSend = new MailSend($this->Member);
        $MailSend->send($email, $subject, $message, 1);
    }
}