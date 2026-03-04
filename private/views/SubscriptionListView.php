<?php

class SubscriptionListView
{
	private $Member;
	private $Fan;
	
	public function __construct(Member $Member)
	{
		$this->Member = $Member;
		if (StaysailIO::session('Member.id') == $this->Member->id) {
			$this->Fan = $this->Member->getAccountOfType('Fan');
		}
	}
	
	public function getHTML()
	{


		$writer = new StaysailWriter();

//		$writer->h1($this->Member->name);

		$subscriptions = $this->Fan->getActiveSubscriptions();
		$entertainers = array();
		if (sizeof($subscriptions)) {
			$writer->h2("Favorite Entertainers");
            $writer->addHTML('<div class="avatar_link_div">');
			foreach ($subscriptions as $Fan_Subscription)
			{
				if (isset($entertainers[$Fan_Subscription->Entertainer_id])) {continue;}
				$writer->addHTML($Fan_Subscription->getAvatarLink());
				$entertainers[$Fan_Subscription->Entertainer_id] = true;
			}
            $writer->addHTML('</div>');
		}else{
            $writer = new StaysailWriter('email_verify');
            $writer->addHTML('<h1>You have no subs yet!</h1>');
            $writer->addHTML('<form method="POST" action="?mode=FanHome&job=all_models"><button type="submit">View creators here.</button></form>');
            return $writer->getHTML();
        }
		return $writer->getHTML();		
	}
	
	public function getEntertainerList()
	{
		$writer = new StaysailWriter('upcoming');
		$writer->addHTML("<div class=\"header\"><div class=\"header_image\">" . Icon::show(Icon::EVENT, Icon::SIZE_LARGE) . "</div>");
		$writer->addHTML("<div class=\"header_text\"><h2>Entertainers You Have Fanned</h2></div>");
		$writer->addHTML("</div><div class=\"items\">");
		
		$subscriptions = $this->Fan->getActiveSubscriptions();
		$alt = 'alt_row';
		foreach ($subscriptions as $Fan_Subscription)
		{
			$alt = $alt ? '' : 'alt_row';
			
			$Entertainer = $Fan_Subscription->Entertainer;
			$avatar = $Entertainer->Member->getAvatarHTML(Member::AVATAR_TINY);
			
			$writer->addHTML("<div class=\"item {$alt}\">{$avatar}&nbsp;{$Entertainer->name}</div>");
		}
		$writer->addHTML("&nbsp;</div>");
		
		return $writer->getHTML();
	}
	
	public function getClubList()
	{
		if (!$this->Fan) {return '';}
		
		$writer = new StaysailWriter();
		
		$writer->h1('Clubs Following');
		$subscriptions = $this->Fan->getSubscribedClubs();
		foreach ($subscriptions as $Club)
		{
			if (!$Club->name) {continue;}
			$url = "?mode=ClubProfile&club_id={$Club->id}";
			$writer->addHTML("<div class=\"fan_select_link\"><a href=\"{$url}\">{$Club->name}</a></div>");
		}
		
		$writer->h1('Find Clubs');
		$filters = array(new Filter(Filter::Sort, 'name'), 
			new Filter(Filter::Match, array('is_deleted' => 0))
		);
		$_framework = StaysailIO::engage();
		$clubs = $_framework->getSubset('Club', $filters);
		$state_lists = $last_state = $dropdown = '';
		$selected_state = StaysailIO::session('selected_state');
		$club_list_by_state = array();
		
		foreach ($clubs as $Club)
		{
			$abbrev = strtoupper($Club->state);
			if (!isset($club_list_by_state[$abbrev])) {
				$club_list_by_state[$abbrev] = array();
			}
			$club_list_by_state[$abbrev][] = StaysailWriter::makeJobLink($Club->name, 'ClubProfile', "&club_id={$Club->id}");
		}

		$state_selector = "<select style=\"width:170px\" id=\"state\"><option>-- Choose State --</option>";
		$list_html = '';
		foreach (Club::getStateNames() as $abbrev => $name)
		{
			$state_selector .= "<option value=\"{$abbrev}\">{$name}</option>\n";
			$list_html .= "<div class=\"fan_select_link\" id=\"StateSel_{$abbrev}\" style=\"float:left;width:175px;display:none;\">\n";			
			if (isset($club_list_by_state[$abbrev])) {
				$list_html .= "<ul>";
				foreach ($club_list_by_state[$abbrev] as $club_link) 
				{
					$list_html .= "<li>{$club_link}</li>\n";
				}
				$list_html .= "</ul>";
			} else {
				$list_html .= "<p>More clubs coming soon!</p>";
			}
			
			$list_html .= "</div>\n\n";
		}
		
		$state_selector .= "</select><div style=\"float:left;margin:10px;\"><a href=\"#\" class=\"button\" onclick=\"unhideStateSelector(el('state').value)\">Find Clubs</a></div>";
		$html = "<form>{$state_selector}</form>{$list_html}";
		$writer->addHTML($html);
		
		return $writer->getHTML();		
	}
	
	public function getRecentPosts()
	{
		if (!$this->Fan) {return '';}
		
		$writer = new StaysailWriter();
		$subscriptions = $this->Fan->getActiveSubscriptions();
		$recent_posts = array();
		foreach ($subscriptions as $Fan_Subscription)
		{
			$Entertainer = $Fan_Subscription->Entertainer;
			$Post = $Entertainer->getLastPost();
			if ($Post) {
				$post_time = strtotime($Post->post_time);
				$recent_posts[$post_time] = $Post;
			}
		}
		if (sizeof($recent_posts)) {
			foreach (array_reverse($recent_posts) as $Post)
			{
				$writer->draw($Post);
			}
		}
		return $writer->getHTML();
	}
}