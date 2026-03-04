<?php

final class Member_Email_Prefs extends StaysailEntity
{
    // Data model properties
    public $name = parent::Line;
    public $sort = parent::Int;

    public $Member = parent::AssignOne;
    public $change_time = parent::Time;
    public $preferences = parent::Boolean;
    
    const Optout = 1;

    // Metadata properties
    //protected $_sort          = 'name ASC';
    //protected $_name_template = '{name}';

    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);		
    }

    public function delete_Job() {$this->delete();}
    
    public function copy_Job() {return $this->copy();}
    
    public function optedOut()
    {
    	$prefs = $this->preferences;
    	return ($prefs & 1);
    }
}