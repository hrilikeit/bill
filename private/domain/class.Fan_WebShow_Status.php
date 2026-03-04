<?php

final class Fan_WebShow_Status extends StaysailEntity
{
    // Data model properties
    public $name = parent::Line;
    public $sort = parent::Int;
	
    public $Fan = parent::AssignOne;
    public $WebShow = parent::AssignOne;
    public $Order = parent::AssignOne;
    public $start_time = parent::Time;
    public $last_poll_time = parent::Time;
    public $minutes_purchased = parent::Int;
    public $polls = parent::Int;
    public $payment_captured = parent::Boolean;

    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);
    }

    public function delete_Job() {parent::delete();}	

    public function copy_Job() {return $this->copy();}
   
    public function secondsSinceLastPoll()
    {
    	$last_time = strtotime($this->last_poll_time);
    	return (time() - $last_time);
    }
    
    public function advancePoll()
    {
    	$this->last_poll_time = date('Y-m-d H:i:s');
    	$this->polls = $this->polls + 1;
    	$this->save();
    	return $this->getRemainingTime();
    }
    
    public function getRemainingTime()
    {
    	$seconds_used = ($this->polls - 1) * 5;
    	if ($seconds_used < 0) {$seconds_used = 0;}
    	$seconds_left = ($this->minutes_purchased * 60) - $seconds_used;
    	$minutes = intval($seconds_left / 60);
    	$seconds = $seconds_left - ($minutes * 60);
    	$seconds = str_pad($seconds, 2, '0', STR_PAD_LEFT);
    	return "{$minutes}:{$seconds}";
    }
    
    public function isExpired()
    {
    	$seconds_used = ($this->polls - 1) * 5;
    	if ($seconds_used < 0) {$seconds_used = 0;}
    	if ($seconds_used < ($this->minutes_purchased * 60)) {
    		return false;
    	}
    	return true;
    }
    
    public function getMinutesUsed()
    {
    	$seconds_used = ($this->polls - 1) * 5;
    	$minutes_used = ceil($seconds_used / 60);
    	if ($minutes_used > $this->minutes_purchased) {
    		$minutes_used = $this->minutes_purchased;
    	}
    	if ($minutes_used < 0) {$minutes_used = 0;}
    	return $minutes_used;
    }
}