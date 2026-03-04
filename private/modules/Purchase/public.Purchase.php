<?php
define('PRICE_MEMBER_FEE', '4.97');
define('PRICE_ENTERTAINER', '2.00');
define('PRICE_HIRES', '9.99');
define('PRICE_MEDRES', '4.99');

define('GATEWAY_ORBITAL', 'orbital');
define('GATEWAY_PAY_TECH', 'paytech');

require '../private/tools/OrbitalPaymentGateway.php';
require '../private/tools/PayTechTrustPaymentGateway.php';
require '../private/views/PostView.php';
require '../private/views/SummaryView.php';
require '../private/views/EventsView.php';
require '../private/views/LiveChatView.php';
require '../private/views/SubscriptionListView.php';
require '../private/views/TwitterView.php';
require '../private/domain/class.MailSend.php';

class Purchase extends StaysailPublic
{
    protected $page, $settings, $categories;
    protected $framework;

    public $Member;
    public $Entertainer;
    public $Fan;
    public $valid;

    public function __construct($dbc = '')
    {
        $this->valid = false;

        $this->framework = StaysailIO::engage();

        $member_id = StaysailIO::session('Member.id');
        $this->Member = new Member($member_id);
        $this->Fan = $this->Member->getAccountOfType('Fan');
    }

    public function getHTML()
    {
        $job = StaysailIO::get('job');
        $map = Maps::getGalleryMap();
        $content = '';
        if ($this->Member->name == 'Demo') {
            $content = $this->cannotPurchase();
        } else {
            switch ($job) {
                case 'purchase':
                case 'purchaseVideo':
                    $content = $this->getVerifyHTML();
                    break;

                case 'verify':
                    $content = $this->completePurchase();
                    break;
            }
        }

        $header = new HeaderView();
        $footer = new FooterView();
        $action = new ActionsView($this->Member);
        $banner = new BannerAdsView();

        $containers = array(new StaysailContainer('H', 'header', $header->getHTML()),
            new StaysailContainer('F', 'footer', $footer->getHTML()),
            new StaysailContainer('A', 'action', $action->getHTML()),
            new StaysailContainer('B', 'banner', $banner->getHTML()),
        );

        $content .= "<p style=\"clear:both\"><strong>Note: </strong><i>YourFansLive.com is billed on your credit card as <span class=\"red\">\"Local City Scene\"</span>.</i></p>";

        // Add PCI Compliance seal
        $content .= '<script type="text/javascript" src="https://sealserver.trustwave.com/seal.js?code=b8db215dbdf145ada76aff0e014fa290"></script>';

        $containers[] = new StaysailContainer('C', 'content', $content);
        $layout = new StaysailLayout($map, $containers);
        return $layout->getHTML();
    }

    private function cannotPurchase()
    {
        $writer = new StaysailWriter();
        $writer->h1('Sorry...');
        $writer->p('This Demo account cannot be used to make purchases.  Feel free to explore the rest of the account options!');
        return $writer->getHTML();
    }

    private function getVerifyHTML()
    {
        $writer = new StaysailWriter();

        $type = StaysailIO::get('type');
        $entity_id = StaysailIO::getInt('id');
        $job = StaysailIO::get('job');
        $item = $description = $price = $instructions = '';
        $map = Maps::getGalleryMap();

        switch ($type) {
            case 'Library':
                $Library = new Library($entity_id);
                if ($job == 'purchase') {

                    return $this->getLibraryPurchaseHTML($Library);
                }
                if ($job == 'purchaseVideo') {
                    return $this->getLibraryPurchaseVideoHTML($Library);
                }

            case 'Entertainer':
                $Entertainer = new Entertainer($entity_id);
                return $this->getSubscriptionPurchaseHTML($Entertainer);

            case 'WebShow':
                $WebShow = new WebShow($entity_id);
                return $this->getWebShowPurchaseHTML($WebShow);

            case 'Member':
                return $this->getMembershipPurchaseHTML();

            case 'Tip':
                $entertainer_id = StaysailIO::session('Entertainer.id');
                $Entertainer = new Entertainer($entertainer_id);
                return $this->getTipPurchaseHTML($Entertainer);
        }
    }

