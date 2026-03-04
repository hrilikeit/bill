<?php

/*include_once '../private/interfaces/interface.AccountType.php';*/

final class Fan extends StaysailEntity/* implements AccountType*/
{
    // Data model properties
    public $name = parent::Line;
    public $sort = parent::Int;

    public $Member = parent::AssignOne;
    public $active = parent::Boolean;
    public $is_deleted = parent::Boolean;

    // Metadata properties
    //protected $_sort          = 'name ASC';
    //protected $_name_template = '{name}';

    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);
        $this->is_deleted = 0;
        $this->active = 0;
    }

    public function delete_Job()
    {
    	$this->is_deleted = 1;
    	$this->save();
    }
    
    public function copy_Job() {return $this->copy();}

    /* AccountType interface methods.  See interface.AccountType for documentation */
    public function getProfileForm($mode = '', $job = '')
    {
    	$profile = new StaysailForm('form');
    	$profile->setPostMethod()
    	        ->setDefaults($this->info())
    	        ->setSubmit('Update Profile');
    	if ($mode) {
    		$profile->setJobAction($mode, $job);
    	}
//    	$profile->addHTML('Please choose a screen name.  This is the name that entertainers will see:');
//    	$profile->addField(StaysailForm::Line, 'Screen Name', 'name', 'required');
    	$profile->addHTML('<strong>Communication Preferences</strong>');
    	$profile->addField(StaysailForm::Bool, 'Receive Expiration Notices', 'expiration_notice');
    	$profile->addField(StaysailForm::Bool, 'Receive Auto Renewal Notices', 'auto_renew_notice');
    	$profile->addField(StaysailForm::Bool, 'Receive Entertainer Messages via Email', 'entertainer_messages');
    	return $profile;
    }
    
    public function saveProfile()
    {
    	$name = StaysailIO::post('name');
    	$this->name = $name;
    	$this->save();
    }
    
    public function registerSession()
    {
    	StaysailIO::setSession('account_type', __CLASS__);
    	StaysailIO::setSession('account_entity_id', $this->id);
    }
    /* END OF AccountType interface methods */
    
    /**
     * Return a list of active subscriptions for this Fan
     * 
     * @return array<Fan_Subscription>
     */
    public function getActiveSubscriptions()
    {
        $filters = array(new Filter(Filter::Match, array('Fan_id' => $this->id, 'active' => 1)),
            //new Filter(Filter::StringCompare, array('active_time', '<=', StaysailIO::now())),
        );
    	$subscriptions = $this->_framework->getSubset('Fan_Subscription', $filters);
    	$filtered_subscriptions = array();
    	foreach ($subscriptions as $Fan_Subscription)
    	{
    		if (!$Fan_Subscription->Entertainer->isActive()) {continue;}
    		$filtered_subscriptions[] = $Fan_Subscription;
    	}

    	return $filtered_subscriptions;
    }

    public function getActiveEntertainers()
    {
        $filters = array(new Filter(Filter::Match, array('is_active' => 1, 'is_deleted' => 0)),
            new Filter(Filter::Sort, 'id DESC'),
        );
        $entertainers = $this->_framework->getSubset('Entertainer', $filters);
        return $entertainers;
    }

    public function getCountActiveEntertainers()
    {
        $filters = array(new Filter(Filter::Match, array('is_active' => 1)),
            new Filter(Filter::Sort, 'id DESC'),
        );
        $entertainers = $this->_framework->getSubsetCount('Entertainer', $filters);
        return $entertainers;
    }

    public function getActiveEntertainersPages($offset, $limit)
    {
        $filters = array(new Filter(Filter::Match, array('is_active' => 1)),
            new Filter(Filter::Limit, [$offset, $limit]),
            new Filter(Filter::Sort, 'id DESC'),
        );
        $entertainers = $this->_framework->getSubset('Entertainer', $filters);
        return $entertainers;
    }
    
    public function getEntertainerOptionList()
    {
    	$subscriptions = $this->getActiveSubscriptions();
    	$option_list = array();
    	foreach ($subscriptions as $Fan_Subscription)
    	{
    		$Entertainer = $Fan_Subscription->Entertainer;
    		$Member = $Entertainer->Member;
    		$option_list[$Member->id] = $Entertainer->name;
    	}
    	return $option_list;
    }
    
    public function getFavoriteClubs()
    {
    	$filters = array(new Filter(Filter::Match, array('is_deleted' => 0)));
    	$clubs = $this->_framework->getSubset('Club', $filters);
    	return $clubs;
    	
    	
    	$filters = array(new Filter(Filter::Match, array('Fan_id' => $this->id)));
    	$favorites = $this->_framework->getSubset('Fan_Favorite_Club', $filters);
    	$clubs = array();
    	foreach ($favorites as $Fan_Favorite_Club)
    	{
    		$clubs[] = $Fan_Favorite_Club->Club;
    	}
    	return $clubs;
    }
    
    public function getSubscribedClubs()
    {
    	$subscriptions = $this->getActiveSubscriptions();
    	$clubs = array();
    	foreach ($subscriptions as $Fan_Subscription)
    	{
    		$Entertainer = $Fan_Subscription->Entertainer;
    		$entertainer_clubs = $Entertainer->getClubs();
    		foreach ($entertainer_clubs as $Club)
    		{
    			$clubs[$Club->id] = $Club;
    		}
    	}
    	return $clubs;
    	
    }
    
    public function isSubscribedTo(Entertainer $Entertainer)
    {
    	$subscriptions = $this->getActiveSubscriptions();
    	foreach ($subscriptions as $Fan_Subscription)
    	{
    		if ($Fan_Subscription->Entertainer->id == $Entertainer->id) {
    			return $Fan_Subscription;
    		}
    	}
    	return false;
    }
    
    public function isBannedFrom(Entertainer $Entertainer)
    {
    	$Fan_Subscription = $this->isSubscribedTo($Entertainer);
    	if (!$Fan_Subscription) {
    		return false;
    	}
    	return $Fan_Subscription->isBanned();
    }
    
    public function hasPurchased(Library $Library)
    {
    	$filters = array(new Filter(Filter::Match, array('Fan_id' => $this->id, 'Library_id' => $Library->id)));
    	$purchased = $this->_framework->getSubset('Fan_Library', $filters);
    	if (sizeof($purchased)) {
    		$Fan_Library = array_pop($purchased);
    		return $Fan_Library->File_Type;
    	}
    	return false;
    }
    
    public function getPurchasedLibrary()
    {
    	$filters = array(new Filter(Filter::Match, array('Fan_id' => $this->id)));
    	$purchases = $this->_framework->getSubset('Fan_Library', $filters);
    	$purchased_library = array();
    	foreach ($purchases as $Fan_Library)
    	{
    		$Library = $Fan_Library->Library;
    		if ($Library->hasAccess($this->Member)) {
    			$purchased_library[] = $Library;
    		}
    	}
    	return $purchased_library;
    }
    
    public function subscribeTo(Entertainer $Entertainer)
    {
        // First, check for an existing subscription
        $Fan_Subscription = $this->isSubscribedTo($Entertainer);
        if (!$Fan_Subscription) {
            $date = date('Y-m-d 00:00:00', strtotime('+1 month', time()));

            // No existing subscription, so create one
            $Fan_Subscription = new Fan_Subscription();
            $updates = array('Fan_id' => $this->id,
    						 'Entertainer_id' => $Entertainer->id,
    						 'active_time' => StaysailIO::now(),
    						 'expire_time' => $date,
    						 'active' => 1,
    						 'auto_renew' => 1,
                             'banned_until_time' => $date,
    					    );
            $Fan_Subscription->update($updates);
            $Fan_Subscription->save();

//            $Member = new Member(StaysailIO::session('Member.id'));

//            if (class_exists('SMSSender')) {
//                $SMSSender = new SMSSender($Entertainer->Member, 'LocalCityScene');
//                $SMSSender = new SMSSender($Member, 'LocalCityScene');
//				  $message = "You have been Fanned by {$this->name}!";
//                $message = "Welcome to YourFansLive.  This is where you will be notified of new subscribers and other purchases.";
//                $SMSSender->send($message);
//			}
    	}
    	// If the fan has subscribed to the inviter, remove the inviter status
    	if (StaysailIO::session('inviter_entertainer_id') == $Entertainer->id) {
    		StaysailIO::setSession('inviter_entertainer_id', null);
    	}
    }
    
    public function hasConfirmedAge()
    {
    	return StaysailIO::session('confirmed_age');
    }
    
    public function setAgeConfirmation()
    {
    	StaysailIO::setSession('confirmed_age', true);
    }
    
