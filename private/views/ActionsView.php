<?php

class ActionsView
{
	private $Member;
	private $Entertainer;
	private $module;

	public function __construct(Member $Member)
	{
		$this->Member = $Member;
		if (StaysailIO::session('Entertainer.id')) {
			$this->Entertainer = new Entertainer(StaysailIO::session('Entertainer.id'));
		} else {
			$this->Entertainer = $this->Member->getAccountOfType('Entertainer');
		}
	}

	public function setModule($module)
	{
		$this->module = $module;
	}

	public function getHTML()
	{
		$writer = new StaysailWriter();

		if ($this->Member->getRole() == Member::ROLE_FAN) {
			$writer->h1("How Do I...", 'red');
			$writer->addHTML($this->getTrainingLink());
		}

		if ($this->Member->getRole() == Member::ROLE_CLUB) {
//			$Club_Admin = $this->Member->getAccountOfType('Club_Admin');
//			$Club = $Club_Admin->Club;
			$writer->h1('Club Admin');
			$writer->addHTML($this->getLinkHTML(Icon::PHOTOS, 'Club Photos', 'Club_AdminHome', null, 'photo'));
//			$writer->addHTML($this->getLinkHTML(Icon::FOLLOWERS, 'Entertainers', 'Club_AdminHome', sizeof($Club->getEntertainers()), 'entertainers'));
//			$writer->addHTML($this->getLinkHTML(Icon::REVIEWS, 'Reviews', 'ClubProfile', sizeof($Club->getReviews()), 'reviews'));
			$writer->addHTML($this->getLinkHTML(Icon::ADMIN, 'Profile', 'Club_AdminHome', null, 'profile'));
			$writer->addHTML($this->getLinkHTML(Icon::CLUBS, 'Club Page', 'ClubProfile'));
			$writer->addHTML($this->getReportLink());
		} else {
		    if (!class_exists('WebCamDyte')) {
                require_once '../private/tools/WebCamDyte.php';
            }
            $show = '';
            $hasActiveStream = false;
            $WebShow = new WebCamDyte();
		    if($this->Entertainer) {
                $WebShowDyte = $this->Entertainer->getWebShowDyte();
                $hasActiveStream = $WebShowDyte ? $WebShow->checkActiveLiveStream($WebShowDyte) : false;
            }
           	if ($this->Entertainer) {
                $framework = StaysailIO::engage();
                $webShowDyteData = $framework->getRowByConditions('WebShowDyte',
                    [
                        'Entertainer_id' => $this->Entertainer->id,
                    ]

                );
				$writer->addHTML($this->Entertainer->Member->getAvatarHTML(Member::AVATAR_SIDEBAR));
				if ($this->Member->getRole() == Member::ROLE_FAN and $this->Member->getAccountOfType('Fan')) {
					if ($this->Entertainer->isAvailable($this->Member->getAccountOfType('Fan'))) {
						$group = "<a href=\"#\" onclick=\"ringDoorbell();\">Subscribers Show</a>";
						$private = "<a href=\"#\" onclick=\"ringDoorbell(1);\">Private Show</a>";
//						if($hasActiveStream) {
                            if ($webShowDyteData){
                                $show = '<a href="?mode=WebShowModule&job=purchase_show">Join Now</a>';
                            }
//                        }
						$writer->addHTML("<p id=\"doorbell\">Ring Doorbell:<br/>{$group} {$private} {$show}</p>");
					} elseif ($WebShow = $this->Entertainer->privateShowInProgress($this->Member->getAccountOfType('Fan'))) {
						if (StaysailIO::get('job') != 'join_show' && $hasActiveStream) {
							$join = StaysailWriter::makeJobLink('Join Now', 'Purchase', 'purchase&type=WebShow', $WebShow->id, 'button');
							$writer->addHTML("<p id=\"doorbell\">A Private Show has started.<br/><br/>{$join}</p>");
						}
					} else {
						// The Entertainer is online and available for a show
						$WebShow = $this->Entertainer->showInProgress();
						if ($WebShow and !$WebShow->isPrivate() and StaysailIO::get('job') != 'join_show') {
							// There's already a show in progress that the Fan may join
							$writer->addHTML("<p id=\"doorbell\"><a class=\"button\" href=\"?mode=WebShowModule&job=purchase_show\">Join Subscribers Show</a></p>");
							//$writer->addHTML($this->getLinkHTML(Icon::VIDEO_CHATS, 'Join Show', 'WebShowModule', null, 'purchase_show'));
						}
					}
				}
			}

			//$writer->addHTML($this->getEOMLink());

			$writer->h1('Favorites');
			$writer->addHTML($this->getProfileLink());
			$writer->addHTML($this->getMessagesLink());
//			$writer->addHTML($this->getReviewsLink());
			$writer->addHTML($this->getPhotosLink());
			$writer->addHTML($this->getVideosLink());
			$writer->addHTML($this->getVideoStoreLink());
			$writer->addHTML($this->getEventsLink());
			$writer->addHTML($this->getPurchasedPhotosLink());
			$writer->addHTML($this->getReportLink());

			$writer->h1('Connections');
			$writer->addHTML($this->getFollowersLink());
			$writer->addHTML($this->getCoworkersLink());
			$writer->addHTML($this->getClubsLink());
			$writer->addHTML($this->getVideoLink());
		//	$writer->addHTML($this->getForumLink());
			$writer->addHTML($this->getSubscribedList());
			if (StaysailIO::get('mode') == 'ClubProfile') {
                $writer->addHTML($this->onlineList());
            }

			if ($this->Member->getRole() == Member::ROLE_ENTERTAINER) {
				$writer->h1("How Do I...", 'red');
				$writer->addHTML($this->getTrainingLink());
			}
		}

		return $writer->getHTML();
	}

