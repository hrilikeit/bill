<?php

require '../private/views/SubscriptionListView.php';
require '../private/views/MostPopularView.php';
require '../private/views/TwitterView.php';

class FanHome extends StaysailPublic
{
	protected $page, $settings, $categories;
    protected $framework;

    public $Member;
    public $Fan;

    public function __construct($dbc = '')
    {
    	$this->valid = false;
        $this->framework = StaysailIO::engage();
        $member_id = StaysailIO::session('Member.id');
        $this->Member = new Member($member_id);
        $this->Fan = $this->Member->getAccountOfType('Fan');
        if ($this->Fan) {
	        $this->Member->name = $this->Fan->name;
    	    $this->Member->save();
        }
    }

    public function getHTML()
    {


    	$job = StaysailIO::get('job');
    	$id = StaysailIO::get('id');

    	$map = Maps::getFanProfileMap();
    	$content_override = '';

    	switch ($job)
    	{
    		case 'confirm_age':
//    			if (StaysailIO::post('confirm')) {
//    				$this->Fan->setAgeConfirmation();
//    			}
//    			$this->Member->checkStanding();
    			break;

            case 'all_models':
//                $this->Member->checkStanding();
                $content_override = $this->allModels();
                break;

    		case 'photos':
//    			$this->Member->checkStanding();
    			$content_override = $this->getPurchasedPhotos();
    			break;

    		case 'review':
//    			$this->Member->checkStanding();
    			$type = StaysailIO::get('type');
    			$id = StaysailIO::get('id');
    			$content_override = $this->writeReview($type, $id);
    			break;

    		case 'post_review':
//    			$this->Member->checkStanding();
    			$type = StaysailIO::get('type');
    			$id = StaysailIO::get('id');
    			$content_override = $this->postReview($type, $id);
    			break;

    		case 'new_payment_method':

    		    $url = explode('?', $_SERVER['HTTP_REFERER']);
    		    if (!empty($url[1]) && $url[1] != 'mode=FanProfile&job=update_bio'){
                    StaysailIO::setSession('purchase_redirect', '');
                }
    			$content_override = $this->addPaymentMethod();
    			break;

    		case 'post_payment_method':
    			$this->postPaymentMethod();
    			if (StaysailIO::session('purchase_redirect')) {
    				header("Location:" . StaysailIO::session('purchase_redirect'));
    				exit;
    			}
    			elseif (StaysailIO::session('inviter_entertainer_id')){
                    header("Location:?mode=EntertainerProfile&entertainer_id=".StaysailIO::session('inviter_entertainer_id'));
                    exit;
                }
                break;

    		case 'activate_with_code':
    			$this->activateWithCode();
    			break;

            case 'public_live':
                $content_override = $this->publicLive();;
                break;

            case 'video_store':
                $content_override = $this->videoStore();;
                break;

            case 'video_store_single':
                $id = StaysailIO::get('id');
                $content_override = $this->videoStoreSingle($id);;
                break;

    		default:
//    			$this->Member->checkStanding();

    			// If the Member has logged in, and is paid up, see if there's a redirect
		    	if (StaysailIO::session('post_login_redirect')) {
		    		$post_login_redirect = StaysailIO::session('post_login_redirect');
		    		StaysailIO::setSession('post_login_redirect', null);
		    		header("Location:{$post_login_redirect}");
		    		exit;
		    	}
       	}

    	$header = new HeaderView();
    	$footer = new FooterView();
    	$action = new ActionsView($this->Member);
    	$banner = new BannerAdsView($this->Member);
		$subscription = new SubscriptionListView($this->Member);
		$twitter = new TwitterView();
        if (!$job){
            $MostPopular = new MostPopularView($this->Member);
            $MostPopular = $MostPopular->getHTML();
        }
        else{
            $MostPopular = '';
        }

		$containers = array(new StaysailContainer('H', 'header', $header->getHTML()),
            new StaysailContainer('F', 'footer', $footer->getHTML()),
            new StaysailContainer('A', 'action', $action->getHTML()),
            new StaysailContainer('B', 'banner', $MostPopular),
        );

//		if (!$this->Fan->hasConfirmedAge()) {
//			$content_override = $this->ageConfirmation();
//		} elseif (StaysailIO::session('inviter_entertainer_id') and $this->Member->expire_time) {
//    		// If an existing user was invited, direct immediately to the payment screen
//			$entertainer_id = StaysailIO::session('inviter_entertainer_id');
//			StaysailIO::setSession('inviter_entertainer_id', null);
//			header("Location:?mode=Purchase&job=purchase&type=Entertainer&id={$entertainer_id}");
//			exit;
//    	}

        if (StaysailIO::session('inviter_entertainer_id') and $this->Member->expire_time) {
            // If an existing user was invited, direct immediately to the payment screen
            $entertainer_id = StaysailIO::session('inviter_entertainer_id');
            StaysailIO::setSession('inviter_entertainer_id', null);
            header("Location:?mode=Purchase&job=purchase&type=Entertainer&id={$entertainer_id}");
            exit;
        }

		if ($content_override) {
			$containers[] = new StaysailContainer('E', 'content', $content_override);
		} else {
			$subscription_content = $subscription->getHTML() . $subscription->getRecentPosts();

			if (StaysailIO::get('da')) {
				$banned_Entertainer = new Entertainer(StaysailIO::getInt('da'));
				$Fan_Subscription = $this->Fan->isSubscribedTo($banned_Entertainer);
				if ($Fan_Subscription) {
					$lift_time = $Fan_Subscription->getFormattedBanLiftTime();
					$message = <<<__END__
					<h1>Sorry...</h1>
					<p>Your subscription to <strong>{$banned_Entertainer->name}</strong> has been temporarily disabled.</p>
					<p>You will be able to view or contact this entertainer again after <strong>{$lift_time}</strong>.</p>
__END__;
					$subscription_content = "<div>{$message}</div>{$subscription_content}";
				}
			}

			$containers[] = new StaysailContainer('E', 'entertainers', $subscription_content);
		}

		$layout = new StaysailLayout($map, $containers);
		return $layout->getHTML();
    }

