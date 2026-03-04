<?php

//require_once 'UsePhpMailer.php';
require '../private/domain/class.WebShowDyte.php';
/*require_once 'class.MailSend.php';
use public_html\subdomains\domain\MailSend;*/

final class Entertainer extends StaysailEntity/* implements AccountType*/
{
    // Data model properties
    public $name = parent::Line;
    public $sort = parent::Int;
    public $order_list = parent::Int;

    public $Member = parent::AssignOne;
    public $position = parent::Enum;
    //public $Club = parent::AssignMany;
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
	public $private = parent::Boolean;
	public $aliases = parent::Line;
	public $maiden_name = parent::Line;
	public $birth_date = parent::Date;
	public $marketing = parent::Enum;
	public $subscription_pricing = parent::Currency;
	public $group_show_price = parent::Currency;
	public $private_show_price = parent::Currency;
	public $allow_custom_pricing = parent::Boolean;
	public $referrer_name = parent::Line;
	public $referrer_id = parent::Int;
	public $seems_like = parent::Boolean;

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
        $this->order_list = ($this->order_list == '') ? 0 : $this->order_list;
        $this->position = ($this->position == '') ? "Entertainer" : $this->position;
        $this->is_deleted = ($this->is_deleted == '') ? 0 : $this->is_deleted;
        $this->video_access = ($this->video_access == '') ? 0 : $this->video_access;
        $this->is_active = ($this->is_active == '') ? 0 : $this->is_active;
        $this->contract_date = ($this->contract_date == '') ? null : $this->contract_date;
        $this->private = ($this->private == '') ? null : $this->private;
        $this->birth_date = ($this->birth_date == '') ? null : $this->birth_date;
        $this->marketing = ($this->marketing == '') ? null : $this->marketing;
        $this->group_show_price = ($this->group_show_price == '') ? null : $this->group_show_price;
        $this->private_show_price = ($this->private_show_price == '') ? null : $this->private_show_price;
        $this->referrer_id = ($this->referrer_id == '') ? null : $this->referrer_id;
        $this->subscription_pricing = ($this->subscription_pricing == '') ? 0.00 : $this->subscription_pricing;
        $this->allow_custom_pricing = ($this->allow_custom_pricing == '') ? 0.00 : $this->allow_custom_pricing;
        $this->seems_like = ($this->seems_like == '') ? 0 : $this->seems_like;
    }

    public function position_Options()
    {
    	return array('Entertainer' => 'Entertainer', 'Manager' => 'Manager',
    				 'Bartender' => 'Bartender', 'Waitstaff' => 'Waitstaff',
    	            );
    }

    public function delete_Job()
    {
    	$this->is_deleted = 1;
    	$this->save();
    }

    public function marketing_Options()
    {
    	return array(
    				 'regional' => 'regional',
    				 'national' => 'national',
    				);
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
    	$profile->addField(StaysailForm::Line, 'Referrer Name', 'referrer_name', 'nullable referrer_name','','','','', '')
                ->addHTML('<small class="field_referrer_name">
                            If you were referred put who referred you here.
                           </small>')
                ->addField(StaysailForm::Line, 'Stage Name', 'stage_name', 'required')
    			->addField(StaysailForm::Line, 'Social Security Number', 'ssn', 'required','','','(Outside US put countries tax doc #)');

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
    	$fields = array('ssn', 'stage_name', 'referrer_name', 'twitter_user', 'twitter_password',
    					'facebook_user', 'facebook_password');
    	$this->updateFrom($fields);
//    	$this->stage_name = str_replace(' ', '_', trim(StaysailIO::post('stage_name')));
    	/*$club_id = StaysailIO::post('Club');
    	$this->assignClub(new Club($club_id));*/
        if (!isset($this->Member_id)) {
            $this->Member_id = StaysailIO::session('Member.id');
        }

    	$this->save();

    	// Look for and add the default subscription
    	$filter = new Filter(Filter::Match, array('name' => self::DefaultSubscription));
    	$fans = $this->_framework->getSubset('Fan', $filter);
        if (count($fans)) {
    		$Fan = $fans[0];

    		if (!$Fan->isSubscribedTo($this)) {
	    		$Fan->subscribeTo($this, 3650);
	    		$this->group_show_price = 1.99;
	    		$this->private_show_price = 5.99;
	    		$this->save();
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
    	$subscriptions = $this->_framework->getSubset('Fan_Subscription', $filters);
    	$subscribers = array();
    	foreach ($subscriptions as $Fan_Subscription)
    	{
    		$Fan = $Fan_Subscription->Fan;
            $fanExist = $Fan->checkFan($Fan->id);
    		if ($fanExist && !$Fan->is_deleted) {
    		    $subscribers[] = $Fan_Subscription;
    		}
    	}
    	return $subscribers;
    }

    public function getActiveSubscribers()
    {
    	$filters = array(new Filter(Filter::Match, array('Entertainer_id' => $this->id)),
            new Filter(Filter::Match, array('active' => 1)),
        );
    	$subscriptions = $this->_framework->getSubset('Fan_Subscription', $filters);
    	$subscribers = array();
    	foreach ($subscriptions as $Fan_Subscription)
    	{
    		$Fan = $Fan_Subscription->Fan;
            $fanExist = $Fan->checkFan($Fan->id);
    		if ($fanExist && !$Fan->is_deleted) {
    		    $subscribers[] = $Fan_Subscription;
    		}
    	}

    	return $subscribers;
    }

    public function getUnactiveSubscribers()
    {
    	$filters = array(new Filter(Filter::Match, array('Entertainer_id' => $this->id)),
            new Filter(Filter::Match, array('auto_renew' => 0)),
        );
    	$subscriptions = $this->_framework->getSubset('Fan_Subscription', $filters);
    	$subscribers = array();
    	foreach ($subscriptions as $Fan_Subscription)
    	{
    		$Fan = $Fan_Subscription->Fan;
            $fanExist = $Fan->checkFan($Fan->id);
    		$subscribers[] = $Fan_Subscription;

    	}

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

     public function getImageGallery($webonly = false)
    {
    	$filters = array(new Filter(Filter::Match, array('Member_id' => $this->Member->id, 'is_deleted' => 0,'File_Type_id' => 3)),
    					 new Filter(Filter::Sort, 'name ASC'),
    				    );
		/*if ($webonly) {
			$filters[] = new Filter(Filter::Match, array('placement' => 'web'));
		}*/
		$gallery = $this->_framework->getSubset('Library', $filters);
		return $gallery;
    }

    public function getGallery($webonly = false)
    {
        $filters = array(
            new Filter(Filter::Match, array('Member_id' => $this->Member->id, 'is_deleted' => 0)),
            new Filter(Filter::IsNull, 'gallery_id'),
            new Filter(Filter::Sort, 'name ASC')
        );
		if ($webonly) {
			$filters[] = new Filter(Filter::Match, array('placement' => 'web'));
		}
		$gallery = $this->_framework->getSubset('Library', $filters);

		return $gallery;
    }

    public function getSaleImages()
    {
    	$filters = array(new Filter(Filter::Match, array('Member_id' => $this->Member->id, 'placement' => 'sale', 'is_deleted' => 0, 'File_Type_id' => 3)),
    					 new Filter(Filter::Sort, 'name ASC'),
    				    );
		$gallery = $this->_framework->getSubset('Library', $filters);
		return $gallery;
    }

    public function getSaleVideos()
    {
    	$filters = array(new Filter(Filter::Match, array('Member_id' => $this->Member->id, 'placement' => 'sale', 'is_deleted' => 0, 'File_Type_id' => 4)),
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

    public function getPostsPagination($offset, $limit)
    {
        $filters = array(new Filter(Filter::Match, array('Member_id' => $this->Member->id)),
            new Filter(Filter::IsTrue, 'active'),
            new Filter(Filter::IsNull, 'Post_id'),
            new Filter(Filter::Limit, array($offset, $limit)),
            new Filter(Filter::Sort, 'post_time DESC'),
        );

        $posts = $this->_framework->getSubset('Post', $filters);
        return $posts;
    }

    public function getPostsCount()
    {
        $filters = array(new Filter(Filter::Match, array('Member_id' => $this->Member->id)),
            new Filter(Filter::IsTrue, 'active'),
            new Filter(Filter::IsNull, 'Post_id'),
            new Filter(Filter::Sort, 'post_time DESC'),
        );

        $postsCount = $this->_framework->getSubsetCount('Post', $filters);
        return $postsCount;
    }


    /**
     * Return array of Post items
     *
     * @return array<Post>
     */
    public function getSlides()
    {
    	$filters = array(new Filter(Filter::Match, array('Member_id' => $this->Member->id)),
    					 new Filter(Filter::IsTrue, 'active'),
    					 new Filter(Filter::IsNull, 'Post_id'),
    					 new Filter(Filter::IsNotNull, 'Library.gallery_id'),
    					 new Filter(Filter::Sort, 'post_time DESC'),
				    	);

		$posts = $this->_framework->getSubset('Post', $filters, "LEFT JOIN `Library` ON Library.id = Post.Library_id");
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

    public function getSearchLink()
    {
    	$avatar = $this->Member->getAvatarHTML(Member::AVATAR_TINY);
    	return "<div class=\"search_result\">{$avatar} {$this->name}</div><br/>";
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

	public function getChatSince($id, $add_last_id = false, $WebShow = null)
	{
		$html = '';
		if (!is_numeric($id)) {$id = 0;}

		$matches = array('Entertainer_id' => $this->id);
		if ($WebShow) {
			$matches['WebShow_id'] = $WebShow->id;
		}
		$filters = array(new Filter(Filter::Match, $matches),
						 new Filter(Filter::NumberCompare, array('id', '>', $id)),
						 new Filter(Filter::Sort, 'id'),
						);
		$posts = $this->_framework->getSubset('MeetingPost', $filters);
		$last_id = 0;
		foreach ($posts as $MeetingPost)
		{
			if ($WebShow === null and $MeetingPost->WebShow) {continue;}
			$last_id = $MeetingPost->id;
			$content = $MeetingPost->content;
			$content = str_replace('%TIME%', date('m/d/y h:ia', strtotime($MeetingPost->post_time)), $content);
			$html .= "<p>{$content}</p>\n";
		}
		if ($add_last_id) {
			if (!$last_id) {$last_id = $id;}
			$html = "{$last_id}|||||{$html}";
		}

		return $html;
	}

	public function getEntertainers($position = 'Entertainer', $stage_name = '')
    {
    	$sql = "SELECT DISTINCT `id`
    			FROM `Entertainer`
    			WHERE `is_deleted` = 0
    				AND `position` = '{$position}'
    				AND `fan_url` = '{$stage_name}'";
    	$this->_framework->query($sql);
    	$entertainers = $entertainer_ids = array();
    	while ($row = $this->_framework->getNextRow())
    	{
    		$entertainer_ids[] = $row['id'];
    	}
    	foreach ($entertainer_ids as $entertainer_id)
    	{
    		$entertainers[] = new Entertainer($entertainer_id);
    	}
    	return $entertainers;
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

	public function assignClubsByID(array $club_ids)
	{
		$this->setMembers('Club', $club_ids);
		$this->save();
	}

	public function getClubs()
	{
		$clubs = array();
		/*foreach ($this->Club_id as $club_id)
		{
			if ($club_id) {
				$Club = new Club($club_id);
				$clubs[] = $Club;
			}
		}*/
		return $clubs;
	}

	public function unassignClub(Club $Club)
	{
		//$current_clubs = $this->Club_id;
		$current_clubs = '';
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

	public function getWebShowDyte($showType = 1)
    {
        $webShowDyteData = $this->_framework->getRowByConditions('WebShowDyte',
            [
                'Member_id' => $this->Member->id,
                'show_type' => $showType
            ]
        );
        return isset($webShowDyteData['id']) ? new WebShowDyte($webShowDyteData['id']) : null;
    }



    public function getLastWebShowDyte($showType = 1)
    {
        $webShowDyteData = $this->_framework->getRowByConditions('WebShowDyte',
            [
                'Member_id' => $this->Member->id,
                'show_type' => $showType
            ]
        ,'id', 'DESC');
        return isset($webShowDyteData['id']) ? new WebShowDyte($webShowDyteData['id']) : null;
    }

	public function getFanWebShowDyte($Fan_id)
    {
        $webShowDyteData = $this->_framework->getRowByConditions('WebShowDyte',
            [
                'Member_id' => $this->Member->id,
                'show_type' => 0,
                'Fan_id' => $Fan_id
            ]
        );
        return isset($webShowDyteData['id']) ? new WebShowDyte($webShowDyteData['id']) : null;
    }

    public function getLastFanWebShowDyte($Fan_id)
    {
        $webShowDyteData = $this->_framework->getRowByConditions('WebShowDyte',
            [
                'Member_id' => $this->Member->id,
                'show_type' => 0,
                'Fan_id' => $Fan_id
            ]
            ,'id', 'DESC');
        return isset($webShowDyteData['id']) ? new WebShowDyte($webShowDyteData['id']) : null;
    }

    public function getLastPublicShow($showType = 2)
    {
        $publicShowData = $this->_framework->getRowByConditions('WebShowDyte',
            [
                'Member_id' => $this->Member->id,
                'show_type' => $showType
            ]
            ,'id', 'DESC');

        return isset($publicShowData['id']) ? new WebShowDyte($publicShowData['id']) : null;
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

	public function groupShowInProgress()
	{
		$WebShow = $this->_framework->getSingle('WebShow',
			array(
				new Filter(Filter::Match, array('Entertainer_id' => $this->id, 'running' => 1)),
				new Filter(Filter::IsNull, 'Fan_id')
			));
		return $WebShow;
	}

	public function endWebShows()
	{
		$filter = new Filter(Filter::Match, array('Entertainer_id' => $this->id, 'running' => 1));
		$webshows = $this->_framework->getSubset('WebShow', $filter);
		foreach ($webshows as $WebShow)
		{
			if ($WebShow->id == 202) {continue;} //TODO:Get rid of this
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
    	$prefill['DOB'] = $this->birth_date;
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
		$online_time = !empty($row['online_time']) ? $row['online_time'] : '';
		$t = strtotime($online_time);

		// Is this time within the last ten seconds?
		if ((time() - $t) < 10) {return true;}
		return false;
	}

	public function isAvailable($Fan)
	{
        if ($Fan){
            if ($this->hasVideoAccess() and $this->isOnline() and $Fan->isSubscribedToEntertainer($this->id)) {
                return true;
            }
        }
//        if ($this->hasVideoAccess() and $this->isOnline() and !$this->showInProgress()) {
//            return true;
//        }
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

	public function clearPrivateRequests()
	{
		$sql = "DELETE FROM `WebShow_Request`
				WHERE Entertainer_id = {$this->id}";
		$this->_framework->query($sql);
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
		return $this->save();
	}

	public function sendWelcomeEmail()
	{

	/*	$message = <<<__END__
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
__END__; */

$name = $this->Member->name;
$message = <<<__END__
	
<!doctype html>
<html lang="en-US">

<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <title>Reset Password Email Template</title>
    <meta name="description" content="Reset Password Email Template.">
    <style type="text/css">
        a:hover {text-decoration: underline !important;}
    </style>
</head>

<body marginheight="0" topmargin="0" marginwidth="0" style="margin: 0px; background-color: #f2f3f8;" leftmargin="0">
  
    <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#f2f3f8"
        style="@import url(https://fonts.googleapis.com/css?family=Rubik:300,400,500,700|Open+Sans:300,400,600,700); font-family: 'Open Sans', sans-serif;">
        <tr>
            <td>
                <table style="background-color: #f2f3f8; max-width:670px;  margin:0 auto 25px;" width="100%" border="0"
                    align="center" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="height:80px;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="text-align:center;">
                          <a href="https://stage.yourfanslive.com/" title="logo" target="_blank">
                            <img style="max-width: 500px; width: 100%;" src="https://stage.yourfanslive.com/site_img/unnamed.jpg" alt="https://stage.yourfanslive.com/">
                          </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="height:20px;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td>
                            <table width="95%" border="0" align="center" cellpadding="0" cellspacing="0"
                                style="max-width:670px;background:#fff; border-radius:3px; text-align:center;-webkit-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);-moz-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);box-shadow:0 6px 18px 0 rgba(0,0,0,.06);">
                                <tr>
                                    <td style="height:40px;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td style="padding:0 35px;">
                                        <h1 style="color:#1e1e2d; font-weight:500; margin:0;font-size:32px;font-family:'Rubik',sans-serif;">Get Started</h1>
                                        <span
                                            style="display:inline-block; vertical-align:middle; margin:29px 0 26px; border-bottom:1px solid #cecece; width:100px;"></span>
                                            <p style="color:#455056; font-size:15px;line-height:24px; margin:0; text-align: start;">
                                              You've joined the site and now we need to verify your age and identity before you can start posting content
                                            </p>

                                            <p style="color:#455056; font-size:15px;line-height:24px; margin:0; text-align: start; font-weight: bold;">
                                              -  Take a photo of ID: Front and back - make sure you can clearly read it 
                                            </p>

                                            <p style="color:#455056; font-size:15px;line-height:24px; margin:0; text-align: start; font-weight: bold;">
                                              -  Take a clear photo of you holding up your ID next to your face with the ID information visible.
                                            </p>

                                            <p style="color:#455056; font-size:15px;line-height:24px; margin:0; text-align: start; font-weight: bold;">
                                              -  Send photos to <a href="mailto:Support@YourFansLive.com">Support@YourFansLive.com</a>
                                            </p>

                                            <p style="color:#455056; font-size:15px;line-height:24px; margin:0; text-align: start;">
                                              We are working on partnering with ID.me for verification in the future
                                            </p>

                                            <p style="color:#455056; font-size:15px;line-height:24px; margin:0; text-align: start; font-weight: bold;">
                                              Your Profile URL is <a href="https://stage.yourfanslive.com/index.php?mode=EntertainerProfile&entertainer_id={$this->id}" target="_blank">www.stage.yourfanslive.com/{$name}</a> 
                                            </p>

                                            <p style="color:#455056; font-size:15px;line-height:24px; margin:0; text-align: start;">
                                              We will send you a personal QR code to your page soon
                                            </p>

                                            <div class="" style="text-align: start;color: #455056;
                                            font-size: 15px;
                                            line-height: 24px;
                                            margin: 0;">
                                              <h2 style="color: #1e1e2d;
                                              font-weight: 500;
                                              font-family: 'Rubik',sans-serif;">
                                              Once you are Verified you can:</h2>

                                              <p>- Edit your Profile photo and Banner</p>
                                              <p>-Start Posting</p>
                                              <p>When posting content in which you have collaborated with other creators:</p>
                                              <p>-Tag the other creator in your post, if they are not yet active on the site invite them to join. This will make collaboration content posting easier for you and give them an opportunity to use the site as well.</p>
                                              <p>-Have ID's and a 2257 for the other creators available if they are not currently signed up for the site. This helps keep with current laws and guidelines.</p>
                                              <p>-We offer 1% referral earnings for any models who sign up with your referral (referral links coming soon)</p>
                                            </div>

                                            <div class="" style="text-align: start;color: #455056;
                                            font-size: 15px;
                                            line-height: 24px;
                                            margin: 0;">
                                              <h2 style="color: #1e1e2d;
                                              font-weight: 500;
                                              font-family: 'Rubik',sans-serif;">
                                              Payouts</h2>

                                              <h4>HOW IT WORKS </h4>

                                              <p>- Weekly payouts for earnings of $100 or more per week</p>
                                              <p>- BiWeekly Payouts for earnings under $100 per week</p>
                                              <p>-View sales reports and statistics through your profile menu</p>

                                              <h4 style="font-weight: bold;">PAYOUT PERCENTAGES</h4>
                                              <p>- 85% for earnings $0.01 to $5,000</p>
                                              <p>-90% for earnings $5,001 to $10,000</p>
                                              <p>- 95% for earning $10,001 and above</p>
                                              <p>-1% for life of any earnings of referred creators while they are active on the site</p>
                                              <p style="margin-top: 35px;">To receive communications and important information on site updates and keep them from going into your spam folder</p>

                                              <h4>please add <a href="mailto:Support@YourFansLive.com">Support@YourFansLive.com</a> to your email contacts today.</h4>

                                              <p style="font-weight: bold;">Be sure to follow us on social media</p>
                                              <p style="font-weight: bold;">Instagram.com/yourfanslive</p>
                                              <p style="font-weight: bold;">Twitter.com/yourfanslive</p>
                                              <a href="twitter.com/YFLsupport" target="_blank">twitter.com/YFLsupport</a>
                                              <p style="font-weight: bold;">That way we can promote you to our followers!</p>
                                              <p style="font-weight: bold;">You may also receive emails from <a href="mailto:edynblairyfl@gmail.com ">edynblairyfl@gmail.com </a></p>
                                              <p style="font-weight: bold;">Edyn is our Creator Support Rep and Social Media Manager.</p>
                                            </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="height:40px;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align:center;">
                          <a href="https://stage.yourfanslive.com/" target="_blank">
                            <img style="max-width: 636px; width: 100%;" src="https://stage.yourfanslive.com/site_img/unnamed1.jpg">
                          </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
__END__;

		$email = $this->Member->email;
		$usePhpMailler = new UsePhpMailer();

		return  $usePhpMailler->sendEmail(
					$email,
					'Welcome!',
					$message,
					'support@yourfanslive.com',
					'support@yourfanslive.com',
					'support@yourfanslive.com'
				);

	/*	if ($email) {
			$email = preg_replace('/[,\n]/', '', $email);
			$headers  = 'MIME-Version: 1.0' . "\r\n".
					    'Content-type: text/html; charset=iso-8859-1' . "\r\n".
						'From: support@yourfanslive.com' . "\r\n".
						'Reply-To: ' . "\r\n".
						'Cc: support@yourfanslive.com';

			mail($email, 'Welcome!', $message, $headers);
			return true;
		}
		return false;*/
	}

	public function performImageMarketing()
	{

	}

    public function notifyFansOfOnline()
    {
        $fans = $this->getSubscribers();
        foreach ($fans as $Fan_Subscription) {
            $Fan = $Fan_Subscription->Fan;
            $fanMember = new Member($Fan->Member_id);
            $message = "{$this->stage_name} is online!";
            $subject = "Online!";
            $MailSend = new MailSend($fanMember);
            $MailSend->send($fanMember->email, $subject, $message, 0, false);

//            if ($Fan->Member && $Fan->Member->phone && $Fan->Member->sms_optout == 0){
//                $SMSSender = new SMSSender($Fan->Member, 'LocalCityScene');
//                $message = "{$this->stage_name} is online!";
//                $SMSSender->send($message);
//            }
        }
    }

	public function getShowPriceFor($show_type)
	{
		if ($show_type == 'group') {
			return $this->group_show_price;
		}
		if ($show_type == 'free') {
			return 0.00;
		}
		return $this->private_show_price;
	}

	public function validateBirthdate()
	{
		if (trim($this->birth_date)) {
			return true;
		}
		return false;
	}

	public function getFormattedBirthdate()
	{
		if (!$this->birth_date) {return '';}
		$time = strtotime($this->birth_date);
		return date('m/d/Y', $time);
	}

	public function get_stage_name($stage_name){
		/*$filters = array(new Filter(Filter::StringCompare, array('stage_name','LIKE','{$stage_name}')),
						new Filter(Filter::Match, array('is_deleted' => 0)),
					);
		//$result = $this->_framework->getSubset('Entertainer', $filters);*/
		$query = "SELECT * FROM `Entertainer` WHERE `stage_name` LIKE '%{$stage_name}%' ORDER BY `stage_name`";

		$this->_framework->query($query);

		$html = '<ul id="country-list">';
	    while ($row = $this->_framework->getNextRow()){
			$html .= "<li en_id=\"".trim($row["id"])."\">".$row["stage_name"]."</li>";
		}
		$html .= "</ul>";
    	return $html;
	}

    public function getPhotos()
    {
        $filter = array(
            new Filter(Filter::Match, array('Member_id' => $this->Member_id, 'is_deleted' => '0')),
            new Filter(Filter::IN, array('mime_type' => ['image/png', 'image/jpg', 'image/jpeg'])),

        );
        $photos = $this->_framework->getSubset('Library', $filter);
        return $photos;
    }

    public function getVideos()
    {
        $filter = array(
            new Filter(Filter::Match, array('Member_id' => $this->Member_id, 'is_deleted' => '0')),
            new Filter(Filter::Where, 'placement != "shop"'),
            new Filter(Filter::IN, array('mime_type' => ['video/quicktime', 'video/mp4', 'video/mov'])),
        );
        $videos = $this->_framework->getSubset('Library', $filter);

        return $videos;
    }

    public function getVideoStore()
    {
        $filter = array(
            new Filter(Filter::Match, array('Member_id' => $this->Member_id, 'is_deleted' => '0', 'File_Type_id' => 4, 'placement' => "shop")),
            new Filter(Filter::IN, array('mime_type' => ['video/quicktime', 'video/mp4', 'video/mov'])),
        );
        $videos = $this->_framework->getSubset('Library', $filter);

        return $videos;
    }

    public function getVideosFan()
    {
        $filter = array(
            new Filter(Filter::Match, array('Member_id' => $this->Member_id, 'is_deleted' => '0')),
            new Filter(Filter::Where, 'placement != "shop"'),
            new Filter(Filter::IN, array('mime_type' => ['video/quicktime', 'video/mp4', 'video/mov'])),
            new Filter(Filter::Sort, 'id DESC'),
        );
        $videos = $this->_framework->getSubset('Library', $filter);
        return $videos;
    }

    public function getAvatarLink()
    {
        $avatar = $this->Member->getAvatarHTML(Member::AVATAR_LITTLE, true);
        $online = $this->isOnline() ? ' - <strong>Online</strong>' : '';
        $entertiner = new Entertainer($this->id);
        $entertainer_link = "<div class=\"avatar_link\">{$entertiner->stage_name}{$online}<br/>"
            . $avatar
            . '</div>';
        return $entertainer_link;
    }

    public function getAvatarUrl()
    {
        $avatar_url = $this->Member->getAvatarHTML(Member::AVATAR_LITTLE, true);
        return $avatar_url;
    }

    public function getMemberDocs()
    {
        $memberDocs = $this->_framework->getRowByField('Member_Docs', 'Member_id', $this->Member_id);
        return new Member_Docs(isset($memberDocs['id']) ? $memberDocs['id'] : null);
    }

    public function getLiveLink()
    {
        $job = StaysailIO::get('job');
        if ($job){
            $action = "?mode=WebShowModule&job=add_fan_participant";
        }
        else {
            $action = "?mode=PublicLive&job=add_guest_participant";
        }
        $entertiner = new Entertainer($this->id);
        $member = new Member($entertiner->Member_id);

        $LastPublicShowDyte = $entertiner->getLastPublicShow();
        $meetingId = '';
        $viewCount = 0;
        if ($LastPublicShowDyte){
            $meetingId = $LastPublicShowDyte->id;
            $viewCount = $LastPublicShowDyte->view_count;
        }

        $avatarUrl = $LastPublicShowDyte->thumbnail;
        if (!$avatarUrl){
            $entertainerAvatar = DATAROOT . "/private/avatars/entertainerAvatar{$entertiner->Member_id}";
            foreach(['png','jpg', 'jpeg'] as $format) {
                if (file_exists($entertainerAvatar . '.' . $format)) {
                    $avatarUrl = $member->getEntertainerAvatarURL();
                }
            }
            if (!$avatarUrl){
                $avatarUrl = $member->getAvatarURL();
            }
        }

        $entertainer_link = "<div class='public-user'>
                                 <form method='post' action='$action'>
                                    <input type='hidden' name='meeting_id' value='$meetingId'>
                                    <input type='hidden' name='entertainer_id' value='$this->id'>
                                    <div class=\"avatar_link\"><br/>
                                        <img src='$avatarUrl' class='public_live_show' alt=''>
                                        <div class='name-view'><b>$this->name</b> 
                                        <span><img src='/site_img/icons/eye.png' height='16' width='16'>&nbsp;&nbsp;$viewCount</span>
                                        </div>
                                        
                                    </div>
                                 </form>
                             </div>";
        return $entertainer_link;
    }

    public function requiredFields()
    {
        $entertiner = new Entertainer($this->id);
        foreach(['png','jpg', 'jpeg'] as $format) {
            if (file_exists(DATAROOT . "/private/avatars/entertainerDisplayPhoto{$this->Member->id}". '.' . $format)) {
                $displayPhoto = DATAROOT . "/private/avatars/entertainerDisplayPhoto{$this->Member->id}". '.' . $format;
            }
        }
       // $displayPhoto = DATAROOT . "/private/avatars/entertainerDisplayPhoto{$this->Member->id}.png";
        $entertainerAvatar = DATAROOT . "/private/avatars/entertainerAvatar{$entertiner->Member_id}";
        $avatar = 0;
        if ($avatar == 0){
            foreach(['png','jpg', 'jpeg'] as $format) {
                if (file_exists($entertainerAvatar . '.' . $format)) {
                    $avatar = 1;
                }
            }
        }
        if ($avatar != 1){
            StaysailIO::setSession('avatarPhotoCheck', 'Avatar Photo required');
            header('Location: /?mode=EntertainerProfile&job=update_bio');
            exit;
        }
        if (!file_exists($displayPhoto)) {
            StaysailIO::setSession('displayPhotoCheck', 'Display Photo required');
            header('Location: /?mode=EntertainerProfile&job=update_bio');
            exit;
        }
        if (!$entertiner->birth_date || !$this->Member->address){
            StaysailIO::setSession('birthDateAddress', 'Birth Date and Mailing Address required');
            header('Location: /?mode=EntertainerProfile&job=update_bio');
            exit;
        }

        return true;
    }

    public function requiredEmailVerify()
    {
        if ($this->Member->email_verified == 0){
            header('Location: /?mode=EmailVerify&job=email_verify');
        }
    }
}