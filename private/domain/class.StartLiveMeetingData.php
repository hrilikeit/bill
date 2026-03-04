<?php

final class StartLiveMeetingData extends StaysailEntity
{
    // Data model properties

    public $Member_id = parent::Int;
    public $ingest_server = parent::Line;
    public $livestream_id = parent::Line;
    public $stream_key = parent::Line;
    public $playback_url = parent::Line;

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