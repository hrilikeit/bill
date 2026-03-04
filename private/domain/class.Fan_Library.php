<?php

final class Fan_Library extends StaysailEntity
{
    // Data model properties
    public $name = parent::Line;
    public $sort = parent::Int;

    public $Fan = parent::AssignOne;
    public $Library = parent::AssignOne;
    public $File_Type = parent::AssignOne;

    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);
    }

    public function delete_Job() {parent::delete();}	

    public function copy_Job() {return $this->copy();}
    
}