    public function getMessagesLink()
	{
		return $this->getLinkHTML(Icon::MESSAGES, 'Messages', 'Message', sizeof($this->Member->getUnreadMessages()));
	}

    public function getVideosLink()
	{
		if ($this->Member->getRole() == Member::ROLE_ENTERTAINER) {
			return $this->getLinkHTML(Icon::VIDEOS, 'Videos', 'EntertainerGallery', sizeof($this->Member->getVideos()), 'videos');
		} else {
            if ($this->Entertainer) {
                return $this->getLinkHTML(Icon::VIDEOS, 'Videos', 'EntertainerGallery', sizeof($this->Entertainer->getVideos()), 'videos');
            }
            else{
                return "";
            }
		}
	}

    public function getVideoStoreLink()
	{
		if ($this->Member->getRole() == Member::ROLE_ENTERTAINER) {
			return $this->getLinkHTML(Icon::VIDEO_STORE, 'Video Store', 'EntertainerGallery', sizeof($this->Member->getVideoStore()), 'videos_store');
		} else {
            if ($this->Entertainer) {
                return $this->getLinkHTML(Icon::VIDEO_STORE, 'Video Store', 'EntertainerGallery', sizeof($this->Entertainer->getVideoStore()), 'videos_store');
            }
            else{
                return "";
            }
		}
	}

	private function getReviewsLink()
	{
		$link = '';
		if (StaysailIO::session('Entertainer.id') or $this->Member->getRole() == Member::ROLE_ENTERTAINER) {
			$link = $this->getLinkHTML(Icon::REVIEWS, 'Reviews', 'EntertainerProfile', sizeof($this->Entertainer->getReviews()), 'reviews');
		} else if (StaysailIO::session('Club.id')) {
			$Club = new Club(StaysailIO::session('Club.id'));
			$link = $this->getLinkHTML(Icon::REVIEWS, 'Reviews', 'ClubProfile', sizeof($Club->getReviews()), 'reviews');
		}

		return $link;
	}

