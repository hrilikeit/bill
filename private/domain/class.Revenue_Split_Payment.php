<?php

final class Revenue_Split_Payment extends StaysailEntity
{
    // Data model properties
    public $name = parent::Line;  // payment reference info (check number, etc)
    public $sort = parent::Int;

    public $payment_time = parent::Time;
    public $amount = parent::Currency;
    public $payee = parent::Line;
    public $memo = parent::Line;


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