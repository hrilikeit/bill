<?php

require '../private/views/SubscriptionListView.php';

class FanProfile extends StaysailPublic
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
    }

    public function getHTML()
    {
    	if ($this->Member->getRole() != Member::ROLE_FAN) {
    		return;
    	}

    	$job = StaysailIO::get('job');
    	$id = StaysailIO::get('id');

    	$map = Maps::getEntertainerProfileUpdateMap();

    	$left = '';
    	switch ($job)
    	{
    		case 'post_bio':
    			$this->postBio();
    			$left = $this->updateBioForm();
    			break;

    		case 'remove_payment':
    			$Payment_Method = new Payment_Method($id);
    			if ($Payment_Method->id) {
	    			$this->Member->removePaymentMethod($Payment_Method);
    			}
    			$left = $this->updateBioForm();
    			break;

    		case 'default_card':
                $Payment_Method = new Payment_Method($id);
                if ($Payment_Method->id) {
                    $this->activeDeactivate($Payment_Method);
                }
                $left = $this->updateBioForm();
    			break;

    		case 'upload_avatar':
    			$this->Member->uploadAvatar();
    			$left = $this->updateBioForm();
    			break;

    		case 'upload_display_photo':
    			$this->Member->uploadDisplayPhoto();
    			$left = $this->updateBioForm();
    			break;

    		case 'crop_avatar':
    			$left = $this->cropAvatar();
    			break;

    		case 'set_avatar':
    			$this->setAvatar();
    			$left = $this->updateBioForm();
    			break;

    		case 'update_bio':
    		default:
    			$left = $this->updateBioForm();
    			break;
    	}

    	$header = new HeaderView();
    	$footer = new FooterView();
    	$action = new ActionsView($this->Member);
//    	$banner = new BannerAdsView($this->Member);
		//$subscription = new SubscriptionListView($this->Member);

		$containers = array(new StaysailContainer('H', 'header', $header->getHTML()),
							new StaysailContainer('F', 'footer', $footer->getHTML()),
							new StaysailContainer('A', 'action', $action->getHTML()),
//							new StaysailContainer('B', 'banner', /*$subscription->getClubList() .*/ $banner->getHTML()),
							);

		$containers[] = new StaysailContainer('L', 'posts', $left);

		$layout = new StaysailLayout($map, $containers);
		return $layout->getHTML();
    }

    public function updateBioForm()
    {
    	if ($this->Member->getRole() != Member::ROLE_FAN) {
    		return '';
    	}

		$writer = new StaysailWriter();
		$writer->h1('Edit Your Profile');

		// Avatar
		$writer->h2('Upload an Avatar');
		$writer->addHTML($this->Member->getAvatarHTML(Member::AVATAR_LITTLE));
		$avatar = new StaysailForm('profile');
		$avatar->setSubmit('Upload')
			   ->setPostMethod()
			   ->setJobAction('FanProfile', 'upload_avatar')
			   ->addField(StaysailForm::File, 'Image File', 'image');
		$writer->draw($avatar);
		if ($this->Member->hasAvatar()) {
			$writer->addHTML(StaysailWriter::makeJobLink('Crop Avatar', 'FanProfile', 'crop_avatar', '', 'spaced button'));
			$writer->p('&nbsp;');
		}
       // $writer->addHTML('<a id="myBtn_delete" href="#" class="delete_acc">delete account</a>');
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
					<a href='?mode=EntertainerProfile&job=delete_acc' class='delete_acc'>delete account</a>'
                        <button style='width: 100px;margin-left: 5px;' type='button' class='close_delete'>close</button>
                    </div>
                </div>
            </div>");
		$writer->addHTML('<p>&nbsp;</p>');
		// Display photo (Landscape)
		$writer->h2('Upload Display photo (Landscape)');
		$writer->addHTML($this->Member->getDisplayPhotoHTML(Member::DISPLAY_LARGE));
		$display = new StaysailForm('profile');
		$display->setSubmit('Upload')
			   ->setPostMethod()
			   ->setJobAction('FanProfile', 'upload_display_photo')
			   ->addField(StaysailForm::File, 'Image File', 'displayPhoto');
		$writer->draw($display);

		// Profile
    //		$writer->addHTML('<p>&nbsp;</p>');
    //		$writer->h2('SMS Notification');
    //		$providers = SMSSender::getProviders();
    //		foreach ($providers as $key => $value)
    //		{
    //			$providers[$key] = $key;
    //		}
//		$bio = new StaysailForm('profile');
//		$bio->setSubmit('Update Notification Profile')
//			->setPostMethod()
//			->setDefaults($this->Fan->info() + $this->Member->info())
//			->setJobAction('FanProfile', 'post_bio')
//			->addHTML('<p>If you wish to be notified by text message when one of your fanned Entertainers
//						comes online, provide the phone number and service provider below.  This information
//						will not be made public, and the text messages will come only from us.</p>')
//			->addField(StaysailForm::Line, 'Phone Number', 'phone')
//            ->addField(StaysailForm::Boolean, 'Do not send text messages', 'sms_optout');
////			->addField(StaysailForm::Select, 'Provider', 'cell_provider', '', $providers);
//
//		$writer->draw($bio);

		// Payment Methods
		// Set redirect URL if the user chooses to add a new payment method
		$url = "?mode=FanProfile&job=update_bio";
		StaysailIO::setSession('purchase_redirect', $url);
		$writer->addHTML('<p>&nbsp;</p>');
		$writer->h2('Payment Methods');
		$payment_methods = $this->Member->getPaymentMethods();
		if (sizeof($payment_methods)) {
			$payment_table = new StaysailTable('profile');
			$sure = array('onclick' => "return confirm('Are you sure you want to remove this payment method?');");
			$sureDefault = array('onclick' => "return confirm('Are you sure ?');");
			foreach ($payment_methods as $Payment_Method)
			{
			    if ($Payment_Method->default_card == 0){
			        $status = 'Activate';
                }
			    elseif($Payment_Method->default_card == 1){
			        $status = 'Deactivate';
                }
				$remove_link = StaysailWriter::makeJobLink('Remove', 'FanProfile', 'remove_payment', $Payment_Method->id, '', $sure);
				$default_card_link = StaysailWriter::makeJobLink($status, 'FanProfile', 'default_card', $Payment_Method->id, '', $sureDefault);
				$payment_table->addRow(array($Payment_Method->name, $remove_link, $default_card_link));
			}
			$writer->draw($payment_table);
		}

		$writer->addHTML('<br/>' . StaysailWriter::makeJobLink('Add Payment Method', 'FanHome', 'new_payment_method', '', 'spaced button'));

        $sql_is_deletion_request = "SELECT * FROM `deletion_requests` WHERE `member_id` = {$this->Member->id} 
                                  AND `status` = 1";
        $is_deletion_request = $this->framework->getSingleRow($sql_is_deletion_request);
        if ($is_deletion_request) {
            $writer->addHTML('<div style="font-size: 11px; display: block; margin-left: 235px;" >Account waiting for deletion</div>');
        } else {
            $writer->addHTML('<a style="display: block;margin-left: 235px;" id="myBtn_delete" href="#" class="delete_acc">delete account</a>');
        }


		return $writer->getHTML();
    }

    private function postBio()
    {
    	$this->Member->phone = StaysailIO::post('phone');
//    	$this->Member->cell_provider = StaysailIO::post('cell_provider');
    	$this->Member->save();
    }

    public function cropAvatar()
    {
    	$url = $this->Member->getAvatarURL();

    	$html = <<<__END__
    	<h1>Crop your Avatar</h1>
    	<p>The headshot needs to be a square.  Click the starting point of your headshot, and drag the mouse pointer while holding down the button.  When you're happy with the size and composition, click the Crop Avatar button.</p>
    	
		<div id="squarifier">
		<div id="square"></div>
		</div>

		<form action="?mode=FanProfile&job=set_avatar" method="post">
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

    public function setAvatar()
    {
    	$x = StaysailIO::post('x');
    	$y = StaysailIO::post('y');
    	$hw = StaysailIO::post('hw');
    	$path = DATAROOT . "/private/avatars/avatar{$this->Member->id}.png";
    	$size = GetImageSize($path);

    	$factor = $size[0] / 360; // Crop factor of image
    	$x *= $factor;
    	$y *= $factor;
    	$hw *= $factor;

		switch ($size['mime'])
		{
			case 'image/jpeg':
				$src_img = ImageCreateFromJPEG($path);
				break;

			case 'image/png':
				$src_img = ImageCreateFromPNG($path);
				break;
		}

		$cropped = ImageCreateTrueColor($hw, $hw);
		ImageCopyResampled($cropped, $src_img, 0, 0, $x, $y, $hw, $hw, $hw, $hw);


		$save_path = DATAROOT . "/private/avatars/avatar{$this->Member->id}.png";
		ImagePNG($cropped, $save_path);
    }

    private function activeDeactivate(Payment_Method $Payment_Method)
    {
        $Member_id = $Payment_Method->Member_id;

        $filter = new Filter(Filter::Match, array('Member_id' => $Member_id));
        $PaymentMethods = $this->framework->getSubset('Payment_Method', $filter);
        foreach ($PaymentMethods as $pm){
            $pm->default_card = 0;
            $pm->save();
        }
        $Payment_Method->default_card = 1;
        $Payment_Method->save();

        return true;
    }
}
