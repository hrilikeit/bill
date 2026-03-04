<?php

final class Club_Admin extends StaysailEntity implements AccountType
{
    // Data model properties
    public $name = parent::Line;
    public $sort = parent::Int;

    public $Member = parent::AssignOne;
    public $Club = parent::AssignOne;


    // Metadata properties
    //protected $_sort          = 'name ASC';
    //protected $_name_template = '{name}';

    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);		
    }



    public function delete_Job() {parent::delete();}	

    public function copy_Job() {return $this->copy();}

    /* AccountType interface methods.  See interface.AccountType for documentation */
    public function getProfileForm($mode = '', $job = '')
    {
    	$geo_locations = $this->_framework->getOptions('Geo_Location');
    	StaysailIO::setSession('Member.id', $this->Member->id);
    	
    	$profile = new StaysailForm('form');
    	if ($this->Club) {
    		$profile->setDefaults($this->Club->info());
    		$submit = 'Update Profile';
    	} else {$submit = 'Add Club';}
    	$profile->setPostMethod()
    			->setSubmit($submit)
    			->setJobAction('Club_AdminHome', 'update_club')
    			->addHTML('<h1>Tell Us About Your Club...</h1>')
    			->addField(StaysailForm::Line, 'Club Name', 'name', 'required')
    			->addField(StaysailForm::Line, 'Address', 'address', 'required')
    			->addField(StaysailForm::Line, '&nbsp;', 'address_2')
    			->addField(StaysailForm::Line, 'City', 'city', 'required')
    			->addField(StaysailForm::Line, 'State', 'state', 'required state')
    			->addField(StaysailForm::Line, 'ZIP', 'zip', 'required zip')
    			->addField(StaysailForm::Select, 'Area', 'Geo_Location', 'required', $geo_locations)
    			->addField(StaysailForm::Text, 'Description', 'description', 'required')
    			->addField(StaysailForm::Text, 'Hours', 'hours', 'required')
    			->addField(StaysailForm::Text, 'YouTube Video Code', 'embed_video');
    			
    	return $profile;
    }
    
    public function saveProfile()
    {
    	
    }
    
    public function registerSession()
    {
    	StaysailIO::setSession('account_type', __CLASS__);
    	StaysailIO::setSession('account_entity_id', $this->id);
    }
    /* END OF AccountType interface methods */
    
}