    private function completePurchase()
    {
        $type = StaysailIO::post('type');
        $entity_id = StaysailIO::post('id');
        $message = '';

        switch ($type) {
            case 'Library':
                $Library = new Library($entity_id);

                if ($Library->File_Type_id == 3 && $this->Fan->hasPurchased($Library)) {
                    return "<h1>Wait a Minute...</h1><p>You already purchased this image.</p>";
                }
                if ($Library->File_Type_id == 4 && $this->Fan->hasPurchased($Library)) {
                    return "<h1>Wait a Minute...</h1><p>You already purchased this Video.</p>";
                }
                $Order = new Order();
                $Order->setMember($this->Member);

                $resolution = StaysailIO::post('resolution');  // 1 = Medium, 2 = High
                $resolution = $resolution == 2 ? 2 : 1; // Allow only 1 and 2
                //$price = $resolution == 2 ? PRICE_HIRES : PRICE_MEDRES;
                $price = $Library->price;
                if ($Library->File_Type_id == 3) {
                    $Order->addOrderLine('Image Purchase', $price, 1, $Library);
                } else {
                    $Order->addOrderLine('Video Purchase', $price, 1, $Library);
                }
                list ($ok, $message) = $this->validateCharge($Order);
                $ok = true;
                if ($ok) {
                    $Fan_Library = new Fan_Library();
                    $update = array('Fan_id' => $this->Fan->id,
                        'Library_id' => $entity_id,
                        'File_Type_id' => $resolution,
                    );
                    $Fan_Library->update($update);
                    $Fan_Library->save();

                    $nameFan = $this->Fan->name;
                    if ($nameFan){
                        $fanMember = new Member($this->Fan->Member_id);
                        $nameFan = $fanMember->name;
                    }
                    $message = "Congratulations ! 
                    '$nameFan'  Purchased your Paid Photo. 
                    You can get more info by visiting to your page.";
                    $subject = 'Picture purchase';
                    $memberEntertainerId = $Library->Member_id;
                    $memberEntertainer = new Member($memberEntertainerId);

                    $MailSend = new MailSend($memberEntertainer);
                    $MailSend->send($memberEntertainer->email, $subject, $message, 0, false);
                } else {
                    $Order->cancel();
                }
                break;

            case 'video_store':
                $Library = new Library($entity_id);

                if ($Library->File_Type_id == 4 && $this->Fan->hasPurchased($Library)) {
                    return "<h1>Wait a Minute...</h1><p>You already purchased this Video.</p>";
                }
                $Order = new Order();
                $Order->setMember($this->Member);
                $price = $Library->price;
                if ($Library->File_Type_id == 4){
                    $Order->addOrderLine('Video Purchase', $price, 1, $Library);
                }
                list ($ok, $message) = $this->validateCharge($Order);
                if ($ok) {
                    $Fan_Library = new Fan_Library();
                    $update = array('Fan_id' => $this->Fan->id,
                        'Library_id' => $entity_id,
                    );
                    $Fan_Library->update($update);
                    $Fan_Library->save();
                    $file = DATAROOT . "/private/library/{$Library->id}";
                    foreach(['mp4', 'MOV', 'mov'] as $format) {
                        if (file_exists($file . '.' . $format)) {
                            $file = $file . '.' . $format;
                        }
                    }

                    $nameFan = $this->Fan->name;
                    if ($nameFan){
                        $fanMember = new Member($this->Fan->Member_id);
                        $nameFan = $fanMember->name;
                    }
                    $message = "Congratulations ! 
                    '$nameFan'  Purchased your video in video store. 
                    You can get more info by visiting to your page.";
                    $subject = 'Video Store';
                    $memberEntertainerId = $Library->Member_id;
                    $memberEntertainer = new Member($memberEntertainerId);

                    $MailSend = new MailSend($memberEntertainer);
                    $MailSend->send($memberEntertainer->email, $subject, $message, 0, false);

                    header("Content-type:application/pdf");
                    header('Content-Disposition: attachment; filename=' . $file);
                    readfile($file);

                } else {
                    $Order->cancel();
                }
                break;

            case 'Entertainer':
                $Entertainer = new Entertainer($entity_id);

                $Order = new Order();
                $Order->setMember($this->Member);
                $Order->addOrderLine('Entertainer Subscription', $Entertainer->subscription_pricing, 1, $Entertainer);
                if ($Order->getTotalAmount() == 0) { //free subscription
                    $ok = true;
                    $message = 'Subscribed!';
                    $this->Fan->subscribeTo($Entertainer);
                } else  {
                    list ($ok, $message) = $this->validateCharge($Order);

                }

                if ($ok) {
                    $this->Fan->subscribeTo($Entertainer);

                    $nameFan = $this->Fan->name;
                    if ($nameFan){
                        $fanMember = new Member($this->Fan->Member_id);
                        $nameFan = $fanMember->name;
                    }
                    $messageGmail = "Congratulations ! 
                                You have a new subscriber with name '$nameFan'";
                    $subject = 'New subscriber';
                    $memberEntertainerId = $Entertainer->Member_id;
                    $memberEntertainer = new Member($memberEntertainerId);

                    $MailSend = new MailSend($memberEntertainer);
                    $MailSend->send($memberEntertainer->email, $subject, $messageGmail, 0, false);
                } else {
                    $Order->cancel();
                }
                break;

            case 'WebShow':
                $WebShow = new WebShow($entity_id);
                $minute_range = range(5, 30, 1);
                $minute_sel = StaysailIO::post('minutes');
                if (isset($minute_range[$minute_sel])) {
                    $minutes = $minute_range[$minute_sel];
                } else {
                    $minutes = 5;
                }
                $Order = new Order();
                $Order->setMember($this->Member);
                $Order->addOrderLine('Show', $WebShow->channel_price, $minutes, $WebShow);

                if ($WebShow->channel_price > 0) {
                    list ($ok, $message) = $this->validateCharge($Order, true);
                } else {
                    list ($ok, $message) = array(true, 'Free show');
                }
                if ($ok) {
                    $Fan_WebShow_Status = new Fan_WebShow_Status();
                    $Fan_WebShow_Status->update(array('Fan_id' => $this->Fan->id,
                        'WebShow_id' => $WebShow->id,
                        'Order_id' => $Order->id,
                        'minutes_purchased' => $minutes,
                        'polls' => 0,
                        'payment_captured' => 0,
                    ));

                    $Fan_WebShow_Status->save();
                    header("Location: /?mode=WebShowModule&job=join_show");
                    exit;
                } else {
                    $Order->cancel();
                }
                break;

            case 'Member':
                $Member = $this->Member;
                $Order = new Order();
                $Order->setMember($this->Member);
                $Order->addOrderLine('Monthly Member Fee', PRICE_MEMBER_FEE);
                list ($ok, $message) = $this->validateCharge($Order);
                if ($ok) {
                    $Member->extendMembership();
                    if (StaysailIO::session('inviter_entertainer_id')) {
                        // If the payment comes from an invitation, subscribe to the inviter for free
                        $entertainer_id = StaysailIO::session('inviter_entertainer_id');
                        $Entertainer = new Entertainer($entertainer_id);
                        $this->Fan->subscribeTo($Entertainer);
                    }
                } else {
                    $Order->cancel();
                }
                break;

            case 'Tip':
                $Entertainer = new Entertainer($entity_id);
                $amount = StaysailIO::post('amount');
                $amount = intval($amount);
                if ($amount > 0) {
                    $Order = new Order();
                    $Order->setMember($this->Member);
                    $Order->addOrderLine('Fan Tip', $amount, 1, $Entertainer);
                    list ($ok, $message) = $this->validateCharge($Order);
                    if ($ok) {
                        $filters = array(new Filter(Filter::Match, array('Entertainer_id' => $entity_id)),
                            new Filter(Filter::Sort, 'id DESC'),
                        );
                        $goalHistory = $this->framework->getSingle('Goal', $filters);
                        $goalId = $goalHistory->id;
                        $goal = new Goal($goalId);
                        if ($goal->status == 0){
                            $goal->current_count += $amount;
                            if ($goal->price <= $goal->current_count){
                                $goal->status = 1;
                            }
                            $goal->save();
                        }

                        // Send SMS to Entertainer
//                        $SMSSender = new SMSSender($Entertainer->Member, 'YourFansLive');
//                        $sms_message = "You have received a \${$amount} tip from {$this->Fan->name}!";
//                        $SMSSender->send($sms_message);
                    } else {
                        $Order->cancel();
                    }
                } else {
                    $message = "Amount is zero";
                }
                break;


            default:
                $message = "Unknown type {$type}";
                break;

        }

        return $message;
    }

