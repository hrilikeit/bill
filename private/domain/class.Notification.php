<?php

final class Notification extends StaysailEntity
{
    // Data model properties
    public $name = parent::Line;
    public $sort = parent::Int;

    public $Member = parent::AssignOne;
    public $email = parent::Line;
    public $send_time = parent::Time;
    public $subject = parent::Line;
    public $content = parent::Text;


    // Metadata properties
    //protected $_sort          = 'name ASC';
    //protected $_name_template = '{name}';

    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);		
    }

    public function delete_Job() {parent::delete();}	

    public function copy_Job() {return $this->copy();}

    public function setMail($email, $subject, $content)
    {
    	$this->update(array('email' => $email, 'subject' => $subject, 'content' => $content));
    	$this->save();
    }
    
    public function send()
    {
    	$success = mail($this->email, $this->subject, $this->message);
    	if ($success) {
    		$this->send_time = StaysailIO::now();
    	}
    }
}