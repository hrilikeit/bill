<?php

class EntertainerModule extends StaysailPublic implements AccountPublic
{
	protected $page, $settings, $categories;
    protected $framework;

    public $Member;
    public $Entertainer;
    public $valid;
    public $stage_text;
    
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
        $stage_text = StaysailIO::get('keyword');
    	
    	switch ($job)
    	{
    		case 'profile':
    			return $this->editProfile();
    			break;
    			
    		case 'post_profile':
    			$this->postProfile();
    			break;
    			
    		case 'member_data':
    			return $this->editMember();
    			break;
    			
    		case 'post_member':
    			$this->postMember();
    			break;
    			
    		case 'history':
    			return $this->history();
    			break;
    		
    		case 'post_bio':
    			$this->postBio();
    			break;
    			
    		// EntertainerGallery
    		case 'post_image':
    			$this->postImage($id);
    			break;
    			
    		case 'remove_image':
    			$this->removeImage($id);
    			break;
    			
    		// EntertainerSchedule
    		case 'post_show':
    			$this->postShow($id);
    			break;
    			
    		// EntertainerChat
    		case 'post_reply':
    			$this->postReply($id);
    			break;

            case 'get_stage_name':
                $stage_text_html = $this->getStageName($stage_text);
                return $stage_text_html;
                break;
    	}
    	
    	
    	$focus = StaysailIO::get('focus'); // The name of the View that should occupy the main panel
    	if ($focus) {
    		$view_list = array($focus);
    	} else {
    		$view_list = array('EntertainerBio', 'EntertainerChat');
    	}
    	return $this->getPanelsForViews($view_list, LSFView::MainVersion);
    }
    
    public function getStageName($stage_text){
        return $this->Entertainer->get_stage_name($stage_text);
    }

    public function getLeftPanelHTML()
    {
    	$view_list = array('EntertainerSchedule', 'EntertainerGallery');
    	return $this->getPanelsForViews($view_list, LSFView::DashVersion);
    }
    
    public function getRightPanelHTML()
    {
    	$view_list = array('EntertainerFanCenter', 'BannerAds');
    	return $this->getPanelsForViews($view_list, LSFView::DashVersion);
    }
    
    private function getPanelsForViews($view_list, $version = LSFView::MainVersion)
    {
    	$writer = new StaysailWriter();
    	foreach ($view_list as $view_name)
    	{
    		require_once DOCROOT . "/../private/views/view.{$view_name}.php";
    		$view = new $view_name($this->Member, $this->Entertainer, $version);

    		$view_writer = new StaysailWriter($view_name);
    		$view_writer->draw($view);
    		$writer->draw($view_writer);
    	}
    	return $writer->getHTML();
    }
    
    private function postBio()
    {
    	$this->Entertainer->bio = StaysailIO::post('bio');
    	$this->Entertainer->save();
    }
    
    private function postImage($library_id = null)
    {
    	if (!$library_id) {
    		// A new image has fields that can't be changed by the Entertainer later:
    		$Library = new Library();
    		$Library->placement = StaysailIO::post('placement');
    		$Library->status = 'pending';
    		$Library->Member = $this->Member;
    		$Library->save(); // Save here to get ID
    		$Library->image = $Library->uploadFile('image', $Library->id);
    		$Library->size = StaysailIO::getFileInfo('image', 'size');
    		$Library->mime_type = StaysailIO::getFileInfo('image', 'type');
    		$Library->save();
    	} else {
    		$Library = new Library($library_id);
    		if (!$Library->belongsTo($this->Member)) {
    			return false;
    		}
    	}
    	$fields = array('name', 'description', 'keywords');
    	$Library->updateFrom($fields);
    	$Library->save();
    }
    
    private function removeImage($library_id)
    {
    	$Library = new Library($library_id);
    	if ($Library->belongsTo($this->Member)) {
	    	$Library->setInactive();
    	}
    }
    
    private function postShow($show_id = null)
    {
    	if (!$show_id) {
    		$Show_Schedule = new Show_Schedule();
    		$Show_Schedule->Entertainer = $this->Entertainer;
    	} else {
    		$Show_Schedule = new Show_Schedule($show_id);
    		if (!$Show_Schedule->belongsTo($this->Member)) {
    			return false;
    		}
    	}
    	$fields = array('start_time', 'end_time', 'type', 'description', 'max_viewers');
    	$Show_Schedule->updateFrom($fields);
    	$Show_Schedule->save();
    }
    
    private function editProfile()
    {
    	$writer = new StaysailWriter();
    	$profile = $this->Entertainer->getProfileForm(__CLASS__, 'post_profile');
    	$member_link = StaysailWriter::makeJobLink('Personal Information', __CLASS__, 'member_data');
    	$pricing_link = StaysailWriter::makeJobLink('Pricing Information', __CLASS__, 'pricing_data');
    	$history_link = StaysailWriter::makeJobLink('History', __CLASS__, 'history');
    	$writer->h1('Your Profile')
    		   ->p("{$member_link} | {$pricing_link} | {$history_link}")
    	       ->draw($profile);
		return $writer->getHTML();
    }
    
    private function postProfile()
    {
    	$this->Entertainer->saveProfile();
    }
    
    private function editMember()
    {
    	$writer = new StaysailWriter(__CLASS__);
    	$writer->h1('Personal Information');
    	
    	$expiration = $this->Member->getExpirationDate();
    	
    	$member = new StaysailForm('form');
    	$member->setPostMethod()
    	       ->setJobAction(__CLASS__, 'post_member')
    	       ->setDefaults($this->Member->info())
    	       ->addField(StaysailForm::Line, 'First Name', 'first_name', 'required')
    	       ->addField(StaysailForm::Line, 'Last Name', 'last_name', 'required')
    	       ->addField(StaysailForm::Line, 'Email Address', 'email', 'required')
    	       ->addField(StaysailForm::Line, 'Phone Number', 'phone', 'required')
    	       ->addField(StaysailForm::Bool, "Auto-Renew Membership (Expires on {$expiration})", 'auto_renew')
    	       ->addField(StaysailForm::Password, 'Password', 'password')
    	       ->addField(StaysailForm::Password, 'Repeat Password', 'password_2', 'require-match:password');
    	
    	$writer->draw($member);
    	
    	return $writer->getHTML();
    }
    
    private function postMember()
    {
    	$fields = array('first_name', 'last_name', 'email', 'phone', 'auto_renew');
    	$this->Member->updateFrom($fields);
    	$password = StaysailIO::post('password');
    	$password2 = StaysailIO::post('password_2');
    	if ($password and $password == $password2) {
    		$this->Member->setPassword($password);
    	}
    	$this->Member->save();
    }
    
    private function postReply($parent_id)
    {
    	$content = StaysailIO::post('content');
    	if (!trim($content)) {return false;}
    	$Post = new Post();
    	$data = array('post_time' => date('Y-m-d H:i:s'),
    				  'active' => 1,
    				  'Member_id' => $this->Member->id,
    				  'Post_id' => $parent_id,
    				  'content' => StaysailIO::post('content'),
    	             );
		$Post->update($data);
		$Post->save();    	             
    }
    
    private function history()
    {
    	return $this->Member->getHistoryHTML();
    }
}