    private function ageConfirmation()
    {
    	$writer = new StaysailWriter();

    	$form = new StaysailForm();
    	$form->setJobAction(__CLASS__, 'confirm_age' . (StaysailIO::get('signup') ? '&signup=1' : ''))
    		 ->setPostMethod()
    		 ->addField(StaysailForm::Boolean, 'I confirm that I am 18 years of age or older', 'confirm', 'require-choice')
    		 ->setSubmit('Confirm');

    	$writer->h1('Age Confirmation')
    		   ->p('Before you continue, please confirm that you are eligible to visit this site by confirming your age below.')
    		   ->draw($form);
		return $writer->getHTML();
    }

    private function activateWithCode()
    {
    	StaysailIO::setSession('promo', StaysailIO::post('promo'));
    	header("Location:?mode=Purchase&job=purchase&type=Member");
    	exit;
    }

    private function getPurchasedPhotos()
    {
        $this->Member->requiredEmailVerify();

		$writer = new StaysailWriter();
		$writer->h1('Vault');

		$purchased_library = $this->Fan->getPurchasedLibrary();
		if (sizeof($purchased_library)) {
            $writer->addHTML('<div class="vault_data">');
			foreach ($purchased_library as $Library)
			{
				if($Library->File_Type_id == 3){
                    if ($Library->gallery_id) {
                        $link = $Library->makeSliderWithLinks();

                    } else {
                        $link = StaysailWriter::makeLink($Library->getThumbnailHTML(), $Library->getFullSizeURL(), '', null, '_blank');
                    }
				}
				if($Library->File_Type_id == 4){
					$video = $Library->getWebVideoHTML();
                    $type = $Library->mime_type;

                    if(in_array($Library->mime_type, [ 'video/quicktime',  'video/x-msvideo' , ''])) {
                        $type = 'video/mp4';
                    }
					$link = '<video controls width="320" height="240">
  								<source src="'.$video.'" type="'.$type.'">
  								your browser does not support this tag.
							</video>';

				}
				$writer->addHTML($link);
			}
            $writer->addHTML('</div>');
		} else {
			$writer->p("You have not yet purchased any high-resolution images.  You may buy them by subscribing to an entertainer's site.");
		}
		return $writer->getHTML();
    }

