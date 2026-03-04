<?php

final class PotentialEntertainr extends StaysailEntity
{
    // Data model properties
    public $name = parent::Line;
    public $sort = parent::Int;

    public $Club = parent::AssignOne;
    public $first_name = parent::Line;
    public $last_name = parent::Line;
    public $stage_name = parent::Line;
    public $signup_code = parent::Line;

    // Metadata properties
    //protected $_sort          = 'name ASC';
    protected $_name_template = '{last_name}, {first_name}';

    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);		
    }

    public function delete_Job() {parent::delete();}	

    public function copy_Job() {return $this->copy();}

}