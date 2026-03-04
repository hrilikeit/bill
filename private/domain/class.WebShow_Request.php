<?php

final class WebShow_Request extends StaysailEntity
{
    // Data model properties
    public $name = parent::Line; // Channel name
    public $sort = parent::Int;

	public $Member = parent::AssignOne;  // The person making the request
	public $Entertainer = parent::AssignOne;
	public $ring_time = parent::Time;
	public $private_request = parent::Boolean; // Whether the requesting member wants a private show
	public $delivered = parent::Boolean;

	// Metadata properties
    //protected $_sort          = 'name ASC';
    //protected $_name_template = '{name}';

    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);	
    }
    
    public function delete_Job() {parent::delete();}	

    public function copy_Job() {return $this->copy();}

	public function makeRequest(Member $Member, Entertainer $Entertainer, $private_request = false)
	{
		$private_request = $private_request ? 1 : 0;
		
		$content = '[[DOORBELL]]';
		$MeetingPost = new MeetingPost();
		$MeetingPost->update(array('Member_id' => $Member->id,
								   'Entertainer_id' => $Entertainer->id,
								   'content' => $content,
								  ));
		$MeetingPost->save();
		
		$this->update(array('Member_id' => $Member->id, 'Entertainer_id' => $Entertainer->id, 'ring_time' => StaysailIO::now(), 'private_request' => $private_request));
		$this->save();
	}
	
	public function deliver()
	{
		$this->delivered = 1;
		$this->save();
	}
	
	public function getNotificationCommand()
	{
		$cmd = 'doorbell';
		$name = $this->Member->name;
		$show = $this->private_request ? 'private show' : 'group show';
		$param = "<p><strong>{$name}</strong> rang your doorbell to request a <strong>{$show}</strong>!</p>";
		return "{$cmd}::{$param}";
	}
}
