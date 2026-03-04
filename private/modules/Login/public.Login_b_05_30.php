<?php

//namespace App\modules\Login;
//
//use App\domain\MailSend;

require '../private/views/LoginView.php';
require '../google/vendor/autoload.php';
include_once '../private/domain/class.MailSend.php';


class Login extends StaysailPublic
{
    protected $page, $settings, $categories;
    protected $framework;

    // These are the member types that people are allowed to have.
    private $member_types;

    public function __construct($dbc = '')
    {
        $this->framework = StaysailIO::engage();
        $this->member_types = array('Fan' => 'Fan', 'Entertainer' => 'Entertainer', 'Club_Admin' => 'Club');
    }

    public function getHTML()
    {
        $job = StaysailIO::get('job');
        $map = Maps::getLoginMap();
        $left_override = '';
        $writer = new StaysailWriter();
//var_dump($job);
        switch ($job) {
            // Show tour videos
            case 'video':
                $left_override = $this->video(StaysailIO::post('area'));
                break;

            // Log in
            case 'authorize':
                $authorizeData = $this->authorize();
                $writer = $authorizeData['writer'];
                if ($authorizeData['newDesign']) {
                    $loginError = true;
                    return require '../public/home_new_index.php';
                }
                break;

            // Join
            case 'join':
                $writer = $this->joinScreen();
                break;

            case 'new_member':
                $writer = $this->newMember();
                break;

            case 'save_profile':
                $writer = $this->saveProfile();
                break;

            case 'update_payment':
                $writer = $this->postPayment();
                break;

            case 'entertainer_inquiry':
                $writer = $this->entertainerSignupInfoScreen();
                break;

            case 'club_inquiry':
                $writer = $this->clubSignupInfoScreen();
                break;

            case 'inquiry_submit':
                $writer = $this->signupInfoSubmit();
                break;

            // Forgot Password
            case 'forgot_pw':
                $writer = $this->forgotPassword();
                break;

            case 'post_forgot_pw':
                $writer = $this->sendPasswordResetRequest();
                break;

            case 'reset_password':
                $writer = $this->resetPasswordForm();
                break;

            case 'complete_reset':
                $writer = $this->completeReset();
                break;

            // Log out
            case 'signout':
                $this->logout();
                header("Location:/index.php");
                exit;

            // Policy
            case 'privacy':
                require '../private/views/PrivacyPolicyView.php';
                $privacy = new PrivacyPolicyView();
                $left_override = $privacy->getHTML();
                //$writer = $this->joinScreen();
                break;

            case 'contact':
                require '../private/views/ContactUsView.php';
                $contact = new ContactUsView();
                $left_override = $contact->getHTML();
                //$writer = $this->joinScreen();
                break;

            // A fan wants to search for an entertainer
            case 'find':
                $left_override = $this->findEntertainer();
//                $left_override = $this->findEntertainerInClub();
                break;

            case 'terms':
                require '../private/views/TermsOfServiceView.php';
                $terms = new TermsOfServiceView();
                $left_override = $terms->getHTML();
                break;

            case 'standard':
                require '../private/views/StandardContract.php';
                $standard = new StandardContract();
                $left_override = $standard->getHTML();
                break;

            case 'login_page':
                $writer = $this->preJoinScreen();
                break;

            case 'join-google':
                $writer = $this->checkGoogleAccount();
//                $writer = $this->createGoogleLogin();
                break;

            case 'create-google':
                $type = StaysailIO::get('type');
                $writer = $this->createGoogleLogin($type);
                break;

            default:
                return require '../public/home_new_index.php';
        }
        $login = new LoginView();
        $footer = new FooterView();

        if (!StaysailIO::session('inviter_entertainer_id') and !$left_override) {
            if (StaysailIO::get('e')) {
                $type = 'entertainer';
            } elseif (StaysailIO::get('c')) {
                $type = 'club';
            } else {
                $type = 'fan';
            }
            $left_override = $this->video($type);
        }

        $containers = array(new StaysailContainer('H', 'header', $login->getHeaderHTML()),
            new StaysailContainer('F', 'footer', $footer->getHTML())
            /*new StaysailContainer('L', 'left', ($left_override ? $left_override : $login->getHTML())),
            new StaysailContainer('R', 'right', $writer->getHTML()),*/
        );
        if ($job == 'privacy' || $job == 'contact' || $job == 'terms' || $job == 'standard') {
            $map = Maps::getPrivacyMap();
            $containers[] = new StaysailContainer('C', 'privacy_content', $left_override);
        } else {
            $containers[] = new StaysailContainer('L', 'left', ($left_override ? $left_override : $login->getHTML()));
            $containers[] = new StaysailContainer('R', 'right', $writer->getHTML());
        }
        $layout = new StaysailLayout($map, $containers);
        return $layout->getHTML();
    }

