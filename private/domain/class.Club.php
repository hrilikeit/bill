<?php

final class Club extends StaysailEntity
{
    // Data model properties
    public $name = parent::Line;
    public $sort = parent::Int;

    public $address = parent::Line;
    public $address_2 = parent::Line;
    public $city = parent::Line;
    public $state = parent::Line;
    public $zip = parent::Line;
    public $phone = parent::Line;
    public $Geo_Location = parent::AssignOne;
    public $description = parent::Text;
    public $hours = parent::Text;
    public $fan_url = parent::Line;
    public $ein = parent::Line;
    public $is_deleted = parent::Boolean;
    public $account_number = parent::Line;
    public $embed_video = parent::Text;
    public $is_affiliate = parent::Boolean;

    // Metadata properties
    //protected $_sort          = 'name ASC';
    //protected $_name_template = '{name}';
	protected $_rating;
    
    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);		
    }
    
    public function delete_Job() 
    {
    	$this->is_deleted = 1;
    	$this->save();
    }
    	
    public function copy_Job() {return $this->copy();}

    public function getEntertainers($position = 'Entertainer')
    {
    	$sql = "SELECT DISTINCT `Entertainer`.`id`
    			FROM `Entertainer`
    			INNER JOIN `Entertainer_Club`
    				ON `Entertainer_Club`.`Club_id` = {$this->id}
						AND `Entertainer_Club`.`Entertainer_id` = `Entertainer`.`id`    				
    			WHERE `Entertainer`.`is_deleted` = 0
    				AND `Entertainer`.`position` = '{$position}'";
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
    
    public function getAdminMember()
    {
    	$sql = "SELECT `Member_id`
    			FROM `Club_Admin`
    			WHERE `Club_id` = {$this->id}
    			LIMIT 1";
    	$this->_framework->query($sql);
    	$row = $this->_framework->getNextRow();
    	if ($row['Member_id']) {
    		$Member = new Member($row['Member_id']);
    	} else {
    		$Member = null;
    	}
    	return $Member;
    }
    
    public static function getClubByFanURL($fan_url)
    {
    	$fan_url = preg_replace('/[^A-Za-z0-9]/', '', $fan_url);
    	$framework = StaysailIO::engage();
    	$filters = array(new Filter(Filter::Match, array('fan_url' => $fan_url, 'is_deleted' => 0)));
    	$clubs = $framework->getSubset('Club', $filters);
    	if (sizeof($clubs)) {
    		return array_pop($clubs);
    	}
    	return null;
    } 
    
    public function getReviews($sort = 'review_time')
    {
    	$filters = array(new Filter(Filter::Match, array('Club_id' => $this->id)),
    					 new Filter(Filter::Sort, "{$sort} DESC"),
    				    );
		$reviews = $this->_framework->getSubset('Review', $filters);    				 
		$rating_total = $rating_count = 0;
		foreach ($reviews as $Review)
		{
			if ($Review->admin_status == 'denied') {continue;}
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
		
    	return $reviews;
    }
    
    public function getRating()
    {
    	if (is_numeric($this->_rating)) {
    		return $this->_rating;
    		
    	}
    	$this->getReviews();
    	return $this->_rating;
    }
    
    public function getStarRatingHTML()
    {
    	$rating = $this->getRating();
    	return Icon::getStarRatingHTML($rating);
    	
    }    

    public function getClubPhotoHTML()
    {
    	if (file_exists(DATAROOT . "/private/avatars/club{$this->id}.png")) {
	    	$url = "/club_photo.php?a={$this->id}";
    		$img = "<img src=\"{$url}\" class=\"club_photo\" alt=\"\" border=\"0\" />";
    		return $img;
    	}
    }
    
    public function uploadClubPhoto()
    {
    	if (isset($_FILES['image'])) {
	    	$file = $_FILES['image'];
			move_uploaded_file($file["tmp_name"], "../private/avatars/club{$this->id}.png");
    	}

    	if (isset($_FILES['menu'])) {
			$file = $_FILES['menu'];
			move_uploaded_file($file["tmp_name"], "../private/avatars/club{$this->id}_menu.png");
    	}
		
    	if (isset($_FILES['flyer'])) {
			$file = $_FILES['flyer'];
			move_uploaded_file($file["tmp_name"], "../private/avatars/club{$this->id}_flyer.png");
    	}
    	
    	if (isset($_POST['delete_menu']) and file_exists("../private/avatars/club{$this->id}_menu.png")) {
    		unlink("../private/avatars/club{$this->id}_menu.png");
    	}
    	
    	if (isset($_POST['delete_flyer']) and file_exists("../private/avatars/club{$this->id}_flyer.png")) {
    		unlink("../private/avatars/club{$this->id}_flyer.png");
    	}
    	    	
    	return true;
    }
    
    public function getMapHTML($w = 300, $h = 100)
    {
    	$address = "{$this->address} {$this->address2}, {$this->city}, {$this->state} {$this->zip}";
    	$address = urlencode($address);
    	$href = "https://www.google.com/maps?q={$address}&t=m&z=16&iwloc=A";
    	$url = "http://maps.googleapis.com/maps/api/staticmap?center={$address}&zoom=13&size={$w}x{$h}&maptype=roadmap%20&markers=color:blue|label:*|{$address}&sensor=false";
    	$img = "<a href=\"{$href}\" target=\"_blank\"><img src=\"{$url}\" alt=\"\" border=\"0\" /></a>";
    	return $img;
    	
    }
    
    public function getAddressHTML()
    {
    	$address = "<strong>{$this->address}<br/>";
    	if ($this->address2) {
    		$address .= "{$this->address2}<br/>";
    	}
    	$address .= "{$this->city}, {$this->state} {$this->zip}</strong>";
    	if ($this->phone) {
    		$address .= "<br/>{$this->phone}";
    	}
    	return $address;
    }
    
    public function getMenuHTML()
    {
    	if (file_exists(DATAROOT . "/private/avatars/club{$this->id}_menu.png")) {
	    	$url = "club_photo.php?a={$this->id}&t=menu";
    		$img = "<a target=\"_blank\" href=\"/{$url}\"><img src=\"{$url}\" class=\"club_photo\" alt=\"\" border=\"0\" /></a>";
    		return $img;
    	}
    }
    
    public function getFlyerHTML()
    {
    	if (file_exists(DATAROOT . "/private/avatars/club{$this->id}_flyer.png")) {
	    	$url = "club_photo.php?a={$this->id}&t=flyer";
    		$img = "<a target=\"_blank\" href=\"/{$url}\"><img src=\"{$url}\" class=\"club_photo\" alt=\"\" border=\"0\" /></a>";
    		return $img;
    	}
    }
    
    public function getPromoButtons()
    {
    	$promo = '';

    	if (file_exists(DATAROOT . "/private/avatars/club{$this->id}_menu.png")) {
	    	$url = "club_photo.php?a={$this->id}&t=menu";
    		$promo .= "<a class=\"button green\" target=\"_blank\" href=\"/{$url}\">See Menu</a>";
    	}
    	
    	if (file_exists(DATAROOT . "/private/avatars/club{$this->id}_flyer.png")) {
	    	$url = "club_photo.php?a={$this->id}&t=flyer";
	    	if ($promo) {$promo .= '&nbsp;';}
    		$promo .= "<a class=\"button green\" target=\"_blank\" href=\"/{$url}\">See Current Flyer</a>";
    	}

    	return $promo;
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
    					new Filter(Filter::Match, array('Club_id' => $this->id)),
    					);
		$shows = $this->_framework->getSubset('Show_Schedule', $filters);
		return $shows;
    }

    /**
     * Return array of Post items
     * 
     * @return array<Post>
     */
    public function getPosts()
    {
    	$Club_Admin = $this->_framework->getSingle('Club_Admin', new Filter(Filter::Match, array('Club_id' => $this->id)));
    	if (!$Club_Admin or !$Club_Admin->Member) {
    		return array();
    	}
    	$member_id = $Club_Admin->Member->id;
    	
    	$filters = array(new Filter(Filter::Match, array('Member_id' => $member_id)),
    					 new Filter(Filter::IsTrue, 'active'),
    					 new Filter(Filter::IsNull, 'Post_id'),
    					 new Filter(Filter::Sort, 'post_time DESC'),
				    	);
		$posts = $this->_framework->getSubset('Post', $filters);
		return $posts;				    	
    }
    
    public function getEntertainerWithFanURL($fan_url)
    {
    	$fan_url = trim($fan_url);
    	$Entertainer = null;
    	StaysailIO::cleanse($fan_url, StaysailIO::SQL);
    	$sql = "SELECT DISTINCT `Entertainer`.`id`
    			FROM `Entertainer`
    			INNER JOIN `Entertainer_Club`
    				ON `Entertainer_Club`.`Club_id` = {$this->id}
						AND `Entertainer_Club`.`Entertainer_id` = `Entertainer`.`id`    				
    			WHERE `Entertainer`.`is_deleted` = 0
    				AND `Entertainer`.`is_active` = 1
    				AND `Entertainer`.`fan_url` = '{$fan_url}'";
    	$this->_framework->query($sql);
    	while ($row = $this->_framework->getNextRow())
    	{
    		$Entertainer = new Entertainer($row['id']);
    	}
    	return $Entertainer;
    }
    
    public function getEmbeddedVideoHTML()
    {
    	$url = $this->embed_video;
    	if (!preg_match('/^http/', $url)) {
    		if (preg_match('/src="([^"]*)"/', $url, $m)) {
    			$url = $m[1];
    			if (!preg_match('/^http:\/\//', $url)) {
    				$url = "http:{$url}";
    			}
    		}
    	}
    	if ($url) {
    		$html = "<div style=\"float:left;width:435px;height:245px;\">
    				<iframe width=\"435\" height=\"245\" src=\"{$url}\" frameborder=\"0\" allowfullscreen></iframe>&nbsp;</div>";
    		return $html;
    	} else {
    		return '';
    	}
    }
    
    public function getStateName()
    {
    	$abbrev = strtoupper($this->state);
    	$us_state_abbrevs_names = Club::getStateNames();
    	
		if (isset($us_state_abbrevs_names[$abbrev])) {
			return $us_state_abbrevs_names[$abbrev];
		}
		
		return $abbrev;
    }
    
    public static function getStateNames()
    {
		return array(
			'AL'=>'ALABAMA',
			'AK'=>'ALASKA',
			'AB'=>'ALBERTA',
			'AS'=>'AMERICAN SAMOA',
			'AZ'=>'ARIZONA',
			'AR'=>'ARKANSAS',
			'BC'=>'BRITISH COLUMBIA',
			'CA'=>'CALIFORNIA',
			'CO'=>'COLORADO',
			'CT'=>'CONNECTICUT',
			'DE'=>'DELAWARE',
			'DC'=>'DISTRICT OF COLUMBIA',
			'FM'=>'FEDERATED STATES OF MICRONESIA',
			'FL'=>'FLORIDA',
			'GA'=>'GEORGIA',
			'GU'=>'GUAM GU',
			'HI'=>'HAWAII',
			'ID'=>'IDAHO',
			'IL'=>'ILLINOIS',
			'IN'=>'INDIANA',
			'IA'=>'IOWA',
			'KS'=>'KANSAS',
			'KY'=>'KENTUCKY',
			'LA'=>'LOUISIANA',
			'ME'=>'MAINE',
			'MB'=>'MANITOBA',
			'MH'=>'MARSHALL ISLANDS',
			'MD'=>'MARYLAND',
			'MA'=>'MASSACHUSETTS',
			'MI'=>'MICHIGAN',
			'MN'=>'MINNESOTA',
			'MS'=>'MISSISSIPPI',
			'MO'=>'MISSOURI',
			'MT'=>'MONTANA',
			'NE'=>'NEBRASKA',
			'NV'=>'NEVADA',
			'NB'=>'NEW BRUNSWICK',
			'NH'=>'NEW HAMPSHIRE',
			'NJ'=>'NEW JERSEY',
			'NM'=>'NEW MEXICO',
			'NY'=>'NEW YORK',
			'NL'=>'NEWFOUNDLAND AND LABRADOR',
			'NC'=>'NORTH CAROLINA',
			'ND'=>'NORTH DAKOTA',
			'MP'=>'NORTHERN MARIANA ISLANDS',
			'NT'=>'NORTHWEST TERRITORIES',
			'NS'=>'NOVA SCOTIA',
			'NU'=>'NUNAVUT',
			'OH'=>'OHIO',
			'OK'=>'OKLAHOMA',
			'ON'=>'ONTARIO',
			'OR'=>'OREGON',
			'PW'=>'PALAU',
			'PA'=>'PENNSYLVANIA',
			'PE'=>'PRINCE EDWARD ISLAND',
			'PR'=>'PUERTO RICO',
			'QU'=>'QUEBEC',
			'RI'=>'RHODE ISLAND',
			'SA'=>'SASKATCHEWAN',
			'SC'=>'SOUTH CAROLINA',
			'SD'=>'SOUTH DAKOTA',
			'TN'=>'TENNESSEE',
			'TX'=>'TEXAS',
			'UT'=>'UTAH',
			'VT'=>'VERMONT',
			'VI'=>'VIRGIN ISLANDS',
			'VA'=>'VIRGINIA',
			'WA'=>'WASHINGTON',
			'WV'=>'WEST VIRGINIA',
			'WI'=>'WISCONSIN',
			'WY'=>'WYOMING',
			'YT'=>'YUKON',
		);
    }
}