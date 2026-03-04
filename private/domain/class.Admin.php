<?php

final class Admin extends StaysailEntity
{
    // Data model properties
    public $name = parent::Line;
    public $sort = parent::Int;

    public $username = parent::Line;
    public $password = parent::Line;
    public $active = parent::Boolean;
    public $real_name = parent::Line;
    public $email = parent::Line;

    // Metadata properties
    //protected $_sort          = 'name ASC';
    //protected $_name_template = '{name}';
    
    private $privileges;

    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);		
    }

    public function delete_Job() 
    {
    	$this->_framework->query("DELETE FROM Admin_Privilege WHERE Admin_id = {$this->id}");
    	parent::delete();
    }	

    public function copy_Job() {return $this->copy();}

    public function has($privilege)
    {
    	if (!is_array($this->privileges)) {
    		$this->privileges = array();
    		$admin_privileges = $this->_framework->getSubset('Admin_Privilege', new Filter(Filter::Match, array('Admin_id' => $this->id)));
    		foreach ($admin_privileges as $Admin_Privilege)
    		{
    			$this->privileges[] = $Admin_Privilege->name;
    		}
    	}
    	return in_array($privilege, $this->privileges);
    }
}