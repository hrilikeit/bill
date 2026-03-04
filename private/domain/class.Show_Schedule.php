<?php

final class Show_Schedule extends StaysailEntity
{
    // Data model properties
    public $name = parent::Line;
    public $sort = parent::Int;

    public $Entertainer = parent::AssignOne;
    public $Fan = parent::AssignOne;
    public $Club = parent::AssignOne;
    public $start_time = parent::Time;
    public $end_time = parent::Time;
    public $description = parent::Text;
    public $max_viewers = parent::Int;
    public $chat_active = parent::Boolean;
    public $cancel = parent::Boolean;
    public $type = parent::Enum;

    // Metadata properties
    //protected $_sort          = 'name ASC';
    //protected $_name_template = '{name}';

    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);		
    }
    
    public function type_Options() {return array('video' => 'video', 'chat' => 'chat', 'performance' => 'performance');}

    public function delete_Job() {parent::delete();}	

    public function copy_Job() {return $this->copy();}
    
    /**
     * Returns true if this Show belongs to the specified Member.  Make sure to check this
     * before you do any destructive editing of a Show.
     * 
     * @param Member
     */
    public function belongsTo(Member $Member)
    {
    	if ($this->Entertainer) {
	    	return ($Member->id == $this->Entertainer->Member->id);
    	}
    	if ($this->Club) {
    		$adminMember = $this->Club->getAdminMember();
	    	return ($Member->id == $adminMember->id);
    	}
    }

    public function getStartEnd()
    {
    	$start = date('m/d/y h:ia', strtotime($this->start_time));
    	$end = date('h:ia', strtotime($this->end_time));
    	return "{$start} - {$end}";
    }
    
    public function comingSoon()
    {
    	$now = time();
    	$start = strtotime($this->start_time);
    	$end = strtotime($this->end_time);
    	
    	if ($now > $start and $now < $end) {return true;}
    	if (($start - $now) < (24 * 60 * 60)) {return true;}
    	return false;
    }    
}