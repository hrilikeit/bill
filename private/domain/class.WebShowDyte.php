<?php


final class WebShowDyte extends StaysailEntity
{
    // Data model properties

    public $Member = parent::AssignOne;
    public $Entertainer = parent::AssignOne;
    public $Fan_id = parent::Int;
    public $meeting_id = parent::Line;
    public $title = parent::Line;
    public $show_type = parent::Boolean;
    public $like_count = parent::Int;
    public $public_live_status = parent::Int;
    public $thumbnail = parent::Line;
    public $view_count = parent::Line;
    public $update_at = parent::Date;

    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);
    }
    
    public function delete_Job() {$this->delete();}

    public function copy_Job() {return $this->copy();}

}