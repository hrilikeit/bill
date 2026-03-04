<?php

final class Member_Social_Media extends StaysailEntity
{
    // Data model properties
    public $name = parent::Line;
    public $sort = parent::Int;

    public $Member = parent::AssignOne;
    public $facebook_username = parent::Line;
    public $facebook_password = parent::Line;
    public $twitter_username = parent::Line;
    public $twitter_password = parent::Line;


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