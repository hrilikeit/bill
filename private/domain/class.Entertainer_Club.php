<?php

final class Entertainer_Club extends StaysailEntity
{
    // Data model properties
    public $name = parent::Line;
    public $sort = parent::Int;

    public $Club = parent::AssignOne;
    public $Entertainer = parent::AssignOne;
    public $active_time = parent::Time;
    public $expire_time = parent::Time;
    public $active = parent::Boolean;


    // Metadata properties
    //protected $_sort          = 'name ASC';
    //protected $_name_template = '{name}';

    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);		
    }



    public function delete_Job() {parent::delete();}	

    public function copy_Job() {return $this->copy();}

}