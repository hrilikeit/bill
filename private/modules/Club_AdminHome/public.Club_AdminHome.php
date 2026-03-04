<?php

class Club_AdminHome extends StaysailPublic
{
	protected $page, $settings, $categories;
    protected $framework;

    public $Member;
    public $Club_Admin;
    
    public function __construct($dbc = '')
    {
    	$this->valid = false;
    	
        $this->framework = StaysailIO::engage();
        
        $member_id = StaysailIO::session('Member.id');
        $this->Member = new Member($member_id);
        $this->Club_Admin = $this->Member->getAccountOfType('Club_Admin');
    }

    public function getHTML()
    {
    	$job = StaysailIO::get('job');
    	$id = StaysailIO::get('id');
    	
    	if (!$this->Club_Admin) {
    		return '';
    	}
    	
    	$map = Maps::getClubAdminHomeMap();
    	
    	$content = '';
    	switch ($job)
    	{
    		case 'update_club':
    			$this->updateClub();
    			$content = $this->photoForm();
    			break;
    			
    		case 'entertainers':
    			$content = $this->entertainerMenu();
    			break;
    			
    		case 'add_entertainer':
    			$content = $this->addEntertainer();
    			break;
    			
    		case 'post_add_entertainer':
    			$content = $this->postAddEntertainer();
    			break;
    			
    		case 'remove_entertainer':
    			$this->removeEntertainer($id);
    			$content = $this->entertainerMenu();
    			break;
    			
    		case 'photo':
    			$content = $this->photoForm();
    			break;
    			
    		case 'post_photo':
    			$this->postPhoto();
    			$content = $this->photoForm();
    			break;
    			
    		case 'profile':
    		default:
    			$content = $this->profileForm();
    			break;
    			
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
		$containers[] = new StaysailContainer('C', 'info', $content);
		$layout = new StaysailLayout($map, $containers);
		return $layout->getHTML();	
    }
    
    private function updateClub()
    {
    	$Club = $this->Club_Admin->Club;
    	if (!$Club) {
    		$Club = new Club();
    	}
    	$fields = array('name', 'address', 'address_2', 'city', 'state', 'zip', 'description', 'hours');
		$Club->updateFrom($fields);
		
		if (StaysailIO::post('embed_video')) {
			$embed_video = StaysailIO::post('embed_video');
			if (preg_match('/(http:\/\/www.youtube.com\/embed\/[^"]*)/', $embed_video, $m)) {
				$embed_video = $m[1];
				$Club->embed_video = $embed_video;
			}
		}
		
		$geo_location_id = StaysailIO::post('Geo_Location');
		$Club->Geo_Location_id = $geo_location_id;
		$Club->save();
		
		$this->Club_Admin->update(array('Club_id' => $Club->id));
		$this->Club_Admin->save();
    }
    
    private function profileForm()
    {
		$writer = new StaysailWriter();
		$form = $this->Club_Admin->getProfileForm();
		$writer->draw($form);
		return $writer->getHTML();
    	
    }
    
    private function photoForm()
    {
    	$writer = new StaysailWriter();
    	$photo = $this->Club_Admin->Club->getClubPhotoHTML();
    	$writer->p($photo);
    	$required = $photo ? '' : 'required';
		$upload = new StaysailForm();
		$upload->setJobAction(__CLASS__, 'post_photo')
			   ->setPostMethod()
			   ->setSubmit('Upload Photos')
			   ->addField(StaysailForm::File, 'Image (Required)', 'image', $required)
			   ->addField(StaysailForm::File, 'Menu (Optional)', 'menu')
			   ->addField(StaysailForm::Boolean, 'Delete Menu?', 'delete_menu')
   			   ->addField(StaysailForm::File, 'Flyer (Optional)', 'flyer')
   			   ->addField(StaysailForm::Boolean, 'Delete Flyer?', 'delete_flyer');
			   
		$writer->h1('Set Your Club Photos');
		$writer->draw($upload);
		return $writer->getHTML();
    }
    
    private function postPhoto()
    {
    	$Club = $this->Club_Admin->Club;
    	$Club->uploadClubPhoto();
    }
    
    private function entertainerMenu()
    {
    	$writer = new StaysailWriter();
    	
    	$current_table = new StaysailTable('club');
    	$potential_table = new StaysailTable('club');
    	
    	// Make current entertainer table
    	$writer->h1('Current Entertainers');
    	$Club = $this->Club_Admin->Club;
    	$entertainers = $Club->getEntertainers();
    	foreach ($entertainers as $Entertainer)
    	{
    		$Member = $Entertainer->Member;
    		$avatar = $Member->getAvatarHTML(Member::AVATAR_LITTLE);
    		$name = "{$Member->last_name}, {$Member->first_name}";
    		$stage_name = $Entertainer->stage_name;
    		$remove = "<a onclick=\"return confirm('Are you sure?');\" href=\"?mode=Club_AdminHome&job=remove_entertainer&id={$Entertainer->id}\" class=\"button\">Remove Entertainer</a>";
    		$current_table->addRow(array($avatar, $name, $stage_name, $remove));
    	}
    	$writer->draw($current_table);
    	return $writer->getHTML();
    }
    
    private function removeEntertainer($entertainer_id)
    {
    	$Club = $this->Club_Admin->Club;
    	$Entertainer = new Entertainer($entertainer_id);
    	if (!$Entertainer->isWithClub($Club)) {
    		return '';
    	}
    	
    	$Entertainer->unassignClub($Club);
    }
}