    private function writeReview($type, $id)
    {
		$writer = new StaysailWriter();

		$writer->start('panel')
		       ->h1("Write a Review");

		// Has the fan already reviewed this entity?
		$type = StaysailIO::get('type');
		$id = StaysailIO::getInt('id');

		if ($type == 'Entertainer' or $type == 'Club') {
			$form = new StaysailForm('compose_message');
			$form->setSubmit('Submit Review')
				 ->setPostMethod()
				 ->setAction("?mode=FanHome&job=post_review&type={$type}&id={$id}");
			$entity = new $type($id);
			$writer->h2("For {$entity->name}");
			$Review = $this->Member->getReviewFor($entity);
			if ($Review) {
				$form->setDefaults($Review->info());
				$old_rating = $Review->rating;
			} else {
				$old_rating = 3;
			}

			$writer->addHTML($this->getRatingControl($old_rating));

			$form->addField(StaysailForm::Line, 'Review Title', 'name', 'required')
			     ->addField(StaysailForm::Text, 'Review', 'content', 'required');
			$form->addHTML("<input type=\"hidden\" name=\"rating\" id=\"rating\" value=\"{$old_rating}\">");
			$writer->draw($form);
		}

		$writer->end('panel');
		return $writer->getHTML();


	}

	private function postReview()
	{
		$type = StaysailIO::get('type');
		$id = StaysailIO::getInt('id');

		// Do not save for Demo account
		if ($this->Member->name == 'Demo') {
			$writer = new StaysailWriter();
			$writer->h1('Thanks!');
			$writer->p('Please note that the Demo account review is not actually saved.');
			return $writer->getHTML();
		}

		if ($type == 'Entertainer' or $type == 'Club') {
			$fields = array('name', 'content', 'rating');
			$entity = new $type($id);
			$Review = $this->Member->getReviewFor($entity);
			if (!$Review) {$Review = new Review();}
			$Review->updateFrom($fields);
			$Review->update(array('review_time' => StaysailIO::now(),
								  'admin_status' => 'pending',
								  "{$type}_id" => $entity->id,
								  'Member_id' => $this->Member->id));

			// Range enforcement:
			if (StaysailIO::post('rating') > 5) {$Review->update(array('rating' => 5));}
			if (StaysailIO::post('rating') < 1) {$Review->update(array('rating' => 1));}

			$Review->save();
		}
	}

	private function getRatingControl($default, $max = 5)
	{
		$html = '';
		for ($i = 1; $i <= $max; $i++)
		{
			$icon = $default >= $i ? Icon::STAR_FULL : Icon::STAR_OFF;
			$img = "<img src=\"/site_img/icons/{$icon}\" id=\"star{$i}\" />";
			$control = "<a onclick=\"updateStar({$i})\">{$img}</a>";
			$html .= $control;
		}
		return $html;
	}

