<?php

require '../private/views/ClubSummaryView.php';
require '../private/views/EventsView.php';
require '../private/views/PostView.php';
require '../private/views/SubscriptionListView.php';
require '../private/views/TwitterView.php';

class ClubProfile extends StaysailPublic
{
	protected $page, $settings, $categories;
    protected $framework;

    public $Member;
    public $Fan;
    public $Club;
    public $valid;

    public function __construct($dbc = '')
    {
    	$this->valid = false;

        $this->framework = StaysailIO::engage();

        $member_id = StaysailIO::session('Member.id');
        $this->Member = new Member($member_id);
        $this->Entertainer = $this->Member->getAccountOfType('Entertainer');
        $this->Club_Admin = $this->Member->getAccountOfType('Club_Admin');

        if ($this->Member and $this->Entertainer) {
        	$this->valid = true;
        }

        if ($this->Club_Admin) {
        	StaysailIO::setSession('Club.id', $this->Club_Admin->Club->id);
        } else if (StaysailIO::get('club_id')) {
        	StaysailIO::setSession('Club.id', StaysailIO::get('club_id'));
        	StaysailIO::setSession('Entertainer.id', null);
        }

        if (StaysailIO::session('Club.id')) {
        	$this->Club = new Club(StaysailIO::session('Club.id'));
        }
    }

    public function getHTML()
    {
//    	$this->Member->checkStanding(); // Make sure the member is paid, if it's a Fan

    	$job = StaysailIO::get('job');
    	$id = StaysailIO::get('id');

    	$map = Maps::getClubProfileMap();
    	$override_content = '';

    	switch ($job)
    	{
    		case 'reviews':
    			$override_content = $this->getReviews();
    			break;

    		case 'events':
    			if (StaysailIO::get('yy')) {
    				$override_content = $this->selectEventOnDate();
    			} else {
    				$override_content = $this->editEvent($id);
    			}
    			break;

    		case 'post_show':
    			$this->postShow($id);
    			break;
    	}

    	$header = new HeaderView();
    	$footer = new FooterView();
    	$action = new ActionsView($this->Member);
    	$banner = new BannerAdsView();
    	$twitter = new TwitterView();
		$subscription = new SubscriptionListView($this->Member);

		$ads = $this->Club_Admin ? $banner->getClubAds() : '';

		$containers = array(new StaysailContainer('H', 'header', $header->getHTML()),
							new StaysailContainer('F', 'footer', $footer->getHTML()),
							new StaysailContainer('A', 'action', $action->getHTML()),
							new StaysailContainer('B', 'banner', $twitter->getHTML() . $ads . $subscription->getClubList() . $banner->getHTML()),
							);

		$summary = new ClubSummaryView($this->Member);
		$events = new EventsView($this->Member);
		$posts = new PostView($this->Member);
		$right = $summary->getHTML() . $events->getHTML() . $summary->getPromoPhotosHTML() . $posts->getClubHTML($this->Club);

		if ($override_content) {
			$left = $override_content;
		} else {
			$left = $summary->getPhotoSummaryHTML() . $this->getQuickLinks() . $summary->getEntertainerList();
		}

		$containers[] = new StaysailContainer('L', 'posts', $left);
		$containers[] = new StaysailContainer('R', 'nearby', $right);
		$layout = new StaysailLayout($map, $containers);
		return $layout->getHTML();
    }

    private function getQuickLinks()
    {
    	$html = '';
    	if ($this->Member->getRole() == Member::ROLE_CLUB) {
			$quick_links = StaysailWriter::makeJobLink(Icon::show(Icon::EVENT) . ' Events', 'ClubProfile', 'events');
			$html = <<<__END__
				<div class="quick_links">{$quick_links}</div>
__END__;
		}
		return $html;
    }

    private function getReviews()
    {
    	$writer = new StaysailWriter();

    	$writer->h1("Reviews");
    	$reviews = $this->Club->getReviews();
    	$alt = 'alt_row';

        if ($this->Member->getRole() == Member::ROLE_FAN) {
	    	$button = "<a href=\"?mode=FanHome&job=review&type=Club&id={$this->Club->id}\" class=\"button\">Write a Reveiw</a>";
			if (!sizeof($reviews)) {
				$writer->p("No reviews have been written about this club.  Be the first to write one!");
			}
			$writer->p($button);
    	}

    	foreach ($reviews as $Review)
    	{
    		$alt = $alt ? '' : 'alt_row';
    		$writer->start("review {$alt}")
    			   ->draw($Review)
    			   ->end();
    	}
    	return $writer->getHTML();
    }

