<?php

class FanModule extends StaysailPublic implements AccountPublic
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
        $this->Fan = $this->Member->getAccountOfType('Fan');

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
    		$view_list = array('FanSubscriptions');
    	}
    	return $this->getPanelsForViews($view_list, LSFView::MainVersion);
    }
    
    public function getLeftPanelHTML()
    {
    	$view_list = array('FanLibrary', 'FanMessages');
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

    		$view_writer = new StaysailWriter($view_name);
    		$view_writer->draw($view);
    		$writer->draw($view_writer);
    	}
    	return $writer->getHTML();
    }
    
    private function history()
    {
    	return $this->Member->getHistoryHTML();
    }
}