	private function addPaymentMethod()
	{
		$payment_methods = count($this->Member->getPaymentMethods());
		$writer = new StaysailWriter('add-payment');
		if (!$payment_methods) {
			$writer->h1('Step 3');
			if (!$this->Member->validatePromotionalCode()) {
				// If they haven't entered a code yet, and are eligible to do so, allow entry on this screen
				$code_form = new StaysailForm('join-code');
				$code_form->setJobAction(__CLASS__, 'activate_with_code')
						  ->setSubmit('Join with Code')
						  ->setPostMethod()
						  ->addField(StaysailForm::Line, 'Promotional Code', 'promo', 'required');
				$writer->draw($code_form);
				$writer->p("<i>Don't have a code?</i>");
				$writer->p("<button type='button' class='home-button'><a href=\"?mode=FanHome&w=1\">Home</a></button>");
				$writer->p("<i>Don't have a code?</i> Enter a payment method below!");
			}
		}

		$writer->h1("Add New Payment Method");
		if (!$payment_methods) {
			$writer->p("<strong><a class=\"red\" href=\"#\" onclick=\"alert('Why Do We Need a Credit Card?\\n\\nWe are primarily an invite only website, which is designed to ensure the privacy and confidentiality of our Entertainers and Fans. We require credit cards to maintain and ensure the privacy of both Entertainers and Fans. The use of a credit card ensures that multiple accounts are not opened for improper purposes, such as trolling or scraping, etc., which helps safeguard the privacy of both Entertainers and Fans.\\n\\nWe are PCI Compliant.')\">Why do we need a credit card?</a> | <a href=\"#\" onclick=\"openTraining(7)\">Video</a></strong>");
		}
		// Add PCI Compliance seal
		$PCI = '<script type="text/javascript" src="https://sealserver.trustwave.com/seal.js?code=b8db215dbdf145ada76aff0e014fa290"></script>';

		$writer->p(StaysailWriter::makeImage('/site_img/visa_mc_discover_logos.jpg', 'We accept Visa, Mastercard, and Discover', 'cc_logo') . $PCI);
		$writer->p("<strong>Note: </strong><i>yourfanslive.com is billed on your credit card as <strong><span class=\"red\">\"Local City Scene\"</span></strong>.</i>");

		if (StaysailIO::session('purchase_redirect')) {
			if ($this->Member->validatePromotionalCode()) {
				$writer->p("After you enter your payment information, <strong>you will be credited for a free 30-day membership.</strong>");
			} else {
				$writer->p("If you were making a purchase, you will be returned to your purchase after entering a payment method.");
			}
		}

		$expiration_dates = array();
		for ($y = date('Y'); $y < date('Y') + 8; $y++)
		{
			for ($m = ($y == date('Y') ? date('m') : 1); $m <= 12; $m++)
			{
				$pm = str_pad($m, 2, 0, STR_PAD_LEFT);
				$expiration_dates["{$m}/{$y}"] = "{$pm}/{$y}";
			}
		}

		$defaults = array('firstname' => $this->Member->first_name,
						  'lastname' => $this->Member->last_name,
						  'email' => $this->Member->email,
						  'country' => 'US',
						 );

		$non_NA = array('--' => 'Non-North American Address');
	    $states = array('' => 'Select One...') + $non_NA + Club::getStateNames();
	    $countries = getCountryCodes();


		$form = new StaysailForm('card-data');
		$form->setPostMethod()
			 ->setJobAction(__CLASS__, 'post_payment_method')
			 ->setSubmit('Add Payment Method')
			 ->setDefaults($defaults)
			 ->addField(StaysailForm::Line, 'Credit Card Number', 'cc_number', 'required ccn')
			 ->addField(StaysailForm::Select, 'Expiration Date', 'expire', 'required', $expiration_dates)
			 ->addField(StaysailForm::Line, 'Verification Code', 'cc_vc', 'required')
			 ->addField(StaysailForm::Line, 'First Name', 'firstname', 'required')
			 ->addField(StaysailForm::Line, 'Last Name', 'lastname', 'required')
			 ->addField(StaysailForm::Line, 'Company', 'company')
			 ->addField(StaysailForm::Line, 'Address', 'address1', 'required')
			 ->addField(StaysailForm::Line, '&nbsp;', 'address2')
			 ->addField(StaysailForm::Line, 'City', 'city', 'required')
			 ->addField(StaysailForm::Select, 'State', 'state', 'required state', $states)
			 ->addField(StaysailForm::Select, 'Country', 'country', 'required', $countries)
			 ->addField(StaysailForm::Line, 'ZIP/Postal Code', 'zip', 'required zip')
			 ->addField(StaysailForm::Line, 'Phone', 'phone')
			 ->addField(StaysailForm::Line, 'Email', 'email');
		$writer->draw($form);
		return $writer->getHTML();

	}

