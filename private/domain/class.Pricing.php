<?php

final class Pricing extends StaysailEntity
{
    // Data model properties
    public $name = parent::Line;
    public $sort = parent::Int;

    public $code = parent::Line;
    public $description = parent::Text;
    public $price = parent::Currency;
    public $File_Type = parent::AssignOne;
    public $entertainer_split = parent::Currency;
    public $club_split = parent::Currency;


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