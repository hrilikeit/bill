<?php
class BannerAdsView
{
	public $Member;
	
	public function __construct($Member = null)
	{
		$this->Member = $Member;
	}

	public function getHTML()
	{
		$html = '';
        $framework = StaysailIO::engage();
        $url = $framework->getSetting('eom_url');
		
//		$html .= "<a href=\"http://www.stripmingle.com\" target=\"_blank\">
//				 <img border=\"0\" src=\"/ads/Banner-1.jpg\" /></a><br/>";
//
//        $html .= "<a href=\"http://www.stripmingle.com\" target=\"_blank\">
//				 <img border=\"0\" src=\"/ads/Banner-2.jpg\" /></a><br/>";
//
//        $html .= "<a href=\"http://localstripfan.com\" target=\"_blank\">
//				 <img border=\"0\" src=\"/ads/Banner-3.jpg\" /></a><br/>";

        $html .= "<a href=\"https://localstripfan.com\" target=\"_blank\">
				 <img border=\"0\" src=\"/ads/stripmingle.png\" /></a><br/>";

//		$html .= "<a href='{$url}' target=\"_blank\">
//				 <img border=\"0\" src=\"/ads/eotmfan.jpg\" /></a>";

        $html .= "<a href=\"http://localcityscene.com\" target=\"_blank\">
				 <img border=\"0\" src=\"/ads/cabaretapps.png\" /></a>";
		return $html;
	}
	
	public function getClubAds()
	{
		$html = '';
		
		$html .= "<a href=\"http://cabaretapps.com\" target=\"_blank\">
				 <img border=\"0\" src=\"/ads/cabaretapps.png\" /></a>";
		
		return $html;
	}
}

