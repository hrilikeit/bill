<?php

final class DyteParticipant extends StaysailEntity
{
    // Data model properties


    public $Member = parent::AssignOne;
    public $WebShowDyte = parent::AssignOne;
 
    public $participant_id = parent::Line;
    public $name = parent::Line;
    public $picture = parent::Line;
    public $custom_participant_id = parent::Line;
    public $preset_name = parent::Line;
    public $created_at = parent::Date;
    public $updated_at = parent::Date;
    public $token = parent::Line;

    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);
    }


    public function delete_Job() {$this->delete();}

    public function copy_Job() {return $this->copy();}

}