    private function getLibraryPurchaseHTML(Library $Library)
    {
        if (!$this->Fan->isSubscribedTo($Library->Member->getAccountOfType('Entertainer'))) {
            return "<h1>Sorry...</h1><p>You are not subscribed to this Entertainer.</p>";
        }

        $writer = new StaysailWriter();

        $full_price = PRICE_HIRES;
        $reduced_price = PRICE_MEDRES;
        $name = 'Hi-Res Image';
        $price = $Library->price;
        list ($width, $height) = $Library->getNativeSize();
        $available_resolutions = array();
        if ($width < 3024 and $height < 3024) {
            // The full-size image is the only one available, and it's medium resolution
            $available_resolutions[1] = "$" . $price . " -- Medium-Res Image ({$width}x{$height})";
        } else {
            $smaller_height = intval(($height / $width) * 1200);
            $available_resolutions[1] = "$" . $price . " -- Medium-Res Image (1200x{$smaller_height})";
            $available_resolutions[2] = "$" . $price . " -- High-Res Image ({$width}x{$height})";
        }

        // Set redirect URL if the user chooses to add a new payment method
        $url = "?mode=Purchase&job=purchase&type=Library&id={$Library->id}";
        StaysailIO::setSession('purchase_redirect', $url);

        $verify_form = new StaysailForm();
        $verify_form->setSubmit('Purchase Image')
            ->setPostMethod()
            ->setJobAction(__CLASS__, 'verify')
            ->setDefaults(array('type' => 'Library', 'id' => $Library->id, 'payment_method_id' => StaysailIO::session('Payment_Method.id')))
            ->addField(StaysailForm::Hidden, '', 'type')
            ->addField(StaysailForm::Hidden, '', 'id')
            ->addField(StaysailForm::Select, 'Resolution', 'resolution', '', $available_resolutions, 'id');

        $payment_methods = $this->Member->getPaymentMethods();
        if (sizeof($payment_methods)) {
            $options = array();
            foreach ($payment_methods as $Payment_Method) {
                $options[$Payment_Method->id] = $Payment_Method->name;
            }

            $verify_form->addField(StaysailForm::Select, 'Pay With', 'payment_method_id', '', $options);
//            $verify_form->addField(StaysailForm::Select, 'Payment Gateway', 'payment_gateway', '', [
//                GATEWAY_ORBITAL => 'Orbital',
//                GATEWAY_PAY_TECH => 'Pay Tech trust',
//            ]);
        } else {
            header("Location:?mode=FanHome&job=new_payment_method");
            exit;
        }
        $verify_form->addHTML(StaysailWriter::makeJobLink('Add Payment Method', 'FanHome', 'new_payment_method', '', 'spaced button'));
        $image = $Library->getWebHTML();
        $image_content = $image . "<div class='bg'></div>
                                <form method='post' class='profile_lock_form' action='?mode=Purchase&job=verify' onsubmit='return uniValidate(this)'>
                                    <input type='hidden' name='type' value='Library'>
                                    <input type='hidden' name='id' value='$Library->id'>
                                    <input id='resolution_val' type='hidden' name='resolution' value='1'>
                                    <input id='payment_method_id_val' type='hidden' name='payment_method_id' value='273'>
                                    <div class='profile_lock profile_lock_container'>
                                        <span>
                                            <input id='purchase_charge' class='profile_lock_input' type='submit' value='Unlock image for &#36;" . $Library->price . "'>
                                        </span>
                                    </div>
                                 </form>";

        $coin = "<div coinsuites='Paywidget' coinsuites_id='3e9af501-5dc5-4792-9a96-2c7c2f7fc18e' amount='" . $Library->price . "' currency='USD' ref_id='" . $Library->id . "' class='coinsuitespaybtn'></div>
                                 <script src='https://apps.coinsuites.com/widget/coinsuites.js?v4'></script>";
        $writer->h1('Purchase Image')
            ->start('purchase_img')
            ->addHTML($image_content)
            ->end('purchase_img')
            ->start('purchase_options')
            ->addHTML($coin)
            ->draw($verify_form)
            ->end('purchase_options');
        return $writer->getHTML();
    }

