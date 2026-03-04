<?php

include '../private/staysail/class.StaysailEntity.php';

final class Member extends StaysailEntity
{
    // Data model properties
    public $name = parent::Line;
    public $sort = parent::Int;

    public $encoded_password = parent::Line;
    public $login_lockout = parent::Boolean;
    public $last_login = parent::Time;
    public $last_name = parent::Line;
    public $first_name = parent::Line;
    public $email = parent::Line;
    public $email_verified = parent::Boolean;
    public $phone = parent::Line;
    public $password_reset_code = parent::Line;
    public $active_time = parent::Time;
    public $expire_time = parent::Time;
    public $auto_renew = parent::Boolean;
    public $time_zone = parent::Line;     // Offset from local time, in hours (e.g., Central Time is -1 if local is Eastern)
    public $is_deleted = parent::Boolean;
    public $online_time = parent::Time;
    public $cell_provider = parent::Line;
    public $sms_optout = parent::Boolean;
    public $address = parent::Line;
    public $address_2 = parent::Line;
    public $city = parent::Line;
    public $state = parent::Line;
    public $zip = parent::Line;
    public $google_id = parent::Line;
    public $twitter_id = parent::Line;
    public $created_at = parent::Date;

    // Metadata properties
    //protected $_sort          = 'name ASC';
    protected $_name_template = '';

    const AVATAR_LARGE = 'large_avatar';
    const AVATAR_MEDIUM = 'medium_avatar';
    const AVATAR_TINY = 'tiny_avatar';
	const AVATAR_LITTLE = 'little_avatar';
	const AVATAR_SIDEBAR = 'sidebar_avatar';

    const DISPLAY_LARGE = 'large_display';
    const DISPLAY_MEDIUM = 'medium_display';


