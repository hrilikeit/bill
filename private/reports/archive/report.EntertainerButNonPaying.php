<?php

class EntertainerButNonPaying extends StaysailReport
{
	public function getName() {return 'People who have signed up and selected an Entertainer but not paid';}
	
	public function __construct()
	{
		parent::__construct();
		
		$this->sql = <<<__END__
		
SELECT DISTINCT CONCAT(last_name, ', ', first_name) AS `Member`, Member.email, Fan.name AS `Screen_Name`, Member.online_time
FROM `Member`

INNER JOIN `Fan`
ON Member_id = Member.id

INNER JOIN `Fan_Subscription`
ON Fan_Subscription.Fan_id = Fan.id

WHERE Member.expire_time = 0		
		
__END__;
	}
	
	public function getCSV()
	{
		
	}
	
	public function getHTML()
	{
		return $this->runReportAsHTML();
	}
	
}