    private function loginUser($Member)
    {
        // OK to log in
        $Member->last_login = date('Y-m-d H:i:s');
        $Member->save();
        $Member->registerSession();
        session_regenerate_id(false);

        // Find out what kind of account this Member has
        $account_type = $Member->getAccountType();
        if (!$account_type) {
            $writer->h1("Sorry...")
                ->p("Your Membership is not associated with an Entertainer, Fan, or Club account.")
                ->p("Please contact the administrator.");
            return $writer;
        }

        if ($account_type == 'Entertainer') {
            // If an entertainer has logged in, clear out any existing web shows
            $Entertainer = $Member->getAccountOfType('Entertainer');
            $Entertainer->clearPrivateRequests();
            $Entertainer->endWebShows();
            StaysailIO::setSession('MemberIsCurrent', true);
        }

        if ($account_type == 'Fan') {
            // If a fan has logged in, notify entertainers that he is online
            $Fan = $Member->getAccountOfType('Fan');
            if (!$Fan->isSMSException()) {
                $Fan->notifyEntertainersOfOnline();
            }
        }

        // If the user is authorized, head out to the appropriate module
        header("Location:?mode={$account_type}Home&w=1");
        exit;
    }

    private function authorize()
    {
        $writer = new StaysailWriter(__CLASS__);
        $username = trim(StaysailIO::post('username'));
        $password = trim(StaysailIO::post('password'));
        $newDesign = StaysailIO::post('new_design');
    //	$filter = new Filter(Filter::Match, array('email' => $username, 'is_deleted' => 0));
        $filter = new Filter(Filter::Match, array('email' => $username));
        $members = $this->framework->getSubset('Member', $filter);
//        var_dump($username);
//        echo "<br>";
//        var_dump($password);
//        echo "<br>";
//        var_dump($newDesign);
//        echo "<br>";
//        var_dump($filter);
//        echo "<br>";
//        echo "<br>";
//        echo "<pre>";
//        var_dump($members);
//        die();
        if (sizeof($members)) {
            $Member = array_pop($members);
            if ($Member->passwordMatch($password)) {
                $this->loginUser($Member);
            }
        }

        $writer->h1("Sorry...")
            ->p("The username/password combination that you entered is not valid.  Please try again.");
        $writer->draw($this->loginScreen());
        return ['newDesign' => $newDesign, 'writer' => $writer];
    }

    private function loginScreen()
    {
        $writer = new StaysailWriter(__CLASS__);

        $login = new StaysailForm('form');
        $login->setPostMethod()
            ->setJobAction(__CLASS__, 'authorize')
            ->addField(StaysailForm::Line, 'Email Address', 'username', 'required')
            ->addField(StaysailForm::Password, 'Password', 'password', 'required')
            ->setSubmit('Log In');

        $writer->draw($login)
            ->p(StaysailWriter::makeJobLink('Wish to join?', __CLASS__, 'join'))
            ->p(StaysailWriter::makeJobLink('Forgot your password?', __CLASS__, 'forgot_pw'));

        return $writer;
    }

    private function redirectGoogle()
    {
        return require '../redirect.php';
    }

    private function preJoinScreen()
    {
        $tos_link = StaysailWriter::makeLink('Terms of Use', '?mode=Login&job=terms', '', null, '_blank');

        $writer = new StaysailWriter(__CLASS__);
        $writer->addHTML('<div class="login-first">');
        $writer->h1('Sign Up Now', 'join');
        //$writer->h2("It only takes a moment and you'll be happy you did!", 'join');

        $fan_link = StaysailWriter::MakeJobLink('Fan', __CLASS__, 'join', '', 'fan_open');
        $entertainer_link = StaysailWriter::makeJobLink('Creator', __CLASS__, 'join&e=1');


        //$writer->h1("I am a...");
        $writer->start('buttons');
        $writer->span($fan_link);
        $writer->span($entertainer_link);
        //$writer->span($club_link);
        $writer->addHTML('&nbsp;');
        $writer->end();

        return $writer;

    }

    private function checkGoogleAccount()
    {
        if (!empty($_SESSION["google_user"])) {
            $userData = $_SESSION["google_user"];
            $writer = new StaysailWriter(__CLASS__);

            $filters = array(new Filter(Filter::Match, array('google_id' => $userData['id'])));
            $Previous_Member_With_Google_id = $this->framework->getSingle('Member', $filters);
            if ($Previous_Member_With_Google_id) {
                $this->loginUser($Previous_Member_With_Google_id);
            }
            else{
                header("Location:/index.php?mode=Login&job=join&showModal=1");
//                header("Location:/index.php?mode=Login&job=create-google");
            }

            return $writer;
            // now you can use this profile info to create account in your website and make user logged in.
        } else {
            header("Location:/index.php?mode=Login&job=join");
        }
    }