//	public function isOnline()
//	{
//		$row = $this->_framework->getRowByID('Member', $this->Member->id);
//		$online_time = $row['online_time'];
//		$t = strtotime($online_time);
//		// Is this time within the last ten seconds?
//		if ((time() - $t) < 10) {return true;}
//		return false;
//	}


    public function isOnline()
    {
        if ($this->Member->id !== null){
            $row = $this->_framework->getRowByID('Member', $this->Member->id);
            $online_time = $row['online_time'];
            $t = strtotime($online_time);
            // Is this time within the last ten seconds?
            if ((time() - $t) < 10) {return true;}
            return false;
        }
        //$row = $this->_framework->getRowByID('Member', $this->Member->id);
        //$row = $this->_framework->getRowByID('Member', $_SESSION['Member.id']);
        return true;

//		$online_time = $row['online_time'];
//		$t = strtotime($online_time);
//		// Is this time within the last ten seconds?
//		if ((time() - $t) < 10) {return true;}
//		return false;
    }

    /**
	 * Return the Payment_Method for the last time this Fan's membership was renewed
	 */
	public function getLastMemberPaymentMethod()
	{
		$sql = "SELECT `Payment_Method_id`
				FROM `Order`
				INNER JOIN `Order_Line`
				    ON Order_Line.Order_id = Order.id
				        AND Order_Line.description = 'Monthly Member Fee'
				WHERE Order.Member_id = {$this->Member->id}
				ORDER BY Order_Line.line_time DESC";
		$row = $this->_framework->getSingleRow($sql);
		$payment_method_id = $row['Payment_Method_id'];	
		if ($payment_method_id) {
			$Payment_Method = new Payment_Method($payment_method_id);
			return $Payment_Method;
		}	
		return false;
	}
	
	public function getWebShowRequests()
	{
		// Getting requests in the last half hour
		$offset_ring_time = date('Y-m-d H:i:s', time() - (30 * 60));
		$filters = array(new Filter(Filter::Match, array('Member_id' => $this->Member->id)),
						 new Filter(Filter::StringCompare, array('ring_time', '>=', $offset_ring_time)),
		);
		$webshow_requests = $this->_framework->getSubset('WebShow_Request', $filters);
		return $webshow_requests;
	}
	
