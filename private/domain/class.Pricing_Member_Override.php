<?php

final class Pricing_Member_Override extends StaysailEntity
{
    // Data model properties
    public $name = parent::Line;
    public $sort = parent::Int;

    public $Member = parent::AssignOne;
    public $Pricing = parent::AssignOne;
    public $price = parent::Currency;
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