    private function createGoogleLogin($type)
    {
        if (!empty($_SESSION["google_user"])) {
            $userData = $_SESSION["google_user"];
            $writer = new StaysailWriter(__CLASS__);
            $filters = array(new Filter(Filter::Match, array('email' => $userData['email'])));
            $Previous_Member_With_Email = $this->framework->getSingle('Member', $filters);
            $filters = array(new Filter(Filter::Match, array('name' => $userData['name'])));
            $Previous_Member_With_Name = $this->framework->getSingle('Member', $filters);
            if ($Previous_Member_With_Email || $Previous_Member_With_Name) {
                $errorMsg = "A user with the specified email address already exists.  Please use a unique email address.  If you think you already have an account, click the Forgot Password link.";
                if ($Previous_Member_With_Name) {
                    $errorMsg = "A user with the specified Screen Name already exists.  Please use a unique Screen Name.  If you think you already have an account, click the Forgot Password link.";
                }
                $writer = new StaysailWriter('box-form');
                $writer->h1("Sorry...", 'join')
                    ->p($errorMsg)
                    ->p(StaysailWriter::makeJobLink('Wish to join?', __CLASS__, 'join'))
                    ->p(StaysailWriter::makeJobLink('Forgot your password?', __CLASS__, 'forgot_pw'));
                return $writer;
            }
            $Member = new Member();
            $Member->update([
                'name' => explode(' ', $userData['name'])[0],
                'first_name' => explode(' ', $userData['name'])[0],
                'last_name' => isset(explode(' ', $userData['name'])[1]) ? explode(' ', $userData['name'])[1] : '',
                'email' => $userData['email'],
                'google_id' => $userData['id'],
                'phone' => '-',
                'created_at' => date("Y/m/d")
            ]);
            if ($Member->hasValidInfo()) {
                $Member->save();
                $_SESSION["google_user"] = [];
                $account = $Member->makeAccountOfType($type);
                $account->registerSession();
                $form = $account->getProfileForm(__CLASS__, 'save_profile');
                $writer->addHTML('<div class="login-first box-form">');
                $writer->h1('Step 2', 'join');
                $writer->draw($form);
                StaysailIO::setSession('Member.id', $Member->id);

                if ($type == 'Entertainer') {
                    $account->signup_club_name = StaysailIO::post('club_name');
                    $account->save();
                    $message = <<<__END__
                        A new Entertainer ({$Member->first_name} {$Member->last_name}) has joined Local Strip Fan.
__END__;
                    if ($_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
                        mail('support@yourfanslive.com', "New Entertainer: {$account->name}", $message);
                    } else {
                        mail('jjustian@gmail.com', "New LOCALHOST Entertainer: {$account->name}", $message);
                    }
                }
                return $writer;
            } else {
                return $this->joinScreen();
            }
        }
        else {
            header("Location:/index.php?mode=Login&job=join");
        }
    }