    private function editEvent($Show_Schedule_id)
    {
		$writer = new StaysailWriter();
		$Show_Schedule = new Show_Schedule($Show_Schedule_id);
		if ($Show_Schedule_id and !$Show_Schedule->belongsTo($this->Member)) {
			$writer->h1('Sorry...')
				   ->p("You do not have access to this show schedule");
			return $writer->getHTML();
		}

		$types = array('general' => 'General Event', 'sports' => 'Sports Event', 'holiday' => 'Holiday Event');
		$show = new StaysailForm();
		$show->setJobAction('ClubProfile', 'post_show', $Show_Schedule_id)
		      ->setSubmit($Show_Schedule_id ? 'Update Show' : 'Add Show')
		      ->setPostMethod()
		      ->setDefaults($Show_Schedule->info())
		      ->addHTML($this->datePicker('Show Start Time', 'start_date', $Show_Schedule->start_time))
		      ->addHTML($this->timePicker('&nbsp', 'start_time', $Show_Schedule->start_time))
		      ->addHTML($this->timePicker('Show End Time', 'end_time', $Show_Schedule->end_time))
		      ->addField(StaysailForm::Radio, 'Show Type', 'type', 'require-choice', $types)
		      ->addField(StaysailForm::Text, 'Description', 'description');

		$writer->h1($Show_Schedule_id ? 'Update a Show' : 'Add a Show')
			   ->p("Please enter your show information below.")
			   ->draw($show);
		return $writer->getHTML();

    }

    private function selectEventOnDate()
    {
    	$writer = new StaysailWriter();
    	$writer->h1("Select an Event");
    	if ($this->Member->getRole() == Member::ROLE_CLUB) {
	    	$writer->p("<a href=\"?mode=ClubProfile&job=events\" class=\"button\">Add a Show</a>");
    	}

    	$year = StaysailIO::get('yy');
    	$month = StaysailIO::get('mm');
    	$day = StaysailIO::get('dd');

    	$shows = $this->Club->getShowsOnDay($year, $month, $day);
    	$writer->start('upcoming');
		$writer->start('header');
		$writer->addHTML("<div class=\"header_image\">" . Icon::show(Icon::EVENT, Icon::SIZE_LARGE) . "</div>");
		$writer->addHTML("<div class=\"header_text\"><h2>Events</h2></div>");
		$writer->end('header');
		$writer->start('items');
		foreach ($shows as $Show_Schedule)
		{
			$link = "<strong><a href=\"?mode=ClubProfile&job=events&id={$Show_Schedule->id}\">{$Show_Schedule->getStartEnd()}</a></strong><br/>";
			$writer->p($link . $Show_Schedule->description);
		}
		$writer->end('items');
		$writer->end('upcoming');
		return $writer->getHTML();

    }

    private function postShow($show_id = null)
    {
    	if (!$show_id) {
    		$Show_Schedule = new Show_Schedule();
    		$Show_Schedule->Club = $this->Club;
    	} else {
    		$Show_Schedule = new Show_Schedule($show_id);
    		if (!$Show_Schedule->belongsTo($this->Member)) {
    			return false;
    		}
    	}
    	$fields = array('type', 'description');
    	$Show_Schedule->updateFrom($fields);

    	// Set the times
    	$start_date = StaysailIO::post('start_date');
    	$start_time = StaysailIO::post('start_time');
    	$end_time = StaysailIO::post('end_time');
    	$start_unix_time = strtotime("{$start_date} {$start_time}");
    	$end_unix_time = strtotime("{$start_date} {$end_time}");

    	if ($end_unix_time <= $start_unix_time) {
    		if ($start_unix_time - $end_unix_time <= 86400) {
    			// If the end time is less than a day before the start time, it will mean that the
    			// event probably goes past midnight on the previous day.  For example, if the user
    			// specifies 1/1/2012 8pm - 2am, the end date should probably be 1/2/2012.  In this
    			// case, I'll add one day (86400 seconds) to get the correct date.
    			$end_unix_time += 86400;
    		} else {
    			// Otherwise, just make the start and end time the same
    			$end_unix_time = $start_unix_time;
    		}
    	}
    	if ($start_unix_time and $end_unix_time) {
    		$Show_Schedule->start_time = date('Y-m-d H:i:s', $start_unix_time);
    		$Show_Schedule->end_time = date('Y-m-d H:i:s', $end_unix_time);
    	}

    	$Show_Schedule->save();
    }

    private function datePicker($label, $field, $default)
    {
    	$display_default = date('m/d/Y');
    	$default_time = strtotime($default);
    	if ($default_time) {
    		$display_default = date('m/d/Y', $default_time);
    	}
    	$html = <<<__END__
    		<div class="label">{$label}</div>
    		<div class="control">
	    	<script>addCalendarField('{$field}', '{$display_default}', 'date_entry required');</script>
	    	</div>
__END__;
    	return $html;
    }

    private function timePicker($label, $field, $default)
    {
    	$display_default = '7:00pm';
    	$default_time = strtotime($default);
    	if ($default_time) {
    		$display_default = date('g:i a', $default_time);
    	}
    	$html = <<<__END__
    		<div class="label">{$label}</div>
    		<div class="control"><input type="text" name="{$field}" value="{$display_default}" class="date_entry required" /></div>
__END__;
		return $html;
    }
}
