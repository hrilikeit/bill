<?php

require '../private/views/ReviewsView.php';

class Reviews extends StaysailPublic
{
	protected $page, $settings, $categories;
    protected $framework;

    public $Member;
    public $Fan;
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
    	$job = StaysailIO::get('job');
    	$id = StaysailIO::get('id');
    	
    	$map = Maps::getGalleryMap();
    	
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

		$reviews = new ReviewsView();
		$containers[] = new StaysailContainer('C', 'posts', $reviews->getHTML());
		$layout = new StaysailLayout($map, $containers);
		return $layout->getHTML();	
    }
    
}