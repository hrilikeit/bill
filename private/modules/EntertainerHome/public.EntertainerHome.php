<?php

require '../private/views/PostView.php';

class EntertainerHome extends StaysailPublic
{
	protected $page, $settings, $categories;
    protected $framework;

    public $Member;
    public $Fan;
    public $Entertainer;
    public $valid;
    
    public function __construct($dbc = '')
    {
    	$this->valid = false;
    	
        $this->framework = StaysailIO::engage();
        
        $member_id = StaysailIO::session('Member.id');
        $this->Member = new Member($member_id);
        $this->Entertainer = $this->Member->getAccountOfType('Entertainer');

        if ($this->Member and $this->Entertainer) {
        	$this->valid = true;
        }
    }

    public function getHTML()
    {
    	header("Location:?mode=EntertainerProfile");
    	exit;
    	
    	$job = StaysailIO::get('job');
    	$id = StaysailIO::get('id');
    	
    	$map = Maps::getEntertainerHomeMap();
    	
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

		$posts = new PostView($this->Member);
		$containers[] = new StaysailContainer('L', 'posts', $posts->getHTML());
		$containers[] = new StaysailContainer('R', 'nearby', 'Nearby Things');
		$layout = new StaysailLayout($map, $containers);
		return $layout->getHTML();	
    }
}