	private function postPaymentMethod()
	{
		$fields = array('firstname', 'lastname', 'company', 'address1', 'address2', 'city', 'state', 'country', 'zip', 'phone', 'email');
		$Payment_Method = new Payment_Method();
		$Payment_Method->updateFrom($fields);
		$Payment_Method->Member_id = $this->Member->id;
		if (preg_match('/(\d{1,2})\/(\d{4})/', StaysailIO::post('expire'), $m)) {
			$Payment_Method->expire_month = $m[1];
			$Payment_Method->expire_year = $m[2];
		}
		$Payment_Method->save();

	    $cc_number = trim(StaysailIO::post('cc_number'));
	    $cc_vc = trim(StaysailIO::post('cc_vc'));
	    $Payment_Method->setEncryptedNumbers($cc_number, $cc_vc);

	    if (strlen($cc_number) > 14) {
	    	$last4 = substr($cc_number, strlen($cc_number) - 4, 4);
	    	$name = "Card ending in {$last4}";

	    	// Delete other payment methods for the same member with the same description
	    	$payment_methods = $this->framework->getSubset('Payment_Method', new Filter(Filter::Match,
	    		array('Member_id' => $this->Member->id, 'name' => $name, 'deleted' => 0)));
			foreach ($payment_methods as $duplicate_Payment_Method)
			{
				$duplicate_Payment_Method->delete_Job();
			}
	    	$Payment_Method->name = $name;
	    	$Payment_Method->save();
	    	StaysailIO::setSession('Payment_Method.id', $Payment_Method->id);
	    }
	}

	private function allModels()
    {
        $this->Member->requiredEmailVerify();
        $writer = new StaysailWriter();
        $writer->h1('Creators');
//        $subscriptions =  $this->Fan->getActiveSubscriptions();
//        $subscribedEntertainers = [];
        $writer->addHTML('<div class="avatar_link_div" id="creators">');


//        foreach ($subscriptions as $s) {
//            $subscribedEntertainers[] = $s->Entertainer_id;
//        }
//        $entertainers = $this->Fan->getActiveEntertainers();
//        if (sizeof($entertainers)) {
//            foreach ($entertainers as $entertainer)
//            {
//                if (!in_array($entertainer->id, $subscribedEntertainers)) {
//                    $writer->addHTML($entertainer->getAvatarLink());
//                }
//            }
//        }



        $writer->addHTML('</div>');
        $writer->addHTML('<script type="text/javascript">var  memberId = '. $this->Member->id . ';</script>');
        $writer->addHTML('<script type="text/javascript" src="/js/creators.js"></script>');
        $writer->addHTML('</div>');
        $writer->addHTML('<div id="pagination">');
        $writer->addHTML('</div>');
        $writer->addHTML('<div>');

        return $writer->getHTML();
    }

