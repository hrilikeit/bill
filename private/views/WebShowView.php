<?php
class WebShowView
{
	private $Member;
	private $WebShow;
	
	public function __construct(Member $Member, WebShow $WebShow)
	{
		$this->Member = $Member;
		$this->WebShow = $WebShow;
	}
	
	public function getHTML()
	{
		$html = '';
		$WebCam = $this->WebShow->getWebCam();
		$WebCam->setAuth($this->WebShow->username, $this->WebShow->password);
		
		if ($this->Member->getRole() == Member::ROLE_ENTERTAINER) {
			$WebCam->setSize(435, 360);
			if ($this->WebShow->Fan_id) {
				$title = "Private Show for {$this->WebShow->Fan->name}";
			} else {
				$title = "Group Show";
			}
			$html .= "<h1>{$title}</h1>\n";
			$html .= $WebCam->getPublisher();
			$html .= "<p>Start the show by clicking the Connect button, and then the Publish button</p>";
			$html .= '<div id="watchers"></div>';
		}
		
		if ($this->Member->getRole() == Member::ROLE_FAN) {
			$WebCam->setSize(520, 453);
			$html = "<div id=\"timer\"></div>";
			$html .= "<div id=\"viewer\">";
			$html .= $WebCam->getViewer();
			$html .= "</div>";
		}
		
		return $html;
	}
	
	public function getFanCamHTML()
	{
		$html = '<div class="fancam">';
		$WebCam = $this->WebShow->getFanWebCam();
		$WebCam->setAuth($this->WebShow->fancam_username, $this->WebShow->fancam_password);
		
		if ($this->Member->getRole() == Member::ROLE_FAN) {
			$WebCam->setSize(435, 360);
			$html .= $WebCam->getPublisher(true);
			$html .= "<p>Start the show by clicking the Connect button, and then the Publish button</p>";
		}
		
		if ($this->Member->getRole() == Member::ROLE_ENTERTAINER) {
			$WebCam->setSize(150, 130);
			$html .= $WebCam->getViewer(true);
		}
		
		$html .= '</div>';
		
		return $html;
	}
}