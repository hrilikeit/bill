<?php

final class MeetingPost extends StaysailEntity
{
	// Data model properties
    public $name = parent::Line;
    public $sort = parent::Int;
    
	public $Entertainer = parent::AssignOne;
	public $Member = parent::AssignOne;
	public $content = parent::Text;
	public $post_time = parent::Time;
	public $WebShow = parent::AssignOne;

	public function __construct($id = null)
	{
		parent::__construct(__CLASS__, $id);		
	}

	public function delete_Job() {parent::remove();}	
	
	public function getPostAs()
	{
		if (preg_match('/<strong>(.+)<\/strong>/', $this->content, $m)) {
			return $m[1];
		}
		return 'A participant';
	}
	
	public function getHTML()
	{
		if (StaysailIO::session('Member.id')) {
			$Member = new Member(StaysailIO::session('Member.id'));
			$member_time = $Member->localToMemberTime($this->post_time);
		} else {
			$member_time = strtotime($this->post_time);
		}
		$post_time_display = date('g:ia', $member_time);
		$content = str_replace('%TIME%', $post_time_display, $this->content);
		
		return $content;
	}
}
