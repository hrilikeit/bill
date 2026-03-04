<?php

final class Order_Line extends StaysailEntity
{
    // Data model properties
    public $name = parent::Line;
    public $sort = parent::Int;

    public $Order = parent::AssignOne;
    public $line_time = parent::Time;
    public $description = parent::Line;
    public $price_ea = parent::Currency;
    public $quantity = parent::Currency;
    public $price = parent::Currency;
    public $cancel = parent::Boolean;
    public $domain_entity = parent::Line;
    public $entity_id = parent::Int;

    // Metadata properties
    //protected $_sort          = 'name ASC';
    //protected $_name_template = '{name}';

    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);		
    }

    public function delete_Job() {parent::delete();}	

    public function copy_Job() {return $this->copy();}
    
    public function cancel()
    {
    	$this->cancel = 1;
    	$this->save();
    }

}