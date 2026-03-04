<?php

final class Entertainer extends StaysailEntity implements AccountType
{
    // Data model properties
    public $name = parent::Line;
    public $sort = parent::Int;

    public $Member = parent::AssignOne;
    public $Club = parent::AssignMany;
    public $bio = parent::Text;
    public $stage_name = parent::Line;
    public $fan_url = parent::Line;
	public $twitter_user = parent::Line;
	public $twitter_password = parent::Line;
	public $facebook_user = parent::Line;
	public $facebook_password = parent::Line;
	public $ssn = parent::Line;
	public $is_deleted = parent::Boolean;
	public $video_access = parent::Boolean;
	public $is_active = parent::Boolean;
	public $contract_date = parent::Date;
	public $contract_signature = parent::Line;
	public $signup_club_name = parent::Line;
		
    // Metadata properties
    //protected $_sort          = 'name ASC';
    protected $_name_template = '{stage_name}';
    
    public $_rating;
    
    const LibraryWebOnly = true;
    const DefaultSubscription = 'BillyBoy';

    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);	
        $this->_rating = 'No Rating';	
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
    
    	$writer = new StaysailWriter();
    	if (!$this->stage_name) {
	    	$writer->h1('Thank you for signing up!')
	    		   ->p("Please select a stage name.  This will be the only name that is shown to your fans, 
	    				so we suggest that you <strong>do not make your stage name a version of your real name</strong>.");
    	}
    	
    	$profile->addHTML($writer->getHTML());
    	$profile->addField(StaysailForm::Line, 'Stage Name', 'stage_name', 'required')
    			->addField(StaysailForm::Line, 'Social Security Number', 'ssn', 'required');
    	
/*
    	$clubs = $this->_framework->getOptions('Club');
    	$profile->addField(StaysailForm::Select, 'Club', 'Club', 'required', $clubs);
    	
    	$writer = new StaysailWriter();
    	$writer->h1("Social Media Settings (optional)")
    		   ->p("You may set your Twitter and/or Facebook username and password, which will give you
    		   		the option to send certain posts you make to social media sites.  If you choose not
    		   		to set these now, you may set them at any time on your Profile screen.");
		$profile->addHTML($writer->getHTML());    		   
		$profile->addField(StaysailForm::Line, 'Twitter Username', 'twitter_user')
				->addField(StaysailForm::Password, 'Twitter Password', 'twitter_password')
				->addField(StaysailForm::Line, 'Facebook Username', 'facebook_user')
				->addField(StaysailForm::Password, 'Facebook Password', 'facebook_password');
*/
    	return $profile;
    }
    
    public function saveProfile()
    {
    	$fields = array('ssn', 'stage_name', 'twitter_user', 'twitter_password',
    					'facebook_user', 'facebook_password');
    	$this->updateFrom($fields);
    	$club_id = StaysailIO::post('Club');
    	$this->assignClub(new Club($club_id));
    	$this->save();
    	
    	// Look for and add the default subscription
    	$filter = new Filter(Filter::Match, array('name' => self::DefaultSubscription));
    	$fans = $this->_framework->getSubset('Fan', $filter);
    	if (count($fans)) {
    		$Fan = $fans[0];
    		if (!$Fan->isSubscribedTo($this)) {
	    		$Fan->subscribeTo($this, 3650);
    		}
    	}
    }
    
    public function registerSession()
    {
    	StaysailIO::setSession('account_type', __CLASS__);
    	StaysailIO::setSession('account_entity_id', $this->id);
    }
    
    /* END OF AccountType interface methods */
    
    
    /* Methods for returning various groups of Entertainer data */
    
    public function getSubscribers()
    {
    	$filters = array(new Filter(Filter::Match, array('Entertainer_id' => $this->id)),
    					 new Filter(Filter::Match, array('active' => 1)),
    					);
    	$subscribers = $this->_framework->getSubset('Fan_Subscription', $filters);
    	return $subscribers;
    }
    
    public function getFanOptionList()
    {
    	$subscriptions = $this->getSubscribers();
    	$option_list = array();
    	foreach ($subscriptions as $Fan_Subscription)
    	{
    		$Fan = $Fan_Subscription->Fan;
    		$Member = $Fan->Member;
    		$option_list[$Member->id] = $Fan->name;
    	}
    	return $option_list;
    }
    
    public function getSubscriberFans()
    {
    	$subscribers = $this->getSubscribers();
    	$fans = array();
    	foreach ($subscribers as $Fan_Subscription)
    	{
    		$fan_id = $Fan_Subscription->Fan->id;
    		$fan_name = $Fan_Subscription->Fan->name;
    		$fans[$fan_id] = $fan_name;
    	}
    	if (sizeof($fans)) {
	    	asort($fans);
    	}
    	return $fans;
    }
    
    public function isActive()
    {
    	return ($this->is_active and !$this->is_deleted and !$this->Member->is_deleted);
    }
    
    
    public function getShowSchedule()
    {
    	$filters = array(new Filter(Filter::Match, array('Entertainer_id' => $this->id)),
    					 new Filter(Filter::Sort, 'start_time ASC'),
    					 new Filter(Filter::StringCompare, array('end_time', '>', date('Y-m-d H:i:s'))),
			    	    );
		$schedule = $this->_framework->getSubset('Show_Schedule', $filters);
		return $schedule;			    	
    }
    
    public function getGallery($webonly = false)
    {
    	$filters = array(new Filter(Filter::Match, array('Member_id' => $this->Member->id, 'is_deleted' => 0)),
    					 new Filter(Filter::Sort, 'name ASC'),
    				    );
		if ($webonly) {
			$filters[] = new Filter(Filter::Match, array('placement' => 'web'));
		}
		$gallery = $this->_framework->getSubset('Library', $filters);
		return $gallery;    							   
    }
    
    public function getSaleImages()
    {
    	$filters = array(new Filter(Filter::Match, array('Member_id' => $this->Member->id, 'placement' => 'sale', 'is_deleted' => 0)),
    					 new Filter(Filter::Sort, 'name ASC'),
    				    );
		$gallery = $this->_framework->getSubset('Library', $filters);
		return $gallery;    							   
    }
        
    /**
     * Return array of Post items
     * 
     * @return array<Post>
     */
    public function getPosts()
    {
    	$filters = array(new Filter(Filter::Match, array('Member_id' => $this->Member->id)),
    					 new Filter(Filter::IsTrue, 'active'),
    					 new Filter(Filter::IsNull, 'Post_id'),
    					 new Filter(Filter::Sort, 'post_time DESC'),
				    	);
		$posts = $this->_framework->getSubset('Post', $filters);
		return $posts;				    	
    }
    
    public function getLastPost()
    {
    	$filters = array(new Filter(Filter::Match, array('Member_id' => $this->Member->id)),
    					 new Filter(Filter::IsTrue, 'active'),
    					 new Filter(Filter::IsNull, 'Post_id'),
    					 new Filter(Filter::Sort, 'post_time DESC'),
				    	);
		$Post = $this->_framework->getSingle('Post', $filters);
		return $Post;				    	
    }
    
    public function getFanURL()
    {
    	return "?mode=EntertainerProfile&entertainer_id={$this->id}";
    }
    
    public function getClubLink()
    {
    	$writer = new StaysailWriter('club_link');
    	$url = $this->getFanURL();
    	$fan_count = sizeof($this->getSubscriberFans());
    	$avatar_img = $this->Member->getAvatarHTML(Member::AVATAR_LARGE);
    	$link = "<a href=\"{$url}\">{$avatar_img}</a>";
    	$writer->p($this->name);
    	$writer->addHTML($link);
    	if ($fan_count) {
	    	$writer->p("Fans: {$fan_count}");
    	}
    	return $writer;
    }
    
    public static function getEntertainerByFanURL($fan_url)
    {
    	$fan_url = preg_replace('/[^A-Za-z0-9]/', '', $fan_url);
    	$framework = StaysailIO::engage();
    	$filters = array(new Filter(Filter::Match, array('fan_url' => $fan_url, 'is_deleted' => 0)));
    	$clubs = $framework->getSubset('Entertainer', $filters);
    	if (sizeof($clubs)) {
    		return array_pop($clubs);
    	}
    	return null;
    }     
    
    public function hasVideoAccess()
    {
    	return $this->video_access;
    }
    
    public function getReviewSummary($sort = 'review_time')
    {	
		$writer = new StaysailWriter();
    	
		$rating_total = $rating_count = 0;
		$reviews = $this->getReviews($sort);
		foreach ($reviews as $Review)
		{
			$writer->draw($Review);
			if (is_numeric($Review->rating)) {
				$rating_total += $Review->rating;
				$rating_count++;
			}
		}
		if ($rating_count) {
			$this->_rating = $rating_total / $rating_count;
		} else {
			$this->_rating = null;
		}
		
		return $writer;
    }
    
    public function getReviews($sort = 'review_time')
    {
    	$filters = array(new Filter(Filter::Match, array('Entertainer_id' => $this->id)),
    					 new Filter(Filter::Sort, "{$sort} DESC"),
    				    );
		$all_reviews = $this->_framework->getSubset('Review', $filters);    				 
		$reviews = array();
		foreach ($all_reviews as $Review)
		{
			if ($Review->admin_status != 'denied') {
				$reviews[] = $Review;
			}
		}
    	return $reviews;
    }
    
    public function getRating()
    {
    	if (is_numeric($this->_rating)) {
    		return $this->_rating;
    		
    	}
    	$this->getReviewSummary();
    	return $this->_rating;
    }
    
    public function getStarRatingHTML()
    {
    	$rating = $this->getRating();
    	return Icon::getStarRatingHTML($rating);
    	
    }
    
    /**
     * Return an array of Show_Schedule for items that are scheduled on the specified date
     * 
     * @param int $year
     * @param int $month
     * @param int $day
     * @return array<Show_Schedule>
     */
    public function getShowsOnDay($year, $month, $day)
    {
    	$month = str_pad($month, 2, '0', STR_PAD_LEFT);
    	$day = str_pad($day, 2, '0', STR_PAD_LEFT);
    	$date_start = "{$year}-{$month}-{$day} 00:00:00";
    	$date_end = "{$year}-{$month}-{$day} 23:59:59";
    	$filters = array(new Filter(Filter::StringCompare, array('start_time', '>=', $date_start)),
    					new Filter(Filter::StringCompare, array('start_time', '<=', $date_end)),
    					new Filter(Filter::Sort, 'start_time'),
    					new Filter(Filter::Match, array('Entertainer_id' => $this->id)),
    					);
		$shows = $this->_framework->getSubset('Show_Schedule', $filters);
		return $shows;
    }
    	
	public function getChatSince($id, $add_last_id = false)
	{
		$html = '';
		if (!is_numeric($id)) {$id = 0;}
		
		$filters = array(new Filter(Filter::Match, array('Entertainer_id' => $this->id)),
						 new Filter(Filter::NumberCompare, array('id', '>', $id)),
						 new Filter(Filter::Sort, 'id'),
						);
		$posts = $this->_framework->getSubset('MeetingPost', $filters);
		$last_id = 0;
		foreach ($posts as $MeetingPost)
		{
			$last_id = $MeetingPost->id;
			
			// This is an example of intercepting a non-message notice for insertion into the chat window
			if ($MeetingPost->content == '[[DOORBELL]]') {
				$Member = new Member(StaysailIO::session('Member.id'));
				if ($Member->getRole() == Member::ROLE_ENTERTAINER) {
					$Fan = $MeetingPost->Member->getAccountOfType('Fan');
					$html .= "<p class=\"doorbell\"><strong>{$Fan->name}</strong> rang your doorbell!</p>\n";
				}
				continue;
			}
			
			$html .= "<p>{$MeetingPost->content}</p>\n";
		}
		if ($add_last_id) {
			if (!$last_id) {$last_id = $id;}
			$html = "{$last_id}|||||{$html}";
		}
		
		return $html;
	}
	
	public function removePosts()
	{
		$filters = array(new Filter(Filter::Match, array('Meeting_id' => $this->id)));
		$posts = $this->_framework->getSubset('MeetingPost', $filters);
		foreach ($posts as $MeetingPost)
		{
			$MeetingPost->remove();
		}
		
		$participants = $this->_framework->getSubset('MeetingParticipant', $filters);
		foreach ($participants as $MeetingParticipant)
		{
			$MeetingParticipant->remove();
		}
	}   
	
	public function isWithClub(Club $Club)
	{
		return in_array($Club->id, $this->Club_id);
	}
	
	public function assignClub(Club $Club)
	{
		if ($Club) {
			$current_clubs = $this->Club_id;
			if (!in_array($Club->id, $current_clubs)) {
				$current_clubs[] = $Club->id;
				$this->setMembers('Club', $current_clubs);
				$this->save();
			}
		}
	}
	
	public function getClubs()
	{
		$clubs = array();
		foreach ($this->Club_id as $club_id)
		{
			if ($club_id) {
				$Club = new Club($club_id);
				$clubs[] = $Club;
			}
		}
		return $clubs;
	}
	
	public function unassignClub(Club $Club)
	{
		$current_clubs = $this->Club_id;
		if (in_array($Club->id, $current_clubs)) {
			$new_clubs = array();
			foreach ($current_clubs as $club_id)
			{
				if ($club_id != $Club->id) {
					$new_clubs[] = $club_id;
				}
			}
			$this->setMembers('Club', $new_clubs);
			$this->save();
		}
	}
	
	public function showInProgress()
	{
		if (!$this->isOnline()) {return false;}
		
		$WebShow = $this->_framework->getSingle('WebShow',
			new Filter(Filter::Match, array('Entertainer_id' => $this->id, 'running' => 1)));
		return $WebShow;
	}
	
	public function privateShowInProgress(Fan $Fan)
	{
		$WebShow = $this->_framework->getSingle('WebShow',
			new Filter(Filter::Match, array('Entertainer_id' => $this->id, 'running' => 1, 'Fan_id' => $Fan->id)));
		return $WebShow;
	}
	
	public function endWebShows()
	{
		$filter = new Filter(Filter::Match, array('Entertainer_id' => $this->id));
		$webshows = $this->_framework->getSubset('WebShow', $filter);
		foreach ($webshows as $WebShow)
		{
			$WebShow->end();
		}
	}
	
	public function getPrefillMetadata()
	{
    	$data = Library::getMetadataTypes();
    	$prefill = array_keys($data);
    	
    	$prefill['full_name'] = $this->Member->getRealFullName();
    	$prefill['stage_name'] = $this->name;
    	$prefill['username'] = $this->Member->email;
    	return $prefill;
	}
	
	public function getWebShowRequests()
	{		
		$filter = new Filter(Filter::Match, array('Entertainer_id' => $this->id, 'delivered' => 0));
		$requests = $this->_framework->getSubset('WebShow_Request', $filter);
		return $requests;
	}
	
	public function isOnline()
	{
		$row = $this->_framework->getRowByID('Member', $this->Member->id);
		$online_time = $row['online_time'];
		$t = strtotime($online_time);
		// Is this time within the last ten seconds?
		if ((time() - $t) < 10) {return true;}
		return false;
	}
	
	public function isAvailable()
	{
		if ($this->hasVideoAccess() and $this->isOnline() and !$this->showInProgress()) {
			return true;
		}
	}
	
	public function getPrivateRequests()
	{
		$filters = array(new Filter(Filter::Match, array('Entertainer_id' => $this->id, 'private_request' => 1)));
		$requests = $this->_framework->getSubset('WebShow_Request', $filters);
		$requesting_fans = array();
		foreach ($requests as $WebShow_Request)
		{
			$Fan = $WebShow_Request->Member->getAccountOfType('Fan');
			if ($Fan and $Fan->isOnline()) {
				$requesting_fans[$Fan->id] = $Fan->name;
			}
		}
		return $requesting_fans;
	}
	
	public function checkContract()
	{
		if ($this->contract_date and $this->contract_signature) {return true;}
		return false;
	}
	
	public function signEntertainerAgreement($signature)
	{
		$this->contract_signature = $signature;
		$this->contract_date = date('Y-m-d');
		$this->save();
	}
	
	public function sendWelcomeEmail()
	{
		$message = <<<__END__
	Welcome and Thank You for joining LocalStripFan.com.  You are almost ready to increase your income as an Entertainer.
	
	Before we can activate your membership, we need you to fax or e-mail your driver's license or ID and social security card to memberservices@localstripfan.com.
	
	The easiest way to do this is to take a photo with your phone and email directly from your phone.
	
	We recommend that once you complete the email you erase the photos for your protection.  We will then email you the Club ID 
	number that you will punch in through the "Add a Club" button on your home page.  Please get familiar with your home page as 
	there is valuable information and training through "Armani's Corner" to help increase you financial capabilities through our
	site.
	
	And finally I want you to know that we have the highest security and safety measures with our site and we are always looking
	for suggestion to improve the site environment for you and your fans.  You are the one in control and can block or remove 
	comments at any time.  Comments can be done by fans only and only paid members have access.  You can contact me 24/7 at
	memberservices@localstripfan.com.
	
	You may download our Entertainer Agreement for your records at: www.localstripfan.com/LSF-Entertainer-Agreement.pdf

	Sincerely,
	Billyboy
__END__;
		
		$email = $this->Member->email;
		if ($email) {
			$email = preg_replace('/[,\n]/', '', $email);
			mail($email, 'Welcome!', $message, "reply-to:memberservices@localstripfan.com");
			return true;
		}
		return false;
	}
}