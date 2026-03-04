<?php
class LoginView
{
	private $Member;

	public function __construct(Member $Member = null)
	{
		$this->Member = $Member;
	}

	public function getHTML()
	{
		$identity = '';
        $videos = '';
		if (StaysailIO::get('e')) {
			$videos = "<input type=\"submit\" onclick=\"el('area').value='entertainer'\" value=\"Entertainer Video\" />";
		} elseif (StaysailIO::get('c')) {
			$videos = "<input type=\"submit\" onclick=\"el('area').value='club'\" value=\"Club Welcome Video\" />";
		}
//		else {
//			$videos = "<input type=\"submit\" onclick=\"el('area').value='fan'\" value=\"Fan Welcome Video\" />";
//		}

		/*if (StaysailIO::session('inviter_club_id') and !StaysailIO::session('inviter_entertainer_id')) {
			// If there's a club but no entertainer, go to the entertainer list for this club
			header("Location: /index.php?mode=Login&job=find");
			exit;
		}*/

		if (StaysailIO::session('inviter_entertainer_id')) {
			$Entertainer = new Entertainer(StaysailIO::session('inviter_entertainer_id'));
			$avatar_html = $Entertainer->Member->getAvatarHTML(Member::AVATAR_MEDIUM);
			$identity = "<div class=\"intro_text\"><p>Is this the girl you want to fan? If not, <a href=\"?mode=Login&job=join\">click here to find other entertainers.</a></p></div>";
		} else {
			$avatar_html = '';
		}

		$html = <<<__END__
		
		<div class="intro">{$avatar_html}</div>

		<div class="video_buttons"><form action="?mode=Login&job=video" method="post" />
			{$videos}
			<input type="hidden" name="area" value="entertainer" id="area" />
			</form>
		</div>

		{$identity}
		
<!--		<div class="intro_text">-->
<!--		<h1>Your exclusive membership website to stay in contact with your favorite entertainers and strip clubs between visits.</h1>-->
<!--		</div>-->
__END__;

		return $html;
	}

	public function getHeaderHTML()
	{
		$header = <<<__END__
			<div class="logo"><a href="?"><img src="/site_img/logo-new.png" alt="Local Strip Fan" /></a></div>
			<form method="post" id="login" action="?mode=Login&job=authorize">
			<input name="username" type="text" onfocus="if(this.value=='Email Address'){this.value='';}" autocomplete="off" value="Email Address" />
			<input name="dummypw" type="text" onfocus="this.style.display='none';document.getElementById('password').style.display='';document.getElementById('password').focus();" value="Password" />
			<input name="password" id="password" type="password" autocomplete="off" style="display:none" />
			<input type="submit" value="Log In" />
			</form>
			
__END__;
		return $header;
	}
}
