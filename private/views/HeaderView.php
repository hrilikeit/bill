<?php

class HeaderView
{
	public function __construct()
	{

	}

	public function getHTML()
	{
        $menu = '';
		if (StaysailIO::session('Member.id')) {
			$Member = new Member(StaysailIO::session('Member.id'));
            $ActionsView = new ActionsView($Member);
            $writer = new StaysailWriter();
            $writer->h1('Favorites');
            $writer->addHTML($ActionsView->getProfileLink());
            $writer->addHTML($ActionsView->getMessagesLink());
            $writer->addHTML($ActionsView->getPhotosLink());
            $writer->addHTML($ActionsView->getVideosLink());
            $writer->addHTML($ActionsView->getVideoStoreLink());
            $writer->addHTML($ActionsView->getEventsLink());
            $writer->addHTML($ActionsView->getPurchasedPhotosLink());
            $writer->addHTML($ActionsView->getReportLink());
            $writer->h1('Connections');
            $writer->addHTML($ActionsView->getFollowersLink());
            $writer->addHTML($ActionsView->getCoworkersLink());
            $writer->addHTML($ActionsView->getClubsLink());
            $writer->addHTML($ActionsView->getVideoLink());
            $writer->addHTML($ActionsView->getSubscribedList());
//            if ($Member->getRole() == Member::ROLE_ENTERTAINER) {
//                $writer->h1("How Do I...", 'red');
//                $writer->addHTML($ActionsView->getTrainingLink());
//            }
            $menu = $writer->getHTML();
			$type = $Member->getAccountType();
			$name = $Member->name;
			$avatar = $Member->getAvatarHTML(Member::AVATAR_TINY);
            $creators = '';
            $publicLive = '';
            $videoStore = '';
            if ($Member->getRole() != Member::ROLE_ENTERTAINER){
                $creators = '| <a href=\'?mode=FanHome&job=all_models\'>Creators</a>';
//                $publicLive = '| <a href=\'?mode=FanHome&job=public_live\'>Public Live</a>';
                $videoStore = '| <a href=\'?mode=FanHome&job=video_store\'>Video Store</a>';
            }
//            $signout = " | <a href=\"?mode={$type}Home\">Home</a> $creators $publicLive $videoStore | <a href=\"?mode=Login&job=signout\">Sign Out</a>";
            $signout = " | <a href=\"?mode={$type}Home\">Home</a> $creators $videoStore | <a href=\"?mode=Login&job=signout\">Sign Out</a>";
			$user_info = "{$avatar}&nbsp;{$name}&nbsp;&nbsp;{$signout}";
		} else {
			$user_info = '';
		}
		$search = '';
		$header = <<<__END__
			<div class="logo inner-logo">
			    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b2/Hamburger_icon.svg/2048px-Hamburger_icon.svg.png" id="btn_mobile_left_menu" alt="Menu">
			    <div class="mobile_mobile_info mobile_mobile_info_hidden" id="mobile_left_menu">
                    <p>{$menu}</p>
                </div>
			    <a href="?">
			        <img src="/site_img/FullLogo.png" alt="Local Strip Fan" />
                </a>
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b2/Hamburger_icon.svg/2048px-Hamburger_icon.svg.png" id="btn_mobile_right_menu" alt="Menu">
                <div class="mobile_userinfo mobile_userinfo_hidden" id="mobile_right_menu">
                {$user_info}
                </div>
            </div>
			{$search}
			<div class="userinfo">
			{$user_info}
			</div>
			
__END__;
		return $header;
	}
}
