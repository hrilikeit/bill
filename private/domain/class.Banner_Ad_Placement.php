<?php

final class Banner_Ad_Placement extends StaysailEntity
{
    // Data model properties
    public $name = parent::Line;
    public $sort = parent::Int;

    public $Banner_Ad = parent::AssignOne;
    public $placement = parent::Enum;


    // Metadata properties
    //protected $_sort          = 'name ASC';
    //protected $_name_template = '{name}';

    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);		
    }

    public function placement_Options {return array('club' => 'club','entertainer' => 'entertainer',);}



    public function delete_Job() {parent::delete();}	

    public function copy_Job() {return $this->copy();}

}