    public function getPhotosLink()
	{
		if ($this->Member->getRole() == Member::ROLE_ENTERTAINER) {
            return  $this->getLinkHTML(Icon::PHOTOS, 'Photos', 'EntertainerGallery', sizeof($this->Member->getPhotos()));
		} else {
           if ($this->Entertainer) {
               return $this->getLinkHTML(Icon::PHOTOS, 'Photos', 'EntertainerGallery', sizeof($this->Entertainer->getPhotos()));
           }
           else{
               return "";
           }
		}
	}

    public function getPurchasedPhotosLink()
	{
		if ($this->Member->getRole() == Member::ROLE_FAN) {
			return $this->getLinkHTML(Icon::PHOTOS, 'Vault', 'FanHome', null, 'photos');
		}
	}

    public function getEventsLink()
	{

	}

    public function getFollowersLink()
	{
		$link = '';
		if ($this->Member->getRole() == Member::ROLE_ENTERTAINER && $this->Entertainer) {
			$link = $this->getLinkHTML(Icon::FOLLOWERS, 'Fans', 'EntertainerProfile', sizeof($this->Entertainer->getSubscribers()), 'fans');

		}
		/*
		if ($this->Member->getRole() == Member::ROLE_FAN) {
			$Fan = $this->Member->getAccountOfType('Fan');
			$link = $this->getLinkHTML(Icon::FOLLOWERS, 'Fans Of', 'FanProfile', sizeof($Fan->getActiveSubscriptions()));
		}
		*/
		return $link;
	}

    public function getCoworkersLink()
	{

	}

    public function getClubsLink()
	{

	}

    public function getVideoLink()
	{
		$html = '';
		if ($this->Entertainer and $this->Entertainer->hasVideoAccess()) {
			if ($this->Member->getRole() == Member::ROLE_ENTERTAINER) {
                $html .= $this->getLinkHTML(Icon::VIDEO_CHATS, 'Start Show', 'WebShowModule', null, 'prestart_show');

                // For the Entertainer, the video link starts the show
//				if ($this->Entertainer->showInProgress()) {
//					// If there's already a show in progress, allow the Entertainer to resume or end it
//					$html .= $this->getLinkHTML(Icon::VIDEO_CHATS, 'Resume Show', 'WebShowModule', null, 'resume_show');
//					$html .= $this->getLinkHTML(ICON::END_VIDEO, 'End Show', 'WebShowModule', null, 'end_show');
//				} else {
//					$html .= $this->getLinkHTML(Icon::VIDEO_CHATS, 'Start Show', 'WebShowModule', null, 'prestart_show');
//				}
			}
		}

		return $html;
	}

	private function getForumLink()
	{
		if ($this->Member->getRole() == Member::ROLE_ENTERTAINER) {
			$href = "/phpBB3";
			$href = "https://www.stripperweb.com/";
			$icon = Icon::show(Icon::COWORKERS, Icon::SIZE_SMALL, 'Entertainer Forum', $href);
			$html = "<div class=\"action\"><div class=\"action_icon\">{$icon}</div>";
			$html .= "<div class=\"action_text\"><a target=\"_blank\" href=\"{$href}\">Entertainer Forum</a></div>";
			$html .= "</div>";
			return $html;
		}
	}

    public function getReportLink()
	{
		$html = '';
		if ($this->Member->getRole() == Member::ROLE_ENTERTAINER) {
			$html .= $this->getLinkHTML(Icon::ACCOUNT, 'Reports', 'ReportModule');
		}
        if ($this->Member->getRole() == Member::ROLE_FAN) {
            $html .= $this->getLinkHTML(Icon::ACCOUNT, 'Reports', 'ReportModule');
        }
		if ($this->Member->getRole() == Member::ROLE_CLUB) {
			$html .= $this->getLinkHTML(Icon::ACCOUNT, 'Reports', 'ReportModule');
		}
		return $html;
	}