    private function joinScreen()
    {
        $tos_link = StaysailWriter::makeLink('Terms of Use', '?mode=Login&job=terms', '', null, '_blank');
        $writer = new StaysailWriter(__CLASS__);
        $writer->addHTML('<div class="join_page_class box-form">');

        // init configuration
        $clientID = '61534181333-4q43dngr7bj5su6knk7ck030p7uotn26.apps.googleusercontent.com';
        $clientSecret = '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';
        $redirectUri = 'https://stage.yourfanslive.com/redirect.php';

// create Client Request to access Google API
        $client = new Google_Client();
        $client->setClientId($clientID);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);
        $client->addScope("email");
        $client->addScope("profile");

//        $writer->addHTML("<a href='".$client->createAuthUrl()."'>Google Login</a>");
        $showModal = '';
        if (isset($_GET["showModal"])){
            $showModal = 'showModal';
        }
        $writer->addHTML("
            <div id='myModalGoogle' class='modalGoogle $showModal'>
              <div class='modal-content-google'>
              <div class='modal-header-google'>
                <h2>Choose Account Type</h2>
              </div>
                <span class='close'>&times;</span>
                <div class='modal_box'>
                    <div id='adbox'>
                        <div class='adbox1'>
                            <a href='?mode=Login&job=create-google&type=Fan'>FAN1</a>
                        </div>
                        <div class='adbox2'>
                            <a href='?mode=Login&job=create-google&type=Entertainer'>CREATOR</a>
                        </div>
                    </div>
                </div>
              </div>
            </div>");

         $writer->addHTML("
                            <div class='switch_container '>
                                <span>FAN</span>
                                <label class=\"switch\">
                                  <input type=\"checkbox\" class=\"switch_account\">
                                  <span class=\"slider round\"></span>
                                </label>
                                <span>CREATOR</span>
                            </div>");

        $type = StaysailIO::get('e') ? 'Entertainer' : 'Fan';
        if ($type == 'Entertainer') {
            $writer->h1('Sign Up Now as Creator', 'join');
        } else {
            $writer->h1('Sign Up Now as Fan', 'join');
        }

        //$writer->h2("It only takes a moment and you'll be happy you did!", 'join');

        if (StaysailIO::get('c')) {
            $type = 'Club_Admin';
        }
        if ($type == 'Entertainer') {
            $promo = 'Club Code';
            $promo_field = 'club_name';
            $promo_class = 'required';
        } elseif ($type == 'Club_Admin') {
            $promo = $promo_field = $promo_class = '';
        } else {
            $promo = 'Promotional Code';
            $promo_field = 'promo';
            $promo_class = '';
        }

        if ($type != 'Entertainer' and $type != 'Club_Admin') {
            $other_link = StaysailWriter::makeJobLink("click here for information on signing up", __CLASS__, 'inquiry');
            //$writer->h1('Step 1');
            $writer->p("This is for FAN sign up only. If you are a Creator {$other_link}.");
        }

        $providers = SMSSender::getProviders();
        foreach ($providers as $key => $value) {
            $providers[$key] = $key;
        }

        $signup = new StaysailForm('join form');
        $signup->setPostMethod()
            ->setJobAction(__CLASS__, 'new_member')
            ->setSubmit('Sign Up Now!')
            ->setDefaults(array('type' => $type))
            ->addField(StaysailForm::Line, '', 'first_name', 'required','','','','','First Name' )
            ->addField(StaysailForm::Line, '', 'last_name', 'required','','','','','Last Name' )
//            ->addField(StaysailForm::Line, 'Last Name', 'last_name', 'required')
            ->addField(StaysailForm::Line, '', 'phone', 'nullable','','','','', 'Phone Number')
            ->addHTML('<small class="field_phone_small ">
                        Phone number is used for communication with the creator through a 3rd party for security.
                       </small>')
//            ->addField(StaysailForm::Boolean, "I do not want to receive text messages from the creators", 'phone_verify', 'reg_phone require-choice')
//            ->addField(StaysailForm::Boolean, "I do not want to receive text messages from the creators", 'sms_optout', 'reg_phone')
//            ->addField(StaysailForm::Line, '', 'referral_email', 'nullable referral_email','','','','', 'Referral Email')
//            ->addHTML('<small class="field_referral_email_small ">
//                        If you were referred put who referred you here.
//                       </small>')
            ->addField(StaysailForm::Line, '', 'name', 'required','','','','', 'Screen Name')
            ->addField(StaysailForm::Line, '', 'email', 'required email','','','','', 'Email')
//    	       ->addField(StaysailForm::Select, 'Cell Provider', 'cell_provider', 'required', $providers)
            ->addField(StaysailForm::Password, '', 'password', 'required password','','','','', 'Password')
            ->addField(StaysailForm::Password, '', 'password_2', 'require-match:password','','','','', 'Repeat Password');
//              ->addField(StaysailForm::Line, 'Creator to Fan', 'creator_to_fan', 'creator_to_fan required','','','(Stage Name)');
        if ($promo) {
            //$signup->addField(StaysailForm::Line, $promo, $promo_field, $promo_class);
        }
        $signup->addField(StaysailForm::Boolean, "I have read and agree to the {$tos_link}", 'tos', 'require-choice')
            ->addField(StaysailForm::Boolean, "I am at least 18 years of age", 'age_verify', 'require-choice')
            ->addField(StaysailForm::Hidden, 'type', 'type')
            ->addHTML('<p>This website is strictly for adults only! <b>This website contains sexually explicit content.</b> You must be at least 18 years of age to enter this website.</p>');

        $writer->draw($signup);
        $writer->addHTML('<div class="social_btn_container">');
       // $writer->addHTML("<a href='" . $client->createAuthUrl() . "' class='google_btn social_btn'>Google Login</a>");
        //$writer->addHTML("<a href='#' class='twitter_btn social_btn'>Twitter Login</a>");
        $writer->addHTML("<a href='/' class='login_btn social_btn'>Login</a>");
        $writer->addHTML('</div>');
        $writer->addHTML('</div>');
        $writer->addHTML('<div class="bottom-section">');

        if ($type == 'Entertainer') {
            $club_link = StaysailWriter::makeJobLink('Fan', __CLASS__, 'join');
        } else {
            $club_link = StaysailWriter::makeJobLink('Creator', __CLASS__, 'join&e=1');
        }
        //$club_link = StaysailWriter::makeJobLink('Content Creator', __CLASS__, 'club_inquiry');
//        $writer->addHTML('<h1 class="join"> Sign Up as</h1>');
//        $writer->start('buttons');
//        $writer->span($club_link);
        $writer->end();
        $writer->addHTML('</div>');
        return $writer;
    }

    private function newMember()
    {
        $writer = new StaysailWriter(__CLASS__);
//        if (StaysailIO::post('promo')) {
//            StaysailIO::setSession('promo', StaysailIO::post('promo'));
//        }
        $emailReg = strtolower(StaysailIO::post('email'));
        $filters = array(new Filter(Filter::Match, array('email' => $emailReg)));
        $Previous_Member_With_Email = $this->framework->getSingle('Member', $filters);

        $filters = array(new Filter(Filter::Match, array('name' => StaysailIO::post('name'))));
        $Previous_Member_With_Name = $this->framework->getSingle('Member', $filters);

        if ($Previous_Member_With_Email || $Previous_Member_With_Name) {
            $errorMsg = "A user with the specified email address already exists.  Please use a unique email address.  If you think you already have an account, click the Forgot Password link.";
            if ($Previous_Member_With_Name) {
                $errorMsg = "A user with the specified Screen Name already exists.  Please use a unique Screen Name.  If you think you already have an account, click the Forgot Password link.";
            }
            $writer = new StaysailWriter('box-form');
            $writer->h1("Sorry...", 'join')
                ->p($errorMsg)
                ->p(StaysailWriter::makeJobLink('Wish to join?', __CLASS__, 'join'))
                ->p(StaysailWriter::makeJobLink('Forgot your password?', __CLASS__, 'forgot_pw'));
            return $writer;
        }
        $Member = new Member();
        $fields = array('first_name', 'last_name', 'email', 'phone', 'sms_optout', 'name', 'referral_email', 'created_at');
        $Member->updateFrom($fields);

        if ($Member->hasValidInfo()) {
//            var_dump(1111);
//            //TODO REFACTOR !!!!!
//            $Member->email_verified = 0;
//            $Member->login_lockout = null;
//            $Member->online_time = null;
            $Member->last_login = date("Y-m-d H:i:s");
            $Member->google_id = 1;
//            $Member->active_time = null;
//            $Member->auto_renew = null;
//            $Member->is_deleted = 0;
//            $Member->sms_optout = null;
//            $Member->expire_time = null;
//            echo "<pre>";
//            var_dump($Member);
//            die();

            $Member->created_at = date("Y/m/d");
            $Member->save();
            $Member->setPassword(StaysailIO::post('password'))->save();

//            $person = null;
            $type = StaysailIO::post('type');
            foreach ($this->member_types as $valid_type => $label) {
                if ($type == $valid_type) {
                    $account = $Member->makeAccountOfType($type);
//                    echo "<pre>";
//                    var_dump($account);
//                    var_dump('id');
//                    var_dump($account->id);
                    $account->registerSession();
//                    var_dump($_SESSION);
                   $form = $account->getProfileForm(__CLASS__, 'save_profile');
                    $writer->addHTML('<div class="login-first box-form">');
                    $writer->h1('Step 2', 'join');
                    $writer->draw($form);
                    StaysailIO::setSession('Member.id', $Member->id);

                    if ($type == 'Entertainer') {
                        $account->order_list = 1000;
                        $account->signup_club_name = StaysailIO::post('club_name');
                        $account->save();

                        $message = <<<__END__
A new Entertainer ({$Member->first_name} {$Member->last_name}) has joined Local Strip Fan.
__END__;
                        if ($_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
                            mail('support@yourfanslive.com', "New Entertainer: {$account->name}", $message);
                        } else {
                            mail('jjustian@gmail.com', "New LOCALHOST Entertainer: {$account->name}", $message);
                        }

                    }
//TODO
//                    die();
                    $email = $Member->email;
                    $domain = $_SERVER['SERVER_NAME'];
                    $activation_link = "https://$domain/?mode=PublicLive&job=activate&email=$email";
                    $subject = 'Please activate your account';
                    $message = "Hi,Please click the following link to activate your account: <a href='$activation_link'>here</a>";
                    $MailSend = new MailSend($Member);
                    $MailSend->send($email, $subject, $message, 1);
                    return $writer;
                }
            }
        } else {
            return $this->joinScreen();
        }

        return $writer;
    }

    private function saveProfile()
    {
        $entertainerId = StaysailIO::session('inviter_entertainer_id');
        $account_type = StaysailIO::session('account_type');
        $account_entity_id = StaysailIO::session('account_entity_id');
        if (!$account_type and StaysailIO::session('Member.id')) {
            $Member = new Member(StaysailIO::session('Member.id'));
            if ($Member) {
                $account_type = $Member->getAccountType();
                $account_entity_id = $Member->getAccountOfType($account_type)->id;
            }
        }
        if ($account_type && $account_entity_id) {
            if (in_array($account_type, array_keys($this->member_types))) {
                $account = new $account_type($account_entity_id);
                $account->saveProfile();

                if ($entertainerId) {
                    header("Location:/?mode=EntertainerProfile&entertainer_id=".$entertainerId);
                    exit;
                }

                switch ($account_type) {
                    case 'Fan':
                        header("Location:/index.php?mode=FanHome&signup=1");
                        exit;

                    case 'Entertainer':
                        header("Location:/index.php?mode=EntertainerProfile&job=add_club");
                        exit;
                }

                exit;
            }
        }

        $writer = new StaysailWriter('box-form');
        $writer->h1("Sorry...", 'join')
            ->p("There was some sort of problem with your order.  Your profile could not be saved.");
        return $writer;
    }

    private function forgotPassword()
    {
        $writer = new StaysailWriter(__CLASS__);
        $writer->addHTML('<div class="box-form">');
        $request = new StaysailForm('join form');
        $request->setSubmit('Send Password Reset Request')
            ->setPostMethod()
            ->setJobAction(__CLASS__, 'post_forgot_pw')
            ->addField(StaysailForm::Line, 'Email Address', 'email', 'required');
        $writer->h1('Forgot Password', 'join')
            ->h2("We're sorry to hear that you've forgotten your password.  Please enter your email address below,
			   	    and we'll send you a link which will allow you to reset your password.", 'join')
            ->draw($request);
        return $writer;

    }

    private function sendPasswordResetRequest()
    {
        $writer = new StaysailWriter(__CLASS__);
        $email = StaysailIO::post('email');
        $members = $this->framework->getSubset('Member', new Filter(Filter::Match, array('email' => $email, 'is_deleted' => 0)));

        if (sizeof($members)) {
            $Member = $members[0];

            $reset_code = substr(md5($Member->id), 0, 16) . substr(md5(uniqid()), 0, 16);
            $Member->password_reset_code = $reset_code;
            $Member->save();
            $domain = $_SERVER['SERVER_NAME'];
            $reset_link = "https://$domain/index.php?mode=Login&job=reset_password&pwauth=$reset_code";
            $message = <<<__END__
Greetings,

Thank you for your password reset request. To reset your password, please paste the following URL into your web browser's
address bar:
    $reset_link
You will be asked to change your password on this screen, and you will be able to log in immediately with your new password.

Please note that this link with expire in a couple hours. If it does not work, please click the "Forgot Password" link again.

If you did not request this password change, then disregard this message.

Regards,
Local City Scene Management

__END__;

            $subject = 'Password Reset';
            $MailSend = new MailSend($Member);
            $MailSend->send($email, $subject, $message);
            $writer->addHTML('<div class="box-form">');
            $writer->h1('Thank you!  Please check your email for further instructions', 'join');
            $writer->draw($this->loginScreen());
        } else {
            $writer->addHTML('<div class="box-form">');
            $writer->h1('Sorry...');
            $writer->p("We could not find that email address.");
            $writer->draw($this->forgotPassword());
        }

        return $writer;
    }

    private function resetPasswordForm()
    {
        $writer = new StaysailWriter(__CLASS__);
        $auth = StaysailIO::get('pwauth');
        if (!$auth) {
            $auth = StaysailIO::session('pwauth');
        }
        if (!$auth) {
            $writer->h1('Sorry...');
            $writer->p('Your reset authorization was not provided.  Please try again:');
            $writer->draw($this->forgotPassword());
            return $writer;
        }
        $members = $this->framework->getSubset('Member', new Filter(Filter::Match, array('password_reset_code' => $auth)));

        if (sizeof($members)) {
            StaysailIO::setSession('pwauth', $auth);
            $Member = $members[0];
            $reset_form = new StaysailForm();
            $reset_form->setPostMethod()
                ->setJobAction(__CLASS__, 'complete_reset')
                ->setSubmit('Reset Your Password')
                ->addField(StaysailForm::Password, 'Password', 'password', 'required password')
                ->addField(StaysailForm::Password, 'Repeat Password', 'password_2', 'require-match:password');
            $writer->h1('Reset Your Password')
                ->draw($reset_form);
        } else {
            $writer->h1('Sorry...');
            $writer->p('Your reset authorization is not valid.  Please try again:');
            $writer->draw($this->forgotPassword());
        }
        return $writer;
    }

    private function completeReset()
    {
        $writer = new StaysailWriter(__CLASS__);
        $auth = StaysailIO::session('pwauth');
        if (!$auth) {
            $writer->h1('Sorry...');
            $writer->p('Your reset authorization was not provided.  Please try again:');
            $writer->draw($this->forgotPassword());
            return $writer;
        }
        $members = $this->framework->getSubset('Member', new Filter(Filter::Match, array('password_reset_code' => $auth)));
        if (sizeof($members)) {
            StaysailIO::setSession('pwauth', null);
            $Member = $members[0];
            $password = StaysailIO::post('password');
            if ($password) {
                $Member->setPassword($password)->save();
                $writer->h1('Password Reset Successful!');
                $writer->draw($this->joinScreen());
            } else {
                $writer->h1('Sorry...')
                    ->p('You did not provide a valid password.  Please try again:')
                    ->draw($this->resetPasswordForm());
            }
        } else {
            $writer->h1('Sorry...');
            $writer->p('Your reset authorization is not valid.  Please try again:');
            $writer->draw($this->forgotPassword());
        }
        return $writer;

    }

    private function postPayment()
    {
        $account_type = StaysailIO::session('account_type');
        $account_entity_id = StaysailIO::session('account_entity_id');
        if ($account_type and $account_entity_id) {
            if (in_array($account_type, array_keys($this->member_types))) {
                $account = new $account_type($account_entity_id);

                // Process the payment.  If successful, set the logged-in member
                StaysailIO::setSession('Member.id', $account->Member->id);
                $account->Member->active_time = date('Y-m-d H:i:s');
                $account->Member->expireInDays(365);
                $account->Member->save();

                $writer = new StaysailWriter(__CLASS__);
                $click_here = StaysailWriter::makeJobLink("click here", $account_type . "Home");
                $writer->h1("Thank you!")
                    ->p("Your fake payment was processed successfully.")
                    ->p("Please {$click_here} to get started with your account.");
                return $writer;
            }
        }
        $writer = new StaysailWriter();
        $writer->h1("Sorry...")
            ->p("There was some sort of problem with your payment.  Your profile could not be saved.");
        return $writer;
    }

    private function video($area)
    {
        $autostart = strstr(StaysailIO::get('job'), 'inquiry') ? 'false' : 'true';

        $html = '
    	<iframe style="display:none;" width="560" height="315" src="https://www.youtube.com/embed/hSSnbopeOcw" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>';

        if ($area == 'fan') {
            $html .= <<<__END__
        <div class="left-logo">
            <a href="/">
                <img src="/site_img/FullLogo.png">
            </a>
        </div>
		<div class="intro_text" style="display:none">
		<h1>The sexiest social media site on the planet</h1>
		</div>
__END__;
        }

        return $html;
    }

    private function logout()
    {
        session_destroy();
        $_SESSION = array();
        return;
    }

    private function findEntertainerInClub()
    {
        $club_id = StaysailIO::session('inviter_club_id');
        if (!$club_id) {
            return '';
        }

        $Club = new Club($club_id);

        $writer = new StaysailWriter();
        $writer->h1("I was invited to join by {$Club->name}");

        $positions = array('Managers' => 'Manager', 'Bartenders' => 'Bartender',
            'Waitstaff' => 'Waitstaff', 'Entertainers' => 'Entertainer');
        foreach ($positions as $label => $position) {
            $entertainers = $Club->getEntertainers($position);
            if (sizeof($entertainers)) {
                $writer->h2($label);
                foreach ($entertainers as $Entertainer) {
                    if (!$Entertainer->private) {
                        $avatar = $Entertainer->Member->getAvatarHTML(Member::AVATAR_LITTLE, false);
                        //$link = StaysailWriter::makeLink($avatar, "/{$Club->account_number}/{$Entertainer->fan_url}");
                        $link = StaysailWriter::makeLink($avatar, "/{$Entertainer->fan_url}");
                        $writer->start('entertainer_selection')
                            ->addHTML("{$link}&nbsp;{$Entertainer->name}")
                            ->end();
                    }
                }
                $writer->addHTML('<br style="clear:both"/>');
            }
        }
        return $writer->getHTML();
    }

    private function findEntertainer()
    {
        $stage_name = StaysailIO::session('stage_name');
        if (!$stage_name) {
            return '';
        }
        $writer = new StaysailWriter();
        $enter = new Entertainer();
        $writer->h1("I was invited to join by {$stage_name}");
        $positions = array('Managers' => 'Manager', 'Bartenders' => 'Bartender',
            'Waitstaff' => 'Waitstaff', 'Entertainers' => 'Entertainer');
        foreach ($positions as $label => $position) {
            $entertainers = $enter->getEntertainers($position, $stage_name);
            if (sizeof($entertainers)) {
                $writer->h2($label);
                foreach ($entertainers as $Entertainer) {
                    if (!$Entertainer->private) {
                        $avatar = $Entertainer->Member->getAvatarHTML(Member::AVATAR_LITTLE, false);
                        //$link = StaysailWriter::makeLink($avatar, "/{$Club->account_number}/{$Entertainer->fan_url}");
                        $link = StaysailWriter::makeLink($avatar, "/{$Entertainer->fan_url}");
                        $writer->start('entertainer_selection')
                            ->addHTML("{$link}&nbsp;{$Entertainer->name}")
                            ->end();
                    }
                }
                $writer->addHTML('<br style="clear:both"/>');
            }
        }
        return $writer->getHTML();
    }

    private function entertainerSignupInfoScreen()
    {
        $writer = new StaysailWriter();
        $writer->addHTML('<div class="enquiry_page_class box-form">');
        $writer->h1('Creator Inquiry', 'join');
        $writer->h2('We appreciate your interest in Local Strip Fan!  So that we may contact you with information about joining, please fill out the information below.', 'join');

        $form = new StaysailForm('join form');
        $Entertainer = new Entertainer();
        $form->setSubmit('Send My Information')
            ->setPostMethod()
            ->setDefaults(array('type' => 'Entertainer'))
            ->setJobAction(__CLASS__, 'inquiry_submit')
            ->addField(StaysailForm::Hidden, 'Type', 'type')
            ->addField(StaysailForm::Select, 'Position', 'type', 'required', $Entertainer->position_Options())
            ->addField(StaysailForm::Line, 'Your Name', 'name', 'required')
            ->addField(StaysailForm::Line, 'Contact Phone Number', 'phone', 'required')
            ->addField(StaysailForm::Line, 'Email', 'email', 'required email')
            ->addField(StaysailForm::Line, 'Club Name', 'club_name', 'required')
            ->addField(StaysailForm::Line, 'Club Code (if available)', 'club_code')
            ->addField(StaysailForm::Line, 'Club Phone Number', 'club_phone', 'required')
            ->addField(StaysailForm::Line, 'Manager Name', 'manager_name', 'required')
            ->addField(StaysailForm::Line, 'How did you hear about us? (website, club, other person)', 'source');

        $writer->draw($form);
        $writer->addHTML('</div>');
        return $writer;
    }

    private function clubSignupInfoScreen()
    {
        $writer = new StaysailWriter();
        $writer->addHTML('<div class="club_page_class box-form">');
        $writer->h1('Content Creator Request', 'join');
        $writer->h2('A verification process will take from 24-48 hours to approve an Entertainer to Content Creator', 'join');

        $form = new StaysailForm('join form');
        $form->setSubmit('Send My Information')
            ->setPostMethod()
            ->setDefaults(array('type' => 'Club'))
            ->setJobAction(__CLASS__, 'inquiry_submit')
            ->addField(StaysailForm::Hidden, 'Type', 'type')
            ->addField(StaysailForm::Line, 'First Name', 'name', 'required')
            ->addField(StaysailForm::Line, 'Last Name', 'role', 'required')
            ->addField(StaysailForm::Line, 'Phone Number', 'phone', 'required')
            ->addField(StaysailForm::Line, 'Email', 'email', 'required email')
            ->addField(StaysailForm::Line, 'Instagram', 'club_name', 'required')
            ->addField(StaysailForm::Line, 'Snap Chat', 'club_address', 'required-if:type')
            ->addField(StaysailForm::Line, 'Twitter', 'club_phone', 'required')
            ->addField(StaysailForm::Line, 'Past Creator site', 'source');

        $writer->draw($form);
        $writer->addHTML('</div>');
        return $writer;
    }

    private function signupInfoSubmit()
    {
        $writer = new StaysailWriter('box-form');
        $writer->h1('Thank you!  You\'re almost done!', 'red')
            ->p('Look for an email from support@yourfanslive.com for instructions on completing your sign up.', 'green big')
            ->p('If you do not receive this email, please check your spam folder.', 'red')
            ->p('Again, we appreciate your interest and your time.  A Local Strip Fan representative will be contacting you shortly with instructions on completing your sign-up!');

        $type = StaysailIO::post('type');
        $name = trim(StaysailIO::post('name'));
        if (!$name) {
            return $writer;
        }
        if ($type == 'Entertainer') {
            $fields = array('name', 'position', 'phone', 'email', 'club_code', 'club_name', 'club_phone',
                'manager_name', 'source');
        } else {
            $fields = array('name', 'role', 'phone', 'email', 'club_name', 'club_address', 'club_phone', 'source');
        }

        $message = "{$type} Sign Up Inquiry\n\n";
        $message .= "{$name} is interested in signing up as a {$type} for Local Strip Fan.\n\n";
        foreach ($fields as $fieldname) {
            $label = ucwords(str_replace('_', ' ', $fieldname));
            $value = StaysailIO::post($fieldname);
            $message .= "{$label} .......... {$value}\n";
        }

        mail('support@yourfanslive.com,jjustian@gmail.com', "{$type} Signup Inquiry", $message);

        if ($type == 'Entertainer') {
            $writer->p("Please check your email for your confirmation message!");

            $confirm = <<<__END__

Welcome {$name} to Local Strip Fan.com.  You can sign up at www.localstripfan.com/entertainer and it will walk you through the steps.  At the sign up process you want to put your club code in.  If you do not have a sign up code, one will be sent you after the sign up process.  Below I am attaching two videos,  one video walks you through the sign up process and the other is a 15 minute video that shows you all the possible income streams.  We are the only social network site that is secure and private for both you and your best fans.  With Local Strip Fan.com you receive 50% of all revenue.  We also support all aspect of your business with the Club Owner 10% Club Manager 5% and House Moms 3%.  If your club is not a member,  we will reach out to the manager you put down on the email to explain the benefits our site brings to everyone so we can start paying the club %.  Remember, it is free for the girls and the clubs.

If you have any questions.  Don't hesitate to call me on my cell 312-296-2551

http://youtu.be/GPOjPIKvf0A  Sign up Video

http://youtu.be/7jPaUZfvJrQ   15 min income video

Bill
Member Services

__END__;

            $email = StaysailIO::post('email');
            $email = preg_replace('/[\n,;]/', '', $email);

//            mail("support@yourfanslive.com,{$email}", "Local Strip Entertainer Confirmation", $confirm, 'From:support@yourfanslive.com');

            $subject = 'Local Strip Entertainer Confirmation';
            $MailSend = new MailSend($this->Member);
            $MailSend->send($email, $subject, $message);
        }

        if ($type == 'Club') {
            $writer->p("Please check your email for your confirmation message!");

            $confirm = <<<__END__

Welcome {$name} to Local Strip Fan.com!

You are now under review for “Content Creator” status.  This will take 24-48 hours.

Expect me to contact you soon. If you have any questions.  Don't hesitate to call me on my cell 312-296-2551.

Bill
Member Services

__END__;

            $email = StaysailIO::post('email');
            $email = preg_replace('/[\n,;]/', '', $email);

            mail("support@yourfanslive.com,{$email}", "Local Strip Club Confirmation", $confirm, 'From:support@yourfanslive.com');
        }

        return $writer;
    }

    public function publicLive()
    {
        $writer = new StaysailWriter();
        $writer->h1('Public Live');

        return $writer;
    }
}
