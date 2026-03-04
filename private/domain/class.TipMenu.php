<?php

final class TipMenu extends StaysailEntity
{
    // Data model properties
    
    public $Member = parent::AssignOne;
    public $Entertainer = parent::AssignOne;

    public $name = parent::Line;
    public $price = parent::Int;

    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);
    }

    public function delete_Job() {$this->delete();}

    public function copy_Job() {return $this->copy();}
 
}