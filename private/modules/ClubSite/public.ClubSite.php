<?php

class ClubSite extends StaysailPublic implements AccountPublic
{
	protected $page, $settings, $categories;
    protected $framework;

    public $Member;
	public $Fan;    
    public $Club;
    public $valid;
    
    public function __construct($dbc = '')
    {
    	$this->valid = false;
    	
        $this->framework = StaysailIO::engage();
        
        $member_id = StaysailIO::session('Member.id');
        $this->Member = new Member($member_id);
        $this->Fan = $this->Member->getAccountOfType('Fan');
        
        $fan_url = StaysailIO::get('c');
        $this->Club = Club::getClubByFanURL($fan_url);

        if ($this->Member and $this->Fan) {
        	$this->valid = true;
        }
    }

    public function getHTML()
    {
    	$job = StaysailIO::get('job');
    	$id = StaysailIO::get('id');
    	
    	switch ($job)
    	{

    	}
    	
    	
    	$focus = StaysailIO::get('focus'); // The name of the View that should occupy the main panel
    	if ($focus) {
    		$view_list = array($focus);
    	} else {
    		$view_list = array('ClubEntertainers');
    	}
    	return $this->getPanelsForViews($view_list, LSFView::MainVersion);
    }
    
    public function getLeftPanelHTML()
    {
    	return '&nbsp;';
    	$view_list = array('EntertainerSiteActions');
    	return $this->getPanelsForViews($view_list, LSFView::DashVersion);
    }
    
    public function getRightPanelHTML()
    {
    	$view_list = array('BannerAds');
    	return $this->getPanelsForViews($view_list, LSFView::DashVersion);
    }
    
    private function getPanelsForViews($view_list, $version = LSFView::MainVersion)
    {
    	$writer = new StaysailWriter();
    	foreach ($view_list as $view_name)
    	{
    		require_once DOCROOT . "/../private/views/view.{$view_name}.php";
    		$view = new $view_name($this->Member, $this->Fan, $version);
    		if ($view_name == 'ClubEntertainers') {
	    		$view->setClub($this->Club);
    		}

    		$view_writer = new StaysailWriter($view_name);
    		$view_writer->draw($view);
    		$writer->draw($view_writer);
    	}
    	return $writer->getHTML();
    }
    

}