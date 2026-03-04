<?php

final class Private_Message extends StaysailEntity
{
    // Data model properties
    public $name = parent::Line;  // Subject
    public $sort = parent::Int;

    public $from_Admin = parent::AssignOne;
    public $from_Member = parent::AssignOne;
    public $to_Member = parent::AssignOne;
    public $send_time = parent::Time;
    public $receive_time = parent::Time;
    public $content = parent::Text;
    public $deleted = parent::Boolean;


    // Metadata properties
    //protected $_sort          = 'name ASC';
    //protected $_name_template = '{name}';
    private $_cleaned;

    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);		
        $this->_cleaned = false;
    }

    public function delete_Job() {parent::delete();}	

    public function copy_Job() {return $this->copy();}

    public function markDeleted()
    {
    	$this->update(array('deleted' => 1));
    	$this->save();
    }
    
    public function getSelectorHTML($sent = false)
    {
    	$name = $this->cleanUp($this->name);
    	$link = "<a href=\"?mode=Message&job=read&id={$this->id}\">{$name}</a>";
    	$send_time = date('m/d/y h:ia', strtotime($this->send_time));
    	
    	if ($sent) {
    		$recipient = $this->getToMember();
    		if ($recipient->id) {
    			$avatar = $recipient->getAvatarHTML(Member::AVATAR_TINY);
    			$to = $recipient->name;
    		} else {
    			$avatar = '';
    			$to = '???';
    		}
    		
    		$html = <<<__END__

<a href=?mode=Message&job=read&id={$this->id}>
	    		<div class="private_message_selector">
	    	   <div style="margin-left: 45px;"><strong>{$name}</strong><br/></div>
	    	   {$avatar}
	    		To {$to} <br/>
	    		<div class ="private_message_selector_unread_time">On {$send_time} </div>
	    		</div></a>

__END__;
    	} else {
	    	$sender = $this->getFromMember();
	    	if ($sender->id) {
		    	$avatar = $sender->getAvatarHTML(Member::AVATAR_TINY);
	    		$from = $sender->name;
	    	} else {
                $avatar = $sender->getAvatarHTML(Member::AVATAR_TINY);
	    		$from = '???';
	    		if ($this->from_Admin_id) {$from = 'LSF Admin';}
	    	}
	    	
	    	$html = <<<__END__
                <a href=?mode=Message&job=read&id={$this->id}>
	    		<div class="private_message_selector_unread">
	    	   <div style="margin-left: 45px;"><strong>{$name}</strong><br/></div>
	    	   {$avatar}
	    		From {$from} <br/>
	    		<div class ="private_message_selector_unread_time">On {$send_time} </div>
	    		</div></a>
__END__;
    	}
    	return $html;
    }
    
    public function getFromMember()
    {
    	$member_id = $this->from_Member_id;
    	$Member = new Member($member_id);
    	return $Member;
    }
   
    public function getToMember()
    {
    	$member_id = $this->to_Member_id;
    	$Member = new Member($member_id);
    	return $Member;
    }
    
    public function getHTML()
    {
    	$writer = new StaysailWriter('message');
    	$this->update(array('receive_time' => StaysailIO::now()));
    	$this->save();

    	$content = $this->cleanUp(StaysailWriter::textToHTML($this->content));
    	$name = $this->cleanUp($this->name);
    	$sender = $this->getFromMember();
    	$avatar = $sender->getAvatarHTML(Member::AVATAR_TINY);
    	$from = $sender->name;
    	$recipient = $this->getToMember();
    	$to = $recipient->name;
    	if ($this->from_Admin_id) {$from = 'YoursFans Administrator';}
    	$cleaned_notice = $this->_cleaned ? "<div class=\"sent\"><strong>Note:</strong> Unauthorized language has been removed from this message.</div>" : '';
    	$send_time = date('m/d/y h:ia', strtotime($this->send_time));
    	$read_time = date('m/d/y h:ia', strtotime($this->receive_time));

        $buttons = "<a href=\"?mode=Message&id={$this->id}&job=compose\" class=\"button\" style='color: green;'>Reply</a> ";
        $buttons .= "<a href=\"?mode=Message&id={$this->id}&job=delete\" class=\"button\" style='color:red;'>Delete</a>";

    	$html = <<<__END__
    		<h1>{$avatar} {$name}</h1>
    		<div class="from">From {$from}</div>
    		<div class="sent">On {$send_time}, read on {$read_time}</div>
    		{$cleaned_notice}
    		<div class="content">{$content}</div>
            {$buttons}
__END__;

		$writer->addHTML($html);
		return $writer->getHTML();
    }
    
    public function cleanUp($txt)
    {
    	// If the message is from an admin, don't clean it
    	if ($this->from_Admin_id) {return $txt;}
    	
    	$wordlist = array();
    	$prohibited_words = $this->_framework->getSubset('Prohibited_Word');
    	foreach ($prohibited_words as $Prohibited_Word)
    	{
    		$pattern = $Prohibited_Word->name;
    		$txt = preg_replace("/{$pattern}/i", '<span class="redact">' . str_repeat('_', strlen($pattern)) . '</span>', $txt);
    	}
    	if (strstr($txt, '<span class="redact">')) {$this->_cleaned = true;}
    	return $txt;
    }

}