    private function getLibraryPurchaseVideoHTML(Library $Library)
    {
        if (!$this->Fan->isSubscribedTo($Library->Member->getAccountOfType('Entertainer'))) {
            return "<h1>Sorry...</h1><p>You are not subscribed to this Entertainer.</p>";
        }

        $writer = new StaysailWriter();

        $full_price = PRICE_HIRES;
        $reduced_price = PRICE_MEDRES;
        $name = 'Hi-Res Image';

        $getID3 = new getID3;
        $file = $getID3->analyze('../private/library/' . $Library->image);
        $width = $file['video']['resolution_x'];
        $height = $file['video']['resolution_y'];
        $price = $Library->price;
        if (!empty($price)) {

        }

        $available_resolutions = array();

        // The full-size image is the only one available, and it's medium resolution
        $available_resolutions[1] = "$" . $price . " -- Video ({$width}x{$height})";


        // Set redirect URL if the user chooses to add a new payment method
        $url = "?mode=Purchase&job=purchaseVideo&type=Library&id={$Library->id}";
        StaysailIO::setSession('purchaseVideo_redirect', $url);

        $verify_form = new StaysailForm('buy_video_form');
        $verify_form->setSubmit('Purchase Video')
            ->setPostMethod()
            ->setJobAction(__CLASS__, 'verify')
            ->setDefaults(array('type' => 'Library', 'id' => $Library->id, 'payment_method_id' => StaysailIO::session('Payment_Method.id')))
            ->addField(StaysailForm::Hidden, '', 'type')
            ->addField(StaysailForm::Hidden, '', 'id')
            ->addField(StaysailForm::Select, 'Resolution', 'resolution', '', $available_resolutions);

        $payment_methods = $this->Member->getPaymentMethods();
        if (sizeof($payment_methods)) {
            $options = array();
            foreach ($payment_methods as $Payment_Method) {
                $options[$Payment_Method->id] = $Payment_Method->name;
            }
            $verify_form->addField(StaysailForm::Select, 'Pay With', 'payment_method_id', '', $options);
//            $verify_form->addField(StaysailForm::Select, 'Payment Gateway', 'payment_gateway', '', [
//                GATEWAY_ORBITAL => 'Orbital',
//                GATEWAY_PAY_TECH => 'Pay Tech trust',
//            ]);
        } else {
            header("Location:?mode=FanHome&job=new_payment_method");
            exit;
        }
        $verify_form->addHTML(StaysailWriter::makeJobLink('Add Payment Method', 'FanHome', 'new_payment_method', '', 'spaced button'));
        $video = $Library->getWebVideoHTML();
//		if($Library->mime_type == 'video/quicktime' || $Library->mime_type == 'video/x-msvideo'){
//			$video_content = '<embed src="'.$video.'" Pluginspage="https://support.apple.com/quicktime" width="320" height="240" CONTROLLER="true" LOOP="false" AUTOPLAY="false" type="'.$Library->mime_type.'" name="Video"></embed>
//			<a href="javascript:void(0);"><img src="/site_img/lock-icon.png"/><span>Unlock video for &#36;'.$Library->price.'</span></a>';
//		}else{
        $type = $Library->mime_type;

        if (in_array($Library->mime_type, ['video/quicktime', 'video/x-msvideo', ''])) {
            $type = 'video/mp4';
        }
        $video_content = "<form method='post' class='profile_lock_form purchaseVideoLock' action='?mode=Purchase&amp;job=verify' onsubmit='return uniValidate(this)'>
                                    <video controls width='320' height='240' preload='none' style='position: absolute;'>
								<source src='$video' type='$type'>
								your browser does not support this tag.
						</video>
						<input type='hidden' name='type' value='Library'>
                                    <input type='hidden' name='id' value='$Library->id'>
                                    <input id='resolution_val' type='hidden' name='resolution' value='1'>
                                    <input id='payment_method_id_val' type='hidden' name='payment_method_id' value='273'>
                                    <div class='profile_lock profile_lock_container profile_lock_containerVideo'>
                                        <span>
                                            <input id='purchase_charge' class='profile_lock_input' type='submit' value='Unlock video for &#36;" . $Library->price . "'>
                                        </span>
                                    </div>
                                 </form>
                                 
                                 
";
//		}

        $coin = "<div coinsuites='Paywidget' coinsuites_id='3e9af501-5dc5-4792-9a96-2c7c2f7fc18e' amount='" . $Library->price . "' currency='USD' ref_id='" . $Library->id . "' class='coinsuitespaybtn'></div>
                                 <script src='https://apps.coinsuites.com/widget/coinsuites.js?v4'></script>";
        $writer->h1('Purchase Video')
            ->start('purchase_video purchaseVideo')
            ->addHTML($video_content)
            ->end('purchase_video purchaseVideo')
            ->start('purchase_options')
            ->addHTML($coin)
            ->draw($verify_form)
            ->end('purchase_options');
        return $writer->getHTML();
    }

