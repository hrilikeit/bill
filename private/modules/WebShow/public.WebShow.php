<?php

require '../private/tools/WebCam.php';
require '../private/views/WebShowView.php';
require '../private/views/LiveChatView.php';

class WebShowModule extends StaysailPublic
{
	protected $page, $settings, $categories;
    protected $framework;

    public $Member;
    public $Entertainer;
    
    public function __construct($dbc = '')
    {
    	$this->valid = false;
    	
        $this->framework = StaysailIO::engage();
        
        $member_id = StaysailIO::session('Member.id');
        $this->Member = new Member($member_id);

    	if (StaysailIO::session('Entertainer.id')) {
			$this->Entertainer = new Entertainer(StaysailIO::session('Entertainer.id'));
		} else {
			$this->Entertainer = $this->Member->getAccountOfType('Entertainer');
		}
    }

    public function getHTML()
    {
    	$job = StaysailIO::get('job');
    	$id = StaysailIO::get('id');
    	
    	$map = Maps::getWebShowMap();
    	
    	switch ($job)
    	{
    	}
    	
    	$header = new HeaderView();
    	$footer = new FooterView();
    	$action = new ActionsView($this->Member);
    	$banner = new BannerAdsView();
    	
		$containers = array(new StaysailContainer('H', 'header', $header->getHTML()),
							new StaysailContainer('F', 'footer', $footer->getHTML()),
							new StaysailContainer('A', 'action', $action->getHTML()),
							new StaysailContainer('B', 'banner', $banner->getHTML()),
							);

		$webshow = new WebShowView($this->Member);
		$chat = new LiveChatView($this->Member);
		
		$containers[] = new StaysailContainer('L', 'webshow', $webshow->getHTML());
		$containers[] = new StaysailContainer('R', 'chat', $chat->getHTML());
		$layout = new StaysailLayout($map, $containers);
		return $layout->getHTML();	
    }       
}