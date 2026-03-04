<?php

final class Review extends StaysailEntity
{
    // Data model properties
    public $name = parent::Line;
    public $sort = parent::Int;

    public $Member = parent::AssignOne;
    public $Entertainer = parent::AssignOne;
    public $Club = parent::AssignOne;
    public $review_time = parent::Time;
    public $rating = parent::Int;
    public $content = parent::Text;
    
    // Administrator Review
    public $admin_status = parent::Enum;
    public $status_note = parent::Line;
    public $status_time = parent::Time;
    public $Admin = parent::AssignOne;
    
    // Metadata properties
    //protected $_sort          = 'name ASC';
    //protected $_name_template = '{name}';

    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);		
    }
    
    public function admin_status_Options() {return array('pending' => 'pending','approved' => 'approved',
    											'denied' => 'denied', 'flagged' => 'flagged');}
    
    public function delete_Job() {parent::delete();}	

    public function copy_Job() {return $this->copy();}
    
    public function getHTML()
    {
    	if (is_numeric($this->rating)) {
    		$stars = Icon::getStarRatingHTML($this->rating);
    	} else {
    		$stars = "No Rating";
    	}
    	$screen_name = $this->Member->name;
    	$content = str_replace("\n", '<br/>', $this->content);
    	$writer = new StaysailWriter();
    	$writer->h2("{$this->name} {$stars}")
    	       ->p($this->content)
    	       ->p("Submitted on " . date(SHORT_DATE_FORMAT, strtotime($this->review_time)) . " by {$screen_name}");

    	if ($this->admin_status != 'approved') {
	    	$writer->p("<div id=\"review{$this->id}\">" . StaysailWriter::makeLink('Flag as Inappropriate', "javascript:flagReview({$this->id})") . "</div>");
    	}
		$writer->addHTML('<hr/>');
		return $writer->getHTML();    	       
    }

}