    public function publicLive()
    {
        $this->Member->requiredEmailVerify();
        $writer = new StaysailWriter();
        $writer->h1('Public Live');
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.dyte.io/v2/livestreams?exclude_meetings=false&status=LIVE",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Authorization: Basic ZTQ4ZWJhOGUtMDJlZS00ZTNmLWFkN2YtZTFmZTQ5MjhhYTI1OjA3OWQ0ZjMyZTgzNDQyODQ0Y2Ey",
                "Content-Type: application/json"
            ],
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $meetings = [];
        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $streams = json_decode($response, true);
            foreach ($streams['data'] as $stream){
                $meetings[] = $stream['meeting_id'];
            }
        }
        $filter = new Filter(Filter::Match, array('is_deleted' => 0));
        $Entertainers = $this->framework->getSubset('Entertainer', $filter);
        $writer->addHTML('<div class="public-content">');
        foreach ($Entertainers as $entertainer) {
            $filters = array(
                new Filter(Filter::Match, array('Entertainer_id' => $entertainer->id)),
                new Filter(Filter::Match, array("public_live_status" => 1)),
                new Filter(Filter::Match, array('show_type' => 2)),
                new Filter(Filter::Sort, 'id DESC'),
            );
            $WebShowDyte = $this->framework->getSingle('WebShowDyte', $filters);
            if ($WebShowDyte){
                if(in_array($WebShowDyte->meeting_id, $meetings)){
                    $writer->addHTML($entertainer->getLiveLink());
                }
                else{
                    if (strtotime("now") > $WebShowDyte->update_at+(30*2)){
                        $WebShowDyte->public_live_status = 0;
                        $WebShowDyte->save();
                    }
                }
            }
        }
        $writer->addHTML('</div>');

        return $writer->getHTML();
    }

    public function videoStore()
    {
        $this->Member->requiredEmailVerify();
        $writer = new StaysailWriter();
        $writer->h1('Video Store');
        $libraryIds = $this->framework->getColumnByConditions('Library', 'id', array('File_Type_id' => 4, 'placement' => '"shop"'));
        if ($libraryIds) {
            $filters = array(
                new Filter(Filter::IN, array('Library_id' => $libraryIds)),
                new Filter(Filter::IsTrue, 'active'),
                new Filter(Filter::IsNull, 'Post_id'),
                new Filter(Filter::Sort, 'post_time DESC'),
            );
            $posts = $this->framework->getSubset('Post', $filters);
            $writer->addHTML("<div class='grid-container'>");
//            $writer->addHTML("{$this->paymentModal()}");
            foreach ($posts as $post){
                $writer->addHTML("{$post->getVideoShopHTML($this->Member->id)}");
            }
            $writer->addHTML("</div>");
        }

        return $writer->getHTML();
    }

    public function videoStoreSingle($id)
    {
        $this->Member->requiredEmailVerify();
        $writer = new StaysailWriter();
        $writer->h1('Video Store');
        $filters = array(
            new Filter(Filter::Where, "Library_id = $id"),
            new Filter(Filter::IsTrue, 'active'),
            new Filter(Filter::IsNull, 'Post_id'),
        );
        $post = $this->framework->getSingle('Post', $filters);
        $writer->addHTML("<div class='grid-container'>");
        $writer->addHTML("{$post->getVideoShopHTML($this->Member->id)}");
        $writer->addHTML("</div>");

        return $writer->getHTML();
    }

    public function paymentModal()
    {
        $payment_methods = $this->Member->getPaymentMethods();
        if (sizeof($payment_methods)) {
            $options = array();
            foreach ($payment_methods as $Payment_Method) {
                $options[$Payment_Method->id] = $Payment_Method->name;
            }

            $opt = '';
            foreach ($options as $key=>$option){
                $opt .= "<option value='$key'>$option</option>";
            }
        }

        $html = "
                <div id='payment_modal' class='modal'>
                
                  <div class='modal-content'>
                    <span class='close'>&times;</span>
                    <form method='post' class='profile_lock_form' action='?mode=Purchase&job=verify' onsubmit='return uniValidate(this)'>
                        <p>pay </p>
                        <input type='hidden' name='type' value='tip'>
                        <input type='hidden' name='id' value=''>
                        <input id='resolution_val' type='hidden' name='resolution' value=''>
                        <input id='payment_method_id_val' type='hidden' name='payment_method_id' value=''>
                        <select>
                            {$opt}
                        </select>
                        <div class='field'><a href='?mode=FanHome&job=new_payment_method' class='spaced button'>Add Payment Method</a></div>
                        <div class='field'><div class='submit'><input type='submit' value='Purchase Video'></div></div>
                     </form>
                  </div>
                </div>
            ";

        return $html;
    }
}

