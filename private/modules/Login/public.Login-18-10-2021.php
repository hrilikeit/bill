<?php

require '../private/views/LoginView.php';

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
    	
    	switch ($job)
    	{
    		// Show tour videos
    		case 'video':
    			$left_override = $this->video(StaysailIO::post('area'));
    			break;
    		
    		// Log in
    		case 'authorize':
    			$writer = $this->authorize();
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
    			$writer = $this ->sendPasswordResetRequest();
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
    			$writer = $this->joinScreen();
    			break;
    			
    		case 'contact':
    			require '../private/views/ContactUsView.php';
    			$contact = new ContactUsView();
    			$left_override = $contact->getHTML();
    			$writer = $this->joinScreen();
    			break;
    			
    		// A fan wants to search for an entertainer
    		case 'find':
    			$left_override = $this->findEntertainerInClub();
    			break;
    		
    		default:
    			$writer = $this->preJoinScreen();
    	}
    	    	
    	$login  = new LoginView();
    	$footer = new FooterView();
    	
    	if (!StaysailIO::session('inviter_entertainer_id') and !$left_override) {
    		if (StaysailIO::get('e')) {$type = 'entertainer';}
    		elseif (StaysailIO::get('c')) {$type = 'club';}
    		else {$type = 'fan';}
    		$left_override = $this->video($type);
    	}
    	
		$containers = array(new StaysailContainer('H', 'header', $login->getHeaderHTML()),
							new StaysailContainer('F', 'footer', $footer->getHTML()),
							new StaysailContainer('L', 'left', ($left_override ? $left_override : $login->getHTML())),
							new StaysailContainer('R', 'right', $writer->getHTML()),
						   );
		$layout = new StaysailLayout($map, $containers);
		return $layout->getHTML();	
    }
    
    private function authorize()
    {
    	$writer = new StaysailWriter(__CLASS__);
    	$username = trim(StaysailIO::post('username'));
    	$password = trim(StaysailIO::post('password'));
    	
    	$filter = new Filter(Filter::Match, array('email' => $username, 'is_deleted' => 0));
    	$members = $this->framework->getSubset('Member', $filter);
    	if (sizeof($members)) {
    		$Member = array_pop($members);
    		if ($Member->passwordMatch($password)) {
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
    	}
    	$writer->h1("Sorry...")
    		   ->p("The username/password combination that you entered is not valid.  Please try again.");
    	$writer->draw($this->loginScreen());
    	return $writer;
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
    
    private function preJoinScreen()
    {
    	$tos_link = StaysailWriter::makeLink('Terms of Use', 'tos.html', '', null, '_blank');
    	
    	$writer = new StaysailWriter(__CLASS__);
         $writer->addHTML('<div class="login-first">');
    	$writer->h1('Sign Up Now', 'join');
    	//$writer->h2("It only takes a moment and you'll be happy you did!", 'join');
    	
		$fan_link = StaysailWriter::MakeJobLink('Fan', __CLASS__, 'join');
    	$entertainer_link = StaysailWriter::makeJobLink('Artist', __CLASS__, 'entertainer_inquiry');
    	$club_link = StaysailWriter::makeJobLink('Content Creators', __CLASS__, 'club_inquiry');
    	
    	//$writer->h1("I am a...");
    	$writer->start('buttons');
    	$writer->span($fan_link);
    	$writer->span($entertainer_link);
    	$writer->span($club_link);
    	$writer->addHTML('&nbsp;');
    	$writer->end();
    	
    	return $writer;
    	
    }
    
    private function joinScreen()
    {
    	$tos_link = StaysailWriter::makeLink('Terms of Use', 'tos.html', '', null, '_blank');
    	
    	$writer = new StaysailWriter(__CLASS__);
        $writer->addHTML('<div class="join_page_class box-form">');
    	$writer->h1('Sign Up Now', 'join');
    	//$writer->h2("It only takes a moment and you'll be happy you did!", 'join');
    	    	
    	$type = StaysailIO::get('e') ? 'Entertainer' : 'Fan';
    	if (StaysailIO::get('c')) {$type = 'Club_Admin';}
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
		foreach ($providers as $key => $value)
		{
			$providers[$key] = $key;
		}
       	$signup = new StaysailForm('join form');
    	$signup->setPostMethod()
    	       ->setJobAction(__CLASS__, 'new_member')
    	       ->setSubmit('Sign Up Now!')
    	       ->setDefaults(array('type' => $type))
    	       ->addField(StaysailForm::Line, 'First Name', 'first_name', 'required')
    	       ->addField(StaysailForm::Line, 'Last Name', 'last_name', 'required')
    	       ->addField(StaysailForm::Line, 'Email', 'email', 'required email')
    	       ->addField(StaysailForm::Line, 'Phone Number', 'phone', 'required')
    	       ->addField(StaysailForm::Select, 'Cell Provider', 'cell_provider', 'required', $providers)
    	       ->addField(StaysailForm::Password, 'Password', 'password', 'required password')
    	       ->addField(StaysailForm::Password, 'Repeat Password', 'password_2', 'require-match:password')
              ->addField(StaysailForm::Line, 'Creator to Fan', 'creator_to_fan', 'required');
    	if ($promo) {
    		//$signup->addField(StaysailForm::Line, $promo, $promo_field, $promo_class);
    	}
    	$signup->addField(StaysailForm::Boolean, "I have read and agree to the {$tos_link}", 'tos', 'require-choice')
    	       ->addField(StaysailForm::Boolean, "I am at least 18 years of age", 'age_verify', 'require-choice')
    	       ->addField(StaysailForm::Hidden, 'type', 'type')
    	       ->addHTML('<p>This website is strictly for adults only! <b>This website contains sexually explicit content.</b> You must be at least 18 years of age to enter this website.</p>');
    	
    	 
        $writer->draw($signup);
    	$writer->addHTML('</div>');
    	return $writer;
    }
    
    private function newMember()
    {
    	$writer = new StaysailWriter(__CLASS__);
    	
    	// If there's a promotional code, add it to a session
    	if (StaysailIO::post('promo')) {
    		StaysailIO::setSession('promo', StaysailIO::post('promo'));
    	}
    	
    	// Make sure that the email address is unique for undeleted members
    	$filters = array(new Filter(Filter::Match, array('is_deleted' => 0, 'email' => StaysailIO::post('email'))));
    	$Previous_Member = $this->framework->getSingle('Member', $filters);
    	if ($Previous_Member) {
	    	$writer = new StaysailWriter('box-form');
    		$writer->h1("Sorry...", 'join')
    			   ->p("A user with the specified email address already exists.  Please use a unique email address.  If you think you already have an account, click the Forgot Password link.")
    	       	   ->p(StaysailWriter::makeJobLink('Wish to join?', __CLASS__, 'join'))
    	           ->p(StaysailWriter::makeJobLink('Forgot your password?', __CLASS__, 'forgot_pw'));
    		return $writer;    		   
    	}
    	
    	$Member = new Member();
    	$fields = array('first_name', 'last_name', 'email', 'phone', 'cell_provider');
    	$Member->updateFrom($fields);

    	if ($Member->hasValidInfo()) {
    		$Member->save();
    		$Member->setPassword(StaysailIO::post('password'))->save();
    		
    		$person = null;
    		$type = StaysailIO::post('type');
    		foreach ($this->member_types as $valid_type => $label)
    		{
    			if ($type == $valid_type) {
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
	    					mail('memberservices@localstripfan.com', "New Entertainer: {$account->name}", $message);
	    				} else {
	    					mail('jjustian@gmail.com', "New LOCALHOST Entertainer: {$account->name}", $message);
	    				}
	    				
    				}
    				
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
    	$account_type = StaysailIO::session('account_type');
    	$account_entity_id = StaysailIO::session('account_entity_id');
    	
    	if (!$account_type and StaysailIO::session('Member.id')) {
    		$Member = new Member(StaysailIO::session('Member.id'));
    		if ($Member) {
    			$account_type = $Member->getAccountType();
    			$account_entity_id = $Member->getAccountOfType($account_type)->id;
    		}
    	}
    	
    	if ($account_type and $account_entity_id) {
    		if (in_array($account_type, array_keys($this->member_types))) {
    			$account = new $account_type($account_entity_id);
    			$account->saveProfile();
    			
    			switch ($account_type)
    			{
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
    		
    		$message = <<<__END__
Greetings,

Thank you for your password reset request. To reset your password, please paste the following URL into your web browser's
address bar:

    https://www.localstripfan.com/reset/?auth={$reset_code}

You will be asked to change your password on this screen, and you will be able to log in immediately with your new password.

Please note that this link with expire in a couple hours. If it does not work, please click the "Forgot Password" link again.

If you did not request this password change, then disregard this message.

Regards,
Local City Scene Management
    		
__END__;
			$from = "Reply-to:memberservices@localstripfan.com\nFrom:memberservices@localstripfan.com";
			mail($email, 'Password Reset', $message, $from);
    		$writer->addHTML('<div class="box-form">');
   			$writer->h1('Thank you!  Please check your email for further instructions', 'join');
   			$writer->draw($this->loginScreen());
    	} else {
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
    	if (!$auth) {$auth = StaysailIO::session('pwauth');}
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

$html ='
    	<iframe style="display:none;" width="560" height="315" src="https://www.youtube.com/embed/hSSnbopeOcw" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>';

		if ($area == 'fan') {
			$html .= <<<__END__
        <div class="left-logo">
            <img src="/site_img/yfl-lrge-logo.png">
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
    	foreach ($positions as $label => $position)
    	{
	    	$entertainers = $Club->getEntertainers($position);
	    	if (sizeof($entertainers)) {
	    		$writer->h2($label);
		    	foreach ($entertainers as $Entertainer)
				{
					if (!$Entertainer->private) {
						$avatar = $Entertainer->Member->getAvatarHTML(Member::AVATAR_LITTLE, false);
						$link = StaysailWriter::makeLink($avatar, "/{$Club->account_number}/{$Entertainer->fan_url}");
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
    	$writer->h1('Artist Inquiry', 'join');
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
    		   ->p('Look for an email from memberservices@localstripfan.com for instructions on completing your sign up.', 'green big')
    		   ->p('If you do not receive this email, please check your spam folder.', 'red')
    		   ->p('Again, we appreciate your interest and your time.  A Local Strip Fan representative will be contacting you shortly with instructions on completing your sign-up!');

		$type = StaysailIO::post('type');
		$name = trim(StaysailIO::post('name'));
		if (!$name) {return $writer;}
		if ($type == 'Entertainer') {
			$fields = array('name', 'position', 'phone', 'email', 'club_code', 'club_name', 'club_phone', 
							'manager_name', 'source');
		} else {
			$fields = array('name', 'role', 'phone', 'email', 'club_name', 'club_address', 'club_phone', 'source');
		}
		
		$message = "{$type} Sign Up Inquiry\n\n";
		$message .= "{$name} is interested in signing up as a {$type} for Local Strip Fan.\n\n";
		foreach ($fields as $fieldname)
		{
			$label = ucwords(str_replace('_', ' ', $fieldname));
			$value = StaysailIO::post($fieldname);
			$message .= "{$label} .......... {$value}\n";
		}
		
		mail('memberservices@localstripfan.com,jjustian@gmail.com', "{$type} Signup Inquiry", $message);
		
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
			
			mail("memberservices@localstripfan.com,{$email}", "Local Strip Entertainer Confirmation", $confirm, 'From:memberservices@localstripfan.com');
		}

		if ($type == 'Club') {
			$writer->p("Please check your email for your confirmation message!");

			$confirm = <<<__END__
			
Welcome {$name} to Local Strip Fan.com!

Expect me to contact you soon. If you have any questions.  Don't hesitate to call me on my cell 312-296-2551.
 
Bill
Member Services
		
__END__;
			
			$email = StaysailIO::post('email');
			$email = preg_replace('/[\n,;]/', '', $email);
			
			mail("memberservices@localstripfan.com,{$email}", "Local Strip Club Confirmation", $confirm, 'From:memberservices@localstripfan.com');
		}
		
		return $writer;
    }
}
