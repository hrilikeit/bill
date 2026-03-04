<?php

class EntertainerSite extends StaysailPublic implements AccountPublic
{
	protected $page, $settings, $categories;
    protected $framework;

    public $Member;
	public $Fan;    
    public $Entertainer;
    public $valid;
    public $subscriber;
    
    public function __construct($dbc = '')
    {
    	$this->valid = false;
    	
        $this->framework = StaysailIO::engage();
        
        $member_id = StaysailIO::session('Member.id');
        $this->Member = new Member($member_id);
        $this->Fan = $this->Member->getAccountOfType('Fan');
        
        $fan_url = StaysailIO::get('e');
        if (!$fan_url) {
        	$fan_url = StaysailIO::session('fan_url');
        }
        StaysailIO::setSession('fan_url', $fan_url);
        $this->Entertainer = Entertainer::getEntertainerByFanURL($fan_url);

        if ($this->Member and $this->Entertainer) {
        	$this->valid = true;
        }
        $this->subscriber = ($this->Fan) ? $this->Fan->isSubscribedTo($this->Entertainer) : [];
    }

    public function getHTML()
    {
    	$job = StaysailIO::get('job');
    	$id = StaysailIO::get('id');
    	
    	switch ($job)
    	{
    		case 'post_comment':
    			$this->postComment();
    			break;
    	}
    	
    	$focus = StaysailIO::get('focus'); // The name of the View that should occupy the main panel
    	if ($focus) {
    		$view_list = array($focus);
    	} else {
    		$view_list = array('EntertainerSiteBody');
    	}
    	return $this->getPanelsForViews($view_list, LSFView::MainVersion);
    }
    
    public function getLeftPanelHTML()
    {
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
    		$view = new $view_name($this->Member, $this->Entertainer, $version);
    		if (strstr($view_name, 'EntertainerSite')) {
    			$view->setSubscribed($this->subscriber);
    		}

    		$view_writer = new StaysailWriter($view_name);
    		$view_writer->draw($view);
    		$writer->draw($view_writer);
    	}
    	return $writer->getHTML();
    }
    
    private function postComment()
    {
    	$comment = StaysailIO::post('comment');
    	$post_id = StaysailIO::post('post_id');
    	$parent_Post = new Post($post_id);	
    	if ($parent_Post->Member->id != $this->Entertainer->Member->id) {return false;}

    	$Post = new Post();
    	$updates = array('content' => $comment,
    					 'post_time' => StaysailIO::now(),
    					 'Post_id' => $parent_Post->id,
    					 'Member_id' => $this->Member->id,
				    	);
		$Post->update($updates);
		$Post->save();				    	
    }
    
}