	const ROLE_FAN = 1;
	const ROLE_ENTERTAINER = 2;
	const ROLE_CLUB = 4;

    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);
    }

    public function delete_Job()
    {
    	$this->is_deleted = 1;
    	$this->save();

    	$type = $this->getAccountType();
    	$account = $this->getAccountOfType($type);
    	if ($account) {
            $account->delete_Job();
        }

        //$account->delete();
        $this->delete();
    }

    public function copy_Job() {return $this->copy();}

    /**
     * Tests whether unsaved Member data is complete, so the profile and login
     * systems can decide whether to save the record.
     *
     * @return bool
     */
    public function hasValidInfo()
    {
    	$info = $this->info();
    	$required = array('first_name', 'last_name', 'email');
    	foreach ($required as $required_field)
    	{
    		if (!isset($info[$required_field]) or !$info[$required_field]) {
    			return false;
    		}
    	}
    	return true;
    }

    /**
     * Sets the session token indicating that this is the logged-in Member
     */
    public function registerSession()
    {
    	StaysailIO::setSession('Member.id', $this->id);
    	StaysailIO::setSession('Member_role', null);
    }

    /**
     * Sets the password to the encoded value.
     *
     * Optionally save afterward with $Member->setPassword($password)->save();
     *
     * @param string $password
     * @return $this
     * @see encodePassword()
     */
    public function setPassword($password)
    {
    	$encoded_password = $this->encodePassword($password);
    	$this->encoded_password = $encoded_password;
    	$this->password_reset_code = '';
    	return $this;
    }

    /**
     * Returns an encoded password given a raw password.
     *
     * The encoded password is a salted hash of
     *     the member_id, the password, the string 'LSFCL'
     *
     * @param string $password
     * @return string
     */
    private function encodePassword($password)
    {
    	$salted = "{$this->id}{$password}LSFCL";
    	return sha1($salted);

    }

    /**
     * Does the specified password match the actual Member password?
     *
     * @param string $password
     * @return boolean
     */
    public function passwordMatch($password)
    {
    	$encoded_password = $this->encodePassword($password);
    	return ($encoded_password == $this->encoded_password);
    }

    public function makeAccountOfType($type)
    {
    	if ($type != 'Fan' and $type != 'Entertainer' and $type != 'Club_Admin') {
    		return false;
    	}

    	$account = new $type();
    	$account->Member = $this;
    	$account->name = $this->name;
    	$account->save();
    	return $account;
    }

    public function getAccountOfType($type)
    {
        if ($type != 'Fan' and $type != 'Entertainer' and $type != 'Club_Admin') {
    		return false;
    	}
    	$filter[] = new Filter(Filter::Match, array('Member_id' => $this->id));
    	$accounts = $this->_framework->getSubset($type, $filter);
        if (sizeof($accounts)) {
    		return array_pop($accounts);
    	}
    	return false;
    }

    public function getAccountType()
    {
    	if ($this->getAccountOfType('Fan')) {return 'Fan';}
    	if ($this->getAccountOfType('Entertainer')) {return 'Entertainer';}
    	if ($this->getAccountOfType('Club_Admin')) {return 'Club_Admin';}
    	return false;
    }

    public function getPaymentScreen()
    {
    	$writer = new StaysailWriter('payment');
    	$writer->h1("Payment Screen")
    		   ->p("Coming soon.  Right now, just put some random numbers in and click Pay Now");

        $payment = new StaysailForm();
		$payment->setSubmit("Pay Now")
		  		->setPostMethod()
				->setJobAction('Login', 'update_payment')
				->addField(StaysailForm::Line, 'Fake Card Number', 'ccn', 'required');

		$writer->draw($payment);
		return $writer;
    }

    public function expireInDays($days)
    {
    	$start = strtotime($this->active_time);
    	$end = $start + (60 * 60 * 24 * $days);
    	$this->expire_time = date('Y-m-d H:i:s', $end);
    }

    public function getExpirationDate()
    {
    	$expire = strtotime($this->expire_time);
    	return date(SHORT_DATE_FORMAT, $expire);
    }

    public function getStartDate()
    {
    	$active = strtotime($this->active_time);
    	return date(SHORT_DATE_FORMAT, $active);
    }

    public function getHistoryHTML()
    {
    	$writer = new StaysailWriter();
    	$writer->h1("Account History");
    	$history = new StaysailTable();
    	$history->addRow(array('Account Create Date', $this->getStartDate()));
    	$history->addRow(array('Expiration Date', $this->getExpirationDate()));
		$writer->draw($history);
		return $writer->getHTML();
    }

    public function getAvatarHTML($size = self::AVATAR_LARGE, $add_url = true, $linkUrl = null)
    {
        $Entertainer = $this->getAccountOfType('Entertainer');
        if ($Entertainer && $this->hasEntertainerAvatar($Entertainer->id)){
            $url = $this->getEntertainerAvatarURL();
        }
        else{
            $url = $this->getAvatarURL();
        }
    	$avatar = "<img src=\"{$url}\" class=\"{$size}\" alt=\"\" border=\"0\" />";

    	if ($Entertainer and $add_url) {
    	    if ($linkUrl == 'Purchase') {
                $avatar = "<a href=\"?mode=Purchase&job=purchase&type=Entertainer&id={$Entertainer->id}\">{$avatar}</a>";
            } else {
                $avatar = "<a href=\"?mode=EntertainerProfile&entertainer_id={$Entertainer->id}\">{$avatar}</a>";
            }
    	}
    	return $avatar;
    }

    public function getEntertainerAvatarHTML($size = self::AVATAR_LARGE, $add_url = true)
    {
        $url = $this->getEntertainerAvatarURL();
        $avatar = "<img src=\"{$url}\" class=\"{$size}\" alt=\"\" border=\"0\" />";
        return $avatar;
    }

    public function getDisplayPhotoHTML($size = self::DISPLAY_LARGE, $add_url = true)
    {
        $url = $this->getDisplayPhotoURL();
        $avatar = "<img src=\"{$url}\" class=\"{$size}\" alt=\"\" border=\"0\" />";
        $Entertainer = $this->getAccountOfType('Entertainer');
        if ($Entertainer and $add_url) {
            $avatar = "<a href=\"?mode=EntertainerProfile&entertainer_id={$Entertainer->id}\">{$avatar}</a>";
        }
        return $avatar;
    }

    public function getEntertainerDisplayPhotoHTML($size = self::DISPLAY_LARGE, $add_url = true)
    {
        $url = $this->getEntertainerDisplayPhotoURL();
        $avatar = "<img src=\"{$url}\" class=\"{$size}\" alt=\"\" border=\"0\" />";
        return $avatar;
    }

    public function getAvatarURL()
    {
    	return "/avatar.php?a={$this->id}";
    }

    public function getDisplayPhotoURL()
    {
        return "/displayPhoto.php?a={$this->id}";
    }
    public function getEntertainerAvatarURL()
    {
        return "/entertainerAvatar.php?a={$this->id}";
    }

    public function getEntertainerDisplayPhotoURL()
    {
        return "/entertainerDisplayPhoto.php?a={$this->id}";
    }

    public function uploadAvatar()
    {
        $file = $_FILES['image'];
        $sourceImagePath = $file["tmp_name"];

        list($sourceWidth, $sourceHeight, $sourceType) = getimagesize($sourceImagePath);

        switch ($sourceType) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($sourceImagePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($sourceImagePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($sourceImagePath);
                break;
            default:
                return false;
        }

        $maxSize = 250;
        if ($sourceWidth > $sourceHeight) {
            $targetWidth = $maxSize;
            $targetHeight = intval($sourceHeight * ($maxSize / $sourceWidth));
        } else {
            $targetWidth = intval($sourceWidth * ($maxSize / $sourceHeight));
            $targetHeight = $maxSize;
        }

        $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);

        imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        $targetImagePath = DATAROOT . "/private/avatars/avatar{$this->id}.png";

        imagepng($targetImage, $targetImagePath);

        imagedestroy($sourceImage);
        imagedestroy($targetImage);

        return true;
    }