    public function getSubscriptionPurchaseHTML(Entertainer $Entertainer)
    {
//        $reminder = false; // Do we need to remind the Entertainer to sign the agreement again?
//        $whole_screen_map = false;


        // If the Member has logged in, and is paid up, see if there's a redirect
//        if (StaysailIO::session('post_login_redirect')) {
//            $post_login_redirect = StaysailIO::session('post_login_redirect');
//            StaysailIO::setSession('post_login_redirect', null);
//            StaysailIO::setSession('MemberIsCurrent', true);
//            header("Location:{$post_login_redirect}");
//            exit;
//        }

//        $header = new HeaderView();
//        $footer = new FooterView();
//        $action = new ActionsView($this->Member);
//        $banner = new BannerAdsView($this->Member);
        //$subscription = new SubscriptionListView($this->Member);
//        $twitter = new TwitterView();

//        $containers = array(new StaysailContainer('H', 'header', $header->getHTML()),
//            new StaysailContainer('F', 'footer', $footer->getHTML()),
//            new StaysailContainer('A', 'action', $action->getHTML()),
//            new StaysailContainer('B', 'banner', $twitter->getHTML() . /*$subscription->getClubList() .*/ $banner->getHTML()),
//        );

//        $posts = new PostView($this->Member);

        $summary = new SummaryView($Entertainer->Member, true);
//        $events = new EventsView($this->Member);
//        $chat = new LiveChatView($this->Member);

//        $notify_fans = '';
//        if (!StaysailIO::session('notify_fans')) {
//            $notify_fans = "<p></p>\n";
//        }
//        $left = $left_side_override ? $left_side_override : ($notify_fans . $summary->getPhotoLandscapeHTML() . $summary->getPhotoSummaryHTML() . $posts->getHTML());
//        $chat_content = $left_side_override ? '' : $chat->getHTML();
//        $right = $summary->getHTML() . $events->getHTML() . $chat_content;

        $html = '<div class="main-div">
					<div class="profile-top-section">
						<div class="display-photo">' . $summary->getPhotoLandscapeHTML() . '</div>
						<div class="profile_banner_container purchase-buttons">
                            <div class="purchase-buttons-content">
                                <div class="avatar-photo">' . $summary->getPhotoSummaryHTML() . '</div>
                                <div class="summary-div">
                                    <div class="summary">' . $summary->getHTML() . '
                                                                <div class="purchase-buttons-summary">
                                <button onclick="javascript:void(0);" class="blue_subscription" id="mpopupLink">Subscription for $' . $Entertainer->subscription_pricing . '</button>
                            </div>
                            </div>
                                </div>
                            </div>
                        </div>
					</div>
				</div>
				<!-- Link to trigger modal -->


<!-- Modal popup box -->
<div id="mpopupBox" class="mpopup">
    <!-- Modal content -->
    <div class="modal-content modal-content-container">
        <div class="modal-header">
            <h2>Subscription</h2>
        </div>
        <div class="modal-body">
        ';
        $writer = new StaysailWriter();

        $verify_form = new StaysailForm('subscribe-modal-form');
        $verify_form->setSubmit("Fan {$Entertainer->name}")
            ->setPostMethod()
            ->setJobAction(__CLASS__, 'verify')
            ->setDefaults(array('type' => 'Entertainer', 'id' => $Entertainer->id, 'payment_method_id' => StaysailIO::session('Payment_Method.id')))
            ->addField(StaysailForm::Hidden, '', 'type')
            ->addField(StaysailForm::Hidden, '', 'id');

        $payment_methods = $this->Member->getPaymentMethods();
        if (true || sizeof($payment_methods)) {
            $options = array();
            foreach ($payment_methods as $Payment_Method) {
                $options[$Payment_Method->id] = $Payment_Method->name;
            }
            $verify_form->addField(StaysailForm::Select, 'Pay $' . $Entertainer->subscription_pricing . ' With', 'payment_method_id', '', $options);
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


        $map = Maps::getGalleryMap();
        $layout = new StaysailLayout($map, $containers);
        return $layout->getHTML();
    }

    public function getWebShowPurchaseHTML($WebShow)
    {
        $writer = new StaysailWriter();

        // Is this a private show?  If so, is the correct fan logged in?
        if ($WebShow->Fan and $WebShow->Fan->id != $this->Fan->id) {
            $writer->h1('Sorry...');
            $writer->p('This is a private show in progress, and the Entertainer is not currently available.  When she becomes available, please ring her doorbell to request your own show!');
            return $writer->getHTML();
        }

        // Set redirect URL if the user chooses to add a new payment method
        $url = "?mode=Purchase&job=purchase&type=WebShow&id={$WebShow->id}";
        StaysailIO::setSession('purchase_redirect', $url);
        $minute_range = range(5, 30, 1);

        $Entertainer = $WebShow->Entertainer;

        $verify_form = new StaysailForm();
        $verify_form->setSubmit('Start the Show')
            ->setPostMethod()
            ->setJobAction(__CLASS__, 'verify')
            ->setDefaults(array('type' => 'WebShow', 'id' => $WebShow->id, 'payment_method_id' => StaysailIO::session('Payment_Method.id')))
            ->addField(StaysailForm::Select, 'Minutes to Purchase', 'minutes', 'required', $minute_range)
            ->addField(StaysailForm::Hidden, '', 'type')
            ->addField(StaysailForm::Hidden, '', 'id');

        $payment_methods = $this->Member->getPaymentMethods();
        if (sizeof($payment_methods)) {
            if ($WebShow->channel_price > 0) {
                $options = array();
                foreach ($payment_methods as $Payment_Method) {
                    $options[$Payment_Method->id] = $Payment_Method->name;
                }
            } else {
                $options = array('' => 'FREE SHOW! No Payment Required');
            }
            $verify_form->addField(StaysailForm::Select, "Pay \${$WebShow->channel_price} per minute with", 'payment_method_id', '', $options);
        } else {
            header("Location:?mode=FanHome&job=new_payment_method");
            exit;
        }
        $verify_form->addHTML(StaysailWriter::makeJobLink('Add Payment Method', 'FanHome', 'new_payment_method', '', 'spaced button'));

        $writer->h1('Join a Show')
            ->h2($Entertainer->name)
            ->start('purchase_img')
            ->addHTML($Entertainer->Member->getAvatarHTML())
            ->end('purchase_img')
            ->start('purchase_options')
            ->p("Please choose how long you want to watch the show.  Your credit card with be authorized, but not charged, for the full amount.  However, you will only be charged for the time that you watch, rounded to the nearest minute.")
            ->draw($verify_form)
            ->end('purchase_options');
        return $writer->getHTML();
    }

    public function getTipPurchaseHTML(Entertainer $Entertainer)
    {
        $writer = new StaysailWriter();
        $amount = StaysailIO::get('amount');
        // Set redirect URL if the user chooses to add a new payment method
        $url = "?mode=Purchase&job=purchase&type=Tip";
        StaysailIO::setSession('purchase_redirect', $url);

        $verify_form = new StaysailForm();
        $verify_form->setSubmit("Tip {$Entertainer->name}")
            ->setPostMethod()
            ->setJobAction(__CLASS__, 'verify')
            ->setDefaults(array('type' => 'Tip', 'id' => $Entertainer->id, 'payment_method_id' => StaysailIO::session('Payment_Method.id'), 'amount'=>$amount))
            ->addField(StaysailForm::Line, 'Tip Amount $', 'amount')
            ->addField(StaysailForm::Hidden, '', 'type')
            ->addField(StaysailForm::Hidden, '', 'id');

        $payment_methods = $this->Member->getPaymentMethods();
        if (sizeof($payment_methods)) {
            $options = array();
            foreach ($payment_methods as $Payment_Method) {
                $options[$Payment_Method->id] = $Payment_Method->name;
            }
            $verify_form->addField(StaysailForm::Select, 'Pay With', 'payment_method_id', '', $options);
        } else {
            header("Location:?mode=FanHome&job=new_payment_method");
            exit;
        }
        $verify_form->addHTML(StaysailWriter::makeJobLink('Add Payment Method', 'FanHome', 'new_payment_method', '', 'spaced button'));

        $writer->h1('Tip Entertainer')
            ->h2($Entertainer->name)
            ->draw($verify_form);
        return $writer->getHTML();
    }

    public function getMembershipPurchaseHTML()
    {
        $writer = new StaysailWriter();
        $Member = $this->Member;

        // Set redirect URL if the user chooses to add a new payment method
        $url = "?mode=Purchase&job=purchase&type=Member&id={$Member->id}";
        StaysailIO::setSession('purchase_redirect', $url);

        $verify_form = new StaysailForm();
        $verify_form->setSubmit('Pay Monthly Fee')
            ->setPostMethod()
            ->setJobAction(__CLASS__, 'verify')
            ->setDefaults(array('type' => 'Member', 'id' => $Member->id, 'payment_method_id' => StaysailIO::session('Payment_Method.id')))
            ->addField(StaysailForm::Hidden, '', 'type')
            ->addField(StaysailForm::Hidden, '', 'id');

        // If the Member has an active promo code, then activate the account as though a payment
        // has been made.  But only if there is no prior start date. Once this is done, redirect to
        // the profile screen.
        if ($Member->validatePromotionalCode()) {
            $Member->extendMembership();
            if (StaysailIO::session('inviter_entertainer_id')) {
                // If the payment comes from an invitation, subscribe to the inviter for free
                $entertainer_id = StaysailIO::session('inviter_entertainer_id');
                $Entertainer = new Entertainer($entertainer_id);
                $this->Fan->subscribeTo($Entertainer);
            }
            header("Location:?mode=FanHome");
            exit;
        }

        $payment_methods = $this->Member->getPaymentMethods();
        if (sizeof($payment_methods)) {
            $options = array();
            foreach ($payment_methods as $Payment_Method) {
                $options[$Payment_Method->id] = $Payment_Method->name;
            }
            $verify_form->addField(StaysailForm::Select, 'Pay $' . PRICE_MEMBER_FEE . ' per month with', 'payment_method_id', '', $options);
        } else {
            // If no payment method exists, go get one
            header("Location:?mode=FanHome&job=new_payment_method");
            exit;
        }
        
        header("Location:?mode=EntertainerProfile&entertainer_id=".StaysailIO::session('inviter_entertainer_id'));

//        $verify_form->addHTML(StaysailWriter::makeJobLink('Add Payment Method', 'FanHome', 'new_payment_method', '', 'spaced button'));
//
//        $writer->h1("Pay Membership Fee")
//            ->draw($verify_form);
        return $writer->getHTML();
    }

    public function validateCharge(Order $Order, $auth_only = false, $pm = false, $tips = false)
    {


//        $FanId = $_SESSION['Member.id'];
//
//        $filter = array(
//            new Filter(Filter::Match, array('Member_id' => $FanId)),
//            new Filter(Filter::Match, array('default_card' => 1))
//        );
//        $pm = $this->framework->getSingle('Payment_Method', $filter);


        if ($pm){
            $payment_method_id = $pm->id;
            $payment_gateway = StaysailIO::post('payment_gateway');
            $MemberId = $_SESSION['Member.id'];
            $Member = new Member($MemberId);
        }
        else{
            $payment_method_id = StaysailIO::post('payment_method_id');
            $payment_gateway = StaysailIO::post('payment_gateway');
            $Member = $this->Member;
        }
        $Payment_Method = new Payment_Method($payment_method_id);
        if (!$Payment_Method->belongsTo($Member)) {
            return array(false, 'The chosen payment method is not valid');
        }
        if (!$Order->belongsTo($Member)) {
            return array(false, 'The selected order is not valid');
        }
        $gateway = new PayTechTrustPaymentGateway();
        if ($Payment_Method->company && (strtolower($Payment_Method->company) == 'test')) {
            $gateway->setTestMode();
        }
        $gateway->setPaymentMethod($Payment_Method);
        $gateway->setOrder($Order);

        $response = false;
        $message = '';

        if ($tips) {
            $resultPay = $gateway->doSaleTips($auth_only);
            if ($resultPay['status']) {
                $response = true;
                $message = "<h1>Thank you!</h1><p>Thank you for your order!  Your order details are below.  <a href=\"/?mode=FanHome\">Click here to return to your home screen</a></p>";
                $message .= $Order->getHTML();
            } else {
                $response = false;
                $message = "<h1>Sorry...</h1><p id='orderError'>Your order could not be completed at this time.  Please update your payment details and try your purchase again.    <a href=\"/?mode=FanHome\">Click here to return to your home screen</a></p>";
            }
            return [$response, $message, $resultPay['response_text']];
        } else {
            if ($gateway->doSale($auth_only)) {
                $response = true;
                $message = "<h1>Thank you!</h1><p>Thank you for your order!  Your order details are below.  <a href=\"/?mode=FanHome\">Click here to return to your home screen</a></p>";
                $message .= $Order->getHTML();
            } else {
                $response = false;
                $message = "<h1>Sorry...</h1><p id='orderError'>Your order could not be completed at this time.  Please update your payment details and try your purchase again.    <a href=\"/?mode=FanHome\">Click here to return to your home screen</a></p>";
            }
            return [$response, $message];
        }

    }
}