//	public function notifyEntertainersOfOnline()
//	{
//		$subscriptions = $this->getActiveSubscriptions();
//		foreach ($subscriptions as $Fan_Subscription)
//		{
//			$Entertainer = $Fan_Subscription->Entertainer;
//			$SMSSender = new SMSSender($Entertainer->Member, 'LocalCityScene');
//			$message = "{$this->name} is online!";
//			$SMSSender->send($message);
//		}
//	}
	
	public function isSMSException()
	{
		$exceptions = $this->_framework->getSetting('sms_exceptions');
		$name = $this->Member->name;
		$exception_list = explode(' ', $exceptions);
		if (is_array($exception_list) and in_array($name, $exception_list)) {
			return true;
		}
		return false;
	}


    /**
     * Return a list of active subscriptions for this Fan and Entertainer
     *
     */
    public function isSubscribedToEntertainer($entertainerId)
    {
        $filters = array(new Filter(Filter::Match, array('Fan_id' => $this->id,
            'Entertainer_id' => $entertainerId,
            'active' => 1)),
            new Filter(Filter::StringCompare, array('active_time', '<=', StaysailIO::now())),
        );
        $subscriptions = $this->_framework->getSubset('Fan_Subscription', $filters);
        return count($subscriptions);
    }

    public function checkFan($id)
    {
        $filter = new Filter(Filter::Match, array('id' => $id));
        $Fan = $this->_framework->getSubset('Fan', $filter);
        if ($Fan){
            return true;
        }
        else{
            return false;
        }
    }
}