//    public function uploadAvatar()
//    {
//    	$file = $_FILES['image'];
//		move_uploaded_file($file["tmp_name"], DATAROOT . "/private/avatars/avatar{$this->id}.png");
//		return true;
//    }

    public function uploadDisplayPhoto()
    {
        $file = $_FILES['displayPhoto'];
        $sourceImagePath = $file["tmp_name"];

        list($sourceWidth, $sourceHeight, $sourceType) = getimagesize($sourceImagePath);

        switch ($sourceType) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($sourceImagePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($sourceImagePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($sourceImagePath);
                break;
            default:
                return false;
        }

        $maxWSize = 680;
        $maxHSize = 400;

        if ($sourceWidth > $sourceHeight) {
            $targetWidth = $maxWSize;
            $targetHeight = intval($sourceHeight * ($maxWSize / $sourceWidth));
        } else {
            $targetWidth = intval($sourceWidth * ($maxHSize / $sourceHeight));
            $targetHeight = $maxHSize;
        }

        $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);

        imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        $targetImagePath = DATAROOT . "/private/avatars/displayPhoto{$this->id}.png";

        imagepng($targetImage, $targetImagePath);

        imagedestroy($sourceImage);
        imagedestroy($targetImage);

        return true;
    }

//    public function uploadEntertainerAvatar()
//    {
//        $EntertainerProfile = new EntertainerProfile();
//        $x = 20;
//        $y = 20;
//        $hw = 320;
//
//        $file = $_FILES['image'];
//        move_uploaded_file($file["tmp_name"], DATAROOT . "/private/avatars/entertainerAvatar{$this->id}.png");
//
//        $EntertainerProfile->setAvatar($x, $y, $hw);
//
//        return true;
//    }

    public function uploadEntertainerAvatar()
    {
        $file = $_FILES['image'];
        $sourceImagePath = $file["tmp_name"];

        list($sourceWidth, $sourceHeight, $sourceType) = getimagesize($sourceImagePath);

        switch ($sourceType) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($sourceImagePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($sourceImagePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($sourceImagePath);
                break;
            default:
                return false;
        }

        $maxSize = 250;
        if ($sourceWidth > $sourceHeight) {
            $targetWidth = $maxSize;
            $targetHeight = intval($sourceHeight * ($maxSize / $sourceWidth));
        } else {
            $targetWidth = intval($sourceWidth * ($maxSize / $sourceHeight));
            $targetHeight = $maxSize;
        }

        $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);

        imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        $targetImagePath = DATAROOT . "/private/avatars/entertainerAvatar{$this->id}.png";

        imagepng($targetImage, $targetImagePath);

        imagedestroy($sourceImage);
        imagedestroy($targetImage);

        return true;
    }


