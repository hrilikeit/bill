<?php

require '../private/views/SearchResultsView.php';

class SearchResults extends StaysailPublic
{
	protected $page, $settings, $categories;
    protected $framework;
    
    private $Member;
    
    public function __construct($dbc = '')
    {
        $this->framework = StaysailIO::engage();
        $member_id = StaysailIO::session('Member.id');
        $this->Member = new Member($member_id);
    }

    public function getHTML()
    {
   		$map = Maps::getSearchResultsMap();
    	
    	$header = new HeaderView();
    	$footer = new FooterView();
    	$content = new SearchResultsView($this->Member);
    	$action = new ActionsView($this->Member);
    	$banner = new BannerAdsView();
    	
		$containers = array(new StaysailContainer('H', 'header', $header->getHTML()),
							new StaysailContainer('F', 'footer', $footer->getHTML()),
							new StaysailContainer('A', 'action', $action->getHTML()),
							new StaysailContainer('C', 'content', $content->getHTML()),
							new StaysailContainer('B', 'banner', $banner->getHTML()),
							);
		$layout = new StaysailLayout($map, $containers);
		return $layout->getHTML();	
	}
	
}