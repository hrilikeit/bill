<?php

final class Order_Line_Revenue_Split extends StaysailEntity
{
    // Data model properties
    public $name = parent::Line;
    public $sort = parent::Int;

    public $Order_Line = parent::AssignOne;
    public $Member = parent::AssignOne;
    public $amount = parent::Currency;
    public $Revenue_Split_Payment = parent::AssignOne;


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