//    public function uploadEntertainerDisplayPhoto()
//    {
//        $file = $_FILES['displayPhoto'];
//        move_uploaded_file($file["tmp_name"], DATAROOT . "/private/avatars/entertainerDisplayPhoto{$this->id}.png");
//        return true;
//    }
    public function uploadEntertainerDisplayPhoto()
    {
        $file = $_FILES['displayPhoto'];
        $sourceImagePath = $file["tmp_name"];

        list($sourceWidth, $sourceHeight, $sourceType) = getimagesize($sourceImagePath);

        switch ($sourceType) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($sourceImagePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($sourceImagePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($sourceImagePath);
                break;
            default:
                return false;
        }

        $maxWSize = 680;
        $maxHSize = 400;

        if ($sourceWidth > $sourceHeight) {
            $targetWidth = $maxWSize;
            $targetHeight = intval($sourceHeight * ($maxWSize / $sourceWidth));
        } else {
            $targetWidth = intval($sourceWidth * ($maxHSize / $sourceHeight));
            $targetHeight = $maxHSize;
        }

        $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);

        imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        $targetImagePath = DATAROOT . "/private/avatars/entertainerDisplayPhoto{$this->id}.png";

        imagepng($targetImage, $targetImagePath);

        imagedestroy($sourceImage);
        imagedestroy($targetImage);

        return true;
    }

    public function hasAvatar()
    {
    	return file_exists(DATAROOT . "/private/avatars/avatar{$this->id}.png");
    }

    public function hasEntertainerAvatar()
    {
        return file_exists(DATAROOT . "/private/avatars/entertainerAvatar{$this->id}.png");
    }

    public function getReviewFor($entity)
    {
    	$class = get_class($entity);
    	if ($class == 'Entertainer' or $class = 'Club') {
    		$filters = array(new Filter(Filter::Match, array('Member_id' => $this->id, "{$class}_id" => $entity->id)));
    		$reviews = $this->_framework->getSubset('Review', $filters);
    		if (sizeof($reviews)) {
    			return array_pop($reviews);
    		}
    	}
    	return false;
    }

//    public function getMessages()
//    {
//    	$filter = array(new Filter(Filter::Match, array('to_Member_id' => $this->id, 'deleted' => '0')),
//    					new Filter(Filter::Sort, 'send_time DESC'),
//    				   );
//		$messages = $this->_framework->getSubset('Private_Message', $filter);
//		return $messages;
//    }

    public function getMessages($selectedMemberId = 0)
    {
        $filter = array(new Filter(Filter::Match, array('to_Member_id' => $this->id, 'deleted' => '0')),
            new Filter(Filter::Sort, 'send_time DESC'),
        );

        if ($selectedMemberId > 0) {
            $filter[] = new Filter(Filter::Match, array('from_Member_id' => $selectedMemberId));
        }

        $messages = $this->_framework->getSubset('Private_Message', $filter);
        return $messages;
    }



    public function getFilteredMessage($id)
    {
        $filter = array(new Filter(Filter::Match, array('to_Member_id' => $this->id, 'deleted' => '0')),
            new Filter(Filter::Sort, 'send_time DESC'),
        );
        $messages = $this->_framework->getSubset('Private_Message', $filter);
        return $messages;
    }

    public function getPopularEntertainers()
    {
        $filters = array(new Filter(Filter::Match, array('is_deleted' => 0)),
            new Filter(Filter::IsNotNull, 'Member_id'),
            new Filter(Filter::Sort, 'order_list ASC'),
            new Filter(Filter::Limit, 50),
        );
        $entertainers = $this->_framework->getSubset('Entertainer', $filters);

        return $entertainers;
    }

    public function getUnreadMessages()
    {
    	$messages = $this->getMessages();
    	$unread = array();
    	foreach ($messages as $Private_Message)
    	{
    		if (!$Private_Message->receive_time) {
    			$unread[] = $Private_Message;
    		}
    	}
    	return $unread;
    }

    public function getSentMessages()
    {
    	$filter = array(new Filter(Filter::Match, array('from_Member_id' => $this->id)),
    					new Filter(Filter::Sort, 'send_time DESC'),
    				   );
		$messages = $this->_framework->getSubset('Private_Message', $filter);
		return $messages;
    }

    public function getPhotos()
    {
        $filter = array(
            new Filter(Filter::Match, array('Member_id' => $this->id, 'is_deleted' => '0')),
            new Filter(Filter::IN, array('mime_type' => ['image/png', 'image/jpg', 'image/jpeg'])),

        );
        $photos = $this->_framework->getSubset('Library', $filter);
        return $photos;
    }