    public function getTrainingLink()
	{
		$link = '';
		if ($this->Member->getRole() == Member::ROLE_ENTERTAINER) {
			$link = <<<__END__
                <select id="training1" onchange="openTraining(this.value)">
                    <option value="">Select a topic...</option>
                    <option value="wnuOaKPTLag">Post type and subscription how to</option>
                </select><br/>
__END__;
		}

		if ($this->Member->getRole() == Member::ROLE_FAN) {
			$link = <<<__END__
				<select id="training1" onchange="openTraining(this.value)">
					<option value="">Select a topic...</option>
					<option value="OG_Yxvu0PqE">Why do I need a Credit Card?</option>
					<option value="vNs4wqpr8Eo">Start a Video Chat?</option>
				</select><br/>
__END__;
		}

		return $link;
	}

	private function getLinkHTML($icon, $text, $mode, $count = null, $job ='')
	{
		$href = "?mode={$mode}&job={$job}";
		$icon = Icon::show($icon, Icon::SIZE_SMALL, $text, $href);
		$html = "<div class=\"action\"><div class=\"action_icon\">{$icon}</div>";
		$html .= "<div class=\"action_text\"><a href=\"{$href}\">{$text}</a></div>";
		if ($count !== null) {$html .= "<div id=\"dyn_{$mode}{$job}\" class=\"action_count\">{$count}</div>";}
		$html .= "</div>";
		return $html;
	}

    public function getSubscribedList()
	{
		if ($this->Member->getRole() != Member::ROLE_FAN) {return;}
		$Fan = $this->Member->getAccountOfType('Fan');
		if (!$Fan) {return '';}
		$writer = new StaysailWriter();
		$subscriptions = $Fan->getActiveSubscriptions();
		foreach ($subscriptions as $Fan_Subscription)
		{
			$Entertainer = $Fan_Subscription->Entertainer;
            $online = $Entertainer->isOnline() ? ' - <strong style="color: #6d6;">Online</strong>' : '';
			$avatar = $Entertainer->Member->getAvatarHTML(Member::AVATAR_TINY);
			$link = StaysailWriter::makeJobLink($Entertainer->name, 'EntertainerProfile', "&entertainer_id={$Entertainer->id}");
			$writer->addHTML("<div id=\"online_{$Entertainer->id}\" class=\"fan_select_link\">{$avatar}&nbsp;{$link}<span id=\"onlinetxt_{$Entertainer->id}\">{$online}</span></div>");
		}
		return $writer->getHTML();
	}

	private function onlineList()
	{
		if (StaysailIO::session('Club.id')) {
			$Club = new Club(StaysailIO::session('Club.id'));
			$entertainers = $Club->getEntertainers();
			if (!sizeof($entertainers)) {return '';}
			$writer = new StaysailWriter();
			$writer->h1('Online Now');
			$online = false;
			foreach ($entertainers as $Entertainer)
			{
				if ($Entertainer->private or !$Entertainer->isOnline()) {continue;}
				$online = true;
				$avatar = $Entertainer->Member->getAvatarHTML(Member::AVATAR_TINY);
				$link = StaysailWriter::makeJobLink($Entertainer->name, 'EntertainerProfile', "&entertainer_id={$Entertainer->id}");
				$writer->addHTML("<div class=\"fan_select_link\">{$avatar}&nbsp;{$link}</div>");
			}
			if (!$online) {return '';}
			return $writer->getHTML();
		}
	}
	
	private function getEOMLink()
	{
		$framework = StaysailIO::engage();
		$url = $framework->getSetting('eom_url');
		return "<a href=\"{$url}\" target=\"_blank\">Vote for Entertainer of the Month!</a>";
	}

    public function getProfileLink()
	{
		if ($this->Member->getRole() == Member::ROLE_ENTERTAINER) {
			return $this->getLinkHTML(Icon::COWORKERS, 'Edit Profile', 'EntertainerProfile', null, 'update_bio');
		}

		if ($this->Member->getRole() == Member::ROLE_FAN) {
			return $this->getLinkHTML(Icon::COWORKERS, 'Edit Profile', 'FanProfile', null, 'update_bio');
		}
	}
}