<?php
class ClubSummaryView
{
	private $Member;
	private $Club;
	private $Club_Admin;
	
	public function __construct(Member $Member)
	{
		$this->Member = $Member;
		if (StaysailIO::session('Club.id')) {
			$this->Club = new Club(StaysailIO::session('Club.id'));
		} else {
			$this->Club_Admin = $this->Member->getAccountOfType('Club_Admin');
			$this->Club = $this->Club_Admin->Club;
		}
	}
	
	public function getHTML()
	{
		if (!$this->Club) {return '';}
		
		$writer = new StaysailWriter();
		$writer->start('summary');
		$writer->h1($this->Club->name);
		
		$writer->p($this->Club->getAddressHTML());
		
		$writer->p($this->Club->getStarRatingHTML());
		
		if ($this->Club->fan_url) {
			$fan_url = $this->Club->fan_url;
			if (!preg_match('/http:\/\//', $fan_url)) {$fan_url = "http://{$fan_url}";}
			$writer->p("<a href=\"{$fan_url}\" target=\"_blank\">{$fan_url}</a>");
		}
		
		$writer->p($this->Club->description);
		$writer->end('summary');
		
		$promo_buttons = $this->Club->getPromoButtons();
		if ($promo_buttons) {
			$writer->p($promo_buttons);
		}
		$writer->p($this->Club->getMapHTML(435, 200));
		$writer->addHTML($this->Club->getEmbeddedVideoHTML());
		
		$writer->p(StaysailWriter::textToHTML($this->Club->hours));
		
		return $writer->getHTML();
		
	}
	
	public function getPhotoSummaryHTML()
	{
		return $this->Club->getClubPhotoHTML();
	}
	
	public function getEntertainerList()
	{
		$writer = new StaysailWriter('upcoming');
		
    	$positions = array('Entertainers' => 'Entertainer', 'Managers' => 'Manager', 
    					   'Bartenders' => 'Bartender', 'Waitstaff' => 'Waitstaff', );
    	foreach ($positions as $label => $position)
    	{
	    	$entertainers = $this->Club->getEntertainers($position);
	    	if (sizeof($entertainers)) {
				$writer->addHTML("<div class=\"header\"><div class=\"header_image\">" . Icon::show(Icon::EVENT, Icon::SIZE_LARGE) . "</div>");
				$writer->addHTML("<div class=\"header_text\"><h2>{$label} at {$this->Club->name}</h2></div>");
				$writer->addHTML("</div><div class=\"items\">");
	    		$alt = 'alt_row';
				$table = new StaysailTable('club');
				if ($this->Member->getRole() == Member::ROLE_FAN) {
					$Fan = $this->Member->getAccountOfType('Fan');
				} else {$Fan = null;}
				foreach ($entertainers as $Entertainer)
				{
					$alt = $alt ? '' : 'alt_row';
			
					$avatar = $Entertainer->Member->getAvatarHTML(Member::AVATAR_LITTLE);
					$subscribe = '';
					if ($Fan) {
						$entertainer_name = str_replace(' ', '&nbsp;', $Entertainer->name);
						if ($Fan->isSubscribedTo($Entertainer)) {
							$subscribe .= "<a href=\"?mode=EntertainerProfile&entertainer_id={$Entertainer->id}\" class=\"button\">Visit&nbsp;{$entertainer_name}</a>";
						} else {
							$subscribe .= "<a href=\"?mode=Purchase&job=purchase&type=Entertainer&id={$Entertainer->id}\" class=\"button\">Fan&nbsp;{$entertainer_name}</a>";
						}
					}
					$reviews = "<a href=\"?mode=EntertainerProfile&entertainer_id={$Entertainer->id}&job=reviews\" class=\"button\">Reviews</a>";
					$table->addRow(array($avatar, $subscribe . '<br/><br/><br/>' . $reviews));
			
					//$writer->addHTML("<div class=\"item {$alt}\">{$avatar}&nbsp;{$Entertainer->name}{$subscribe} {$reviews}</div>");
				}
				$writer->draw($table);
				$writer->addHTML("&nbsp;</div>");
	    	}
    	}
				
		return $writer->getHTML();
	}
	
	public function getPromoPhotosHTML()
	{
		$html = "<div style=\"clear:both;float:left\">" . $this->Club->getMenuHTML() . "</div>\n";
		$html .= "<div style=\"margin-top:10px;clear:both;float:left\">" . $this->Club->getFlyerHTML() . "</div>\n";
		return $html;
	}
}