//    public function getPhoto()
//    {
//        $messages = $this->getPhotos();
//        $unread = array();
//        foreach ($messages as $Private_Message)
//        {
//            if (!$Private_Message->receive_time) {
//                $unread[] = $Private_Message;
//            }
//        }
//        return $unread;
//    }

    public function getVideos()
    {
        $filter = array(
            new Filter(Filter::Match, array('Member_id' => $this->id, 'is_deleted' => '0')),
            new Filter(Filter::Where, 'placement != "shop"'),
            new Filter(Filter::IN, array('mime_type' => ['video/quicktime', 'video/mp4', 'video/mov'])),
        );
        $videos = $this->_framework->getSubset('Library', $filter);
        foreach ($videos as $Library) {
            $Post = $Library->getLibraryPost();
            if (!$Post || $Post['active'] == 0){
                $Library->delete_Job();
            }
        }

        return $videos;
    }

    public function getVideoStore()
    {
        $filter = array(
            new Filter(Filter::Match, array('Member_id' => $this->id, 'is_deleted' => '0', 'File_Type_id' => 4, 'placement' => 'shop')),
            new Filter(Filter::IN, array('mime_type' => ['video/quicktime', 'video/mp4', 'video/mov'])),
        );
        $videos = $this->_framework->getSubset('Library', $filter);
        return $videos;
    }

