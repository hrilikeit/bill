<?php

final class Fan_Favorite_Club extends StaysailEntity
{
    // Data model properties
    public $name = parent::Line;
    public $sort = parent::Int;

    public $Fan = parent::AssignOne;
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

}