function getCountryCodes()
{
	$codes = array(
	'AF' => 'Afghanistan',
'AX' => '�land Islands',
'AL' => 'Albania',
'DZ' => 'Algeria',
'AS' => 'American Samoa',
'AD' => 'Andorra',
'AO' => 'Angola',
'AI' => 'Anguilla',
'AQ' => 'Antarctica',
'AG' => 'Antigua and Barbuda',
'AR' => 'Argentina',
'AM' => 'Armenia',
'AW' => 'Aruba',
'AU' => 'Australia',
'AT' => 'Austria',
'AZ' => 'Azerbaijan',
'BS' => 'Bahamas',
'BH' => 'Bahrain',
'BD' => 'Bangladesh',
'BB' => 'Barbados',
'BY' => 'Belarus',
'BE' => 'Belgium',
'BZ' => 'Belize',
'BJ' => 'Benin',
'BM' => 'Bermuda',
'BT' => 'Bhutan',
'BO' => 'Bolivia, Plurinational State of',
'BQ' => 'Bonaire, Sint Eustatius and Saba',
'BA' => 'Bosnia and Herzegovina',
'BW' => 'Botswana',
'BV' => 'Bouvet Island',
'BR' => 'Brazil',
'IO' => 'British Indian Ocean Territory',
'BN' => 'Brunei Darussalam',
'BG' => 'Bulgaria',
'BF' => 'Burkina Faso',
'BI' => 'Burundi',
'KH' => 'Cambodia',
'CM' => 'Cameroon',
'CA' => 'Canada',
'CV' => 'Cape Verde',
'KY' => 'Cayman Islands',
'CF' => 'Central African Republic',
'TD' => 'Chad',
'CL' => 'Chile',
'CN' => 'China',
'CX' => 'Christmas Island',
'CC' => 'Cocos (Keeling) Islands',
'CO' => 'Colombia',
'KM' => 'Comoros',
'CG' => 'Congo',
'CD' => 'Congo, the Democratic Republic of the',
'CK' => 'Cook Islands',
'CR' => 'Costa Rica',
'CI' => 'Cote d\'Ivoire',
'HR' => 'Croatia',
'CU' => 'Cuba',
'CW' => 'Cura�ao',
'CY' => 'Cyprus',
'CZ' => 'Czech Republic',
'DK' => 'Denmark',
'DJ' => 'Djibouti',
'DM' => 'Dominica',
'DO' => 'Dominican Republic',
'EC' => 'Ecuador',
'EG' => 'Egypt',
'SV' => 'El Salvador',
'GQ' => 'Equatorial Guinea',
'ER' => 'Eritrea',
'EE' => 'Estonia',
'ET' => 'Ethiopia',
'FK' => 'Falkland Islands (Malvinas)',
'FO' => 'Faroe Islands',
'FJ' => 'Fiji',
'FI' => 'Finland',
'FR' => 'France',
'GF' => 'French Guiana',
'PF' => 'French Polynesia',
'TF' => 'French Southern Territories',
'GA' => 'Gabon',
'GM' => 'Gambia',
'GE' => 'Georgia',
'DE' => 'Germany',
'GH' => 'Ghana',
'GI' => 'Gibraltar',
'GR' => 'Greece',
'GL' => 'Greenland',
'GD' => 'Grenada',
'GP' => 'Guadeloupe',
'GU' => 'Guam',
'GT' => 'Guatemala',
'GG' => 'Guernsey',
'GN' => 'Guinea',
'GW' => 'Guinea-Bissau',
'GY' => 'Guyana',
'HT' => 'Haiti',
'HM' => 'Heard Island and McDonald Islands',
'VA' => 'Holy See (Vatican City State)',
'HN' => 'Honduras',
'HK' => 'Hong Kong',
'HU' => 'Hungary',
'IS' => 'Iceland',
'IN' => 'India',
'ID' => 'Indonesia',
'IR' => 'Iran, Islamic Republic of',
'IQ' => 'Iraq',
'IE' => 'Ireland',
'IM' => 'Isle of Man',
'IL' => 'Israel',
'IT' => 'Italy',
'JM' => 'Jamaica',
'JP' => 'Japan',
'JE' => 'Jersey',
'JO' => 'Jordan',
'KZ' => 'Kazakhstan',
'KE' => 'Kenya',
'KI' => 'Kiribati',
'KP' => 'Korea, Democratic People\'s Republic of',
'KR' => 'Korea, Republic of',
'KW' => 'Kuwait',
'KG' => 'Kyrgyzstan',
'LA' => 'Lao People\'s Democratic Republic',
'LV' => 'Latvia',
'LB' => 'Lebanon',
'LS' => 'Lesotho',
'LR' => 'Liberia',
'LY' => 'Libya',
'LI' => 'Liechtenstein',
'LT' => 'Lithuania',
'LU' => 'Luxembourg',
'MO' => 'Macao',
'MK' => 'Macedonia, the former Yugoslav Republic of',
'MG' => 'Madagascar',
'MW' => 'Malawi',
'MY' => 'Malaysia',
'MV' => 'Maldives',
'ML' => 'Mali',
'MT' => 'Malta',
'MH' => 'Marshall Islands',
'MQ' => 'Martinique',
'MR' => 'Mauritania',
'MU' => 'Mauritius',
'YT' => 'Mayotte',
'MX' => 'Mexico',
'FM' => 'Micronesia, Federated States of',
'MD' => 'Moldova, Republic of',
'MC' => 'Monaco',
'MN' => 'Mongolia',
'ME' => 'Montenegro',
'MS' => 'Montserrat',
'MA' => 'Morocco',
'MZ' => 'Mozambique',
'MM' => 'Myanmar',
'NA' => 'Namibia',
'NR' => 'Nauru',
'NP' => 'Nepal',
'NL' => 'Netherlands',
'NC' => 'New Caledonia',
'NZ' => 'New Zealand',
'NI' => 'Nicaragua',
'NE' => 'Niger',
'NG' => 'Nigeria',
'NU' => 'Niue',
'NF' => 'Norfolk Island',
'MP' => 'Northern Mariana Islands',
'NO' => 'Norway',
'OM' => 'Oman',
'PK' => 'Pakistan',
'PW' => 'Palau',
'PS' => 'Palestinian Territory, Occupied',
'PA' => 'Panama',
'PG' => 'Papua New Guinea',
'PY' => 'Paraguay',
'PE' => 'Peru',
'PH' => 'Philippines',
'PN' => 'Pitcairn',
'PL' => 'Poland',
'PT' => 'Portugal',
'PR' => 'Puerto Rico',
'QA' => 'Qatar',
'RE' => 'R�union',
'RO' => 'Romania',
'RU' => 'Russian Federation',
'RW' => 'Rwanda',
'BL' => 'Saint Barth�lemy',
'SH' => 'Saint Helena, Ascension and Tristan da Cunha',
'KN' => 'Saint Kitts and Nevis',
'LC' => 'Saint Lucia',
'MF' => 'Saint Martin (French part)',
'PM' => 'Saint Pierre and Miquelon',
'VC' => 'Saint Vincent and the Grenadines',
'WS' => 'Samoa',
'SM' => 'San Marino',
'ST' => 'Sao Tome and Principe',
'SA' => 'Saudi Arabia',
'SN' => 'Senegal',
'RS' => 'Serbia',
'SC' => 'Seychelles',
'SL' => 'Sierra Leone',
'SG' => 'Singapore',
'SX' => 'Sint Maarten (Dutch part)',
'SK' => 'Slovakia',
'SI' => 'Slovenia',
'SB' => 'Solomon Islands',
'SO' => 'Somalia',
'ZA' => 'South Africa',
'GS' => 'South Georgia and the South Sandwich Islands',
'SS' => 'South Sudan',
'ES' => 'Spain',
'LK' => 'Sri Lanka',
'SD' => 'Sudan',
'SR' => 'Suriname',
'SJ' => 'Svalbard and Jan Mayen',
'SZ' => 'Swaziland',
'SE' => 'Sweden',
'CH' => 'Switzerland',
'SY' => 'Syrian Arab Republic',
'TW' => 'Taiwan, Province of China',
'TJ' => 'Tajikistan',
'TZ' => 'Tanzania, United Republic of',
'TH' => 'Thailand',
'TL' => 'Timor-Leste',
'TG' => 'Togo',
'TK' => 'Tokelau',
'TO' => 'Tonga',
'TT' => 'Trinidad and Tobago',
'TN' => 'Tunisia',
'TR' => 'Turkey',
'TM' => 'Turkmenistan',
'TC' => 'Turks and Caicos Islands',
'TV' => 'Tuvalu',
'UG' => 'Uganda',
'UA' => 'Ukraine',
'AE' => 'United Arab Emirates',
'GB' => 'United Kingdom',
'US' => 'United States',
'UM' => 'United States Minor Outlying Islands',
'UY' => 'Uruguay',
'UZ' => 'Uzbekistan',
'VU' => 'Vanuatu',
'VE' => 'Venezuela, Bolivarian Republic of',
'VN' => 'Viet Nam',
'VG' => 'Virgin Islands, British',
'VI' => 'Virgin Islands, U.S.',
'WF' => 'Wallis and Futuna',
'EH' => 'Western Sahara',
'YE' => 'Yemen',
'ZM' => 'Zambia',
'ZW' => 'Zimbabwe',
	);
	return $codes;
}