//    public function getVideo()
//    {
//        $messages = $this->getVideos();
//        $unread = array();
//        foreach ($messages as $Private_Message)
//        {
//            if (!$Private_Message->receive_time) {
//                $unread[] = $Private_Message;
//            }
//        }
//        return $unread;
//    }

    public function getRole()
    {

    	if (StaysailIO::session('Member_role')) {
            return StaysailIO::session('Member_role');
    	}

    	if ($this->getAccountOfType('Fan')) {
    		$role = self::ROLE_FAN;
            //TODO need refactor
//    	} elseif ($this->getAccountOfType('Entertainer')) {
//    		$role = self::ROLE_ENTERTAINER;
//    	} else {
//    		$role = self::ROLE_CLUB;
        } else {
    		$role = self::ROLE_ENTERTAINER;
    	}

    	StaysailIO::setSession('Member_role', $role);

    	return $role;
    }

    public function getPaymentMethods()
    {
    	$filters = array(new Filter(Filter::Match, array('Member_id' => $this->id, 'deleted' => 0)));
    	$payment_methods = $this->_framework->getSubset('Payment_Method', $filters);
    	return $payment_methods;
    }

	public function getUnexpiredPaymentMethod()
	{
		$payment_methods = $this->getPaymentMethods();
		foreach ($payment_methods as $Payment_Method)
		{
			print "  Checking {$Payment_Method->id}\n";
			if (!$Payment_Method->isExpired()) {
				return $Payment_Method;
			}
		}
		return false;
	}

	public function removePaymentMethod(Payment_Method $Payment_Method)
	{
		if (!$Payment_Method->belongsTo($this)) {
			return false;
		}
		$Payment_Method->delete_Job();
		return true;
	}

    public function getRealFullName()
    {
    	return "{$this->last_name}, {$this->first_name}";
    }

    /**
     * Make sure the member is paid up.  If not, direct to the Purchase screen.  Otherwise,
     * simply return silently.
     *
     * Set the MemberIsCurrent session so that external services at this domain can verify
     * that the Member is paid up.
     */
    public function checkStanding()
    {
    	if ($this->getRole() != Member::ROLE_FAN) {
    		StaysailIO::setSession('MemberIsCurrent', true);
    		return true;
    	}
    	$expire_time = strtotime($this->expire_time);
    	if (!$expire_time or time() > $expire_time) {
    		StaysailIO::setSession('MemberIsCurrent', null);
    		header("Location:/?mode=Purchase&job=purchase&type=Member&id={$this->id}");
    		exit;
    	}
    	StaysailIO::setSession('MemberIsCurrent', true);
    	return true;
    }

    public function memberIsPaid()
    {
    	if ($this->getAccountType() == 'Fan') {
	    	$expire_time = strtotime($this->expire_time);
    		if (!$expire_time or time() > $expire_time) {
    			return false;
    		}
    	}
    	return true;
    }

    public function checkActive()
    {
    	return ($this->active_time ? true : false);
    }

    public function extendMembership()
    {
    	// If the expire time hasn't yet arrived, add a month to the expiration date
    	$expire_time = strtotime($this->expire_time);
    	$extra_time = 60 * 60 * 24 * 31;
    	if ($expire_time and $expire_time > time()) {
    		$new_expire_time = $expire_time + $extra_time;
    	} else {
    		$new_expire_time = 	time() + $extra_time;
    	}

  		$this->expire_time = date('Y-m-d', $new_expire_time) . ' 23:59:59';
  		if (!$this->active_time) {
  			$this->active_time = date('Y-m-d H:i:s');
  		}
  		$this->save();
    }

    public function getEmailPrefs()
    {
    	$Member_Email_Prefs = $this->_framework->getSingle('Member_Email_Prefs',
    					     new Filter(Filter::Match, array('Member_id' => $this->id)));
		return $Member_Email_Prefs;
    }

    public function optedOutOfEmail()
    {
    	$Member_Email_Prefs = $this->getEmailPrefs();
    	if ($Member_Email_Prefs) {
    		return $Member_Email_Prefs->optedOut();
    	}
    	return false;
    }

    public function setEmailPrefs($prefs)
    {
    	$Member_Email_Prefs = $this->getEmailPrefs();
    	if (!$Member_Email_Prefs) {
    		$Member_Email_Prefs = new Member_Email_Prefs();
    		$Member_Email_Prefs->update(array('Member_id' => $this->id));
    	}
    	$Member_Email_Prefs->update(array('change_time' => StaysailIO::now(), 'preferences' => $prefs));
		$Member_Email_Prefs->save();
    }

	public function updateOnline()
	{
		$this->online_time = StaysailIO::now();
		$this->save();
	}

	public function validatePromotionalCode()
	{
		// Once the Member has an Active Time, the promotional code can no longer be used
		if ($this->active_time) {return false;}

		$promo = strtolower(trim(StaysailIO::session('promo')));
		$codes = explode(' ', $this->_framework->getSetting('code'));
		foreach ($codes as $code)
		{
			$code = strtolower(trim($code));
			if ($code == $promo) {
				return true;
			}

		}
		return false;
	}

	/**
	 * Given a local time from the database, returns an offset time for the member's time zone.
	 *
	 * @param string $time (in 'Y-m-d H:i:s' format)
	 * @return number (UNIX time)
	 */
	public function localToMemberTime($time)
	{
		$unix = strtotime($time); // Local UNIX time
		$offset = $this->time_zone; // This should be the number of hours to add from local time
		if (is_numeric($offset)) {
			$seconds = $offset * (60 * 60);
			$offset_time = $unix + $seconds;
			return $offset_time;
		}
		return $unix;
	}

	public function isOnline()
	{
		$row = $this->_framework->getRowByID('Member', $this->id);
		$online_time = $row['online_time'];
		$t = strtotime($online_time);

		// Is this time within the last ten seconds?
		if ((time() - $t) < 10) {return true;}
		return false;
	}

	public function validatePhoneNumber()
	{
		$phone = preg_replace('/[^0-9]/', '', $this->phone);
		if (strlen($phone) == 10) {return true;}
		return false;
	}

	public function validateAddress()
	{
		if (trim($this->address) and trim($this->city) and trim($this->state) and trim($this->zip)) {
			return true;
		}
		return false;
	}

    public function requiredEmailVerify()
    {
        if ($this->email_verified == 0){
            header('Location: /?mode=EmailVerify&job=email_verify');
            exit;
        }
    }
}
