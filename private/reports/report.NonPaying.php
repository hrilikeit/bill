<?php

class NonPaying extends StaysailReport
{
	public function getName() {return 'Signed up, but not paid';}
	
	public function __construct()
	{
		parent::__construct();
		
//		$this->sql = <<<__END__
//
//		SELECT CONCAT(Member.last_name, ', ', Member.first_name) AS `Member`,
//				Member.email AS `email`, Member.name AS `Screen Name`,
//				Member.online_time AS `Last Online`
//		FROM `Member`
//
//		INNER JOIN `Fan`
//			ON Fan.Member_id = Member.id
//
//		WHERE Member.expire_time = 0
//__END__;

        $this->sql = <<<__END__
		
		SELECT CONCAT(Member.last_name, ', ', Member.first_name) AS `Member`,
				Member.email AS `email`, Member.name AS `Screen Name`,
				Member.online_time AS `Last Online`
		FROM `Member`
		
		INNER JOIN `Fan`
			ON Fan.Member_id = Member.id
		
		LEFT JOIN `Payment_Method`
			ON Payment_Method.Member_id = Member.id

		WHERE Member.auto_renew = 0
		AND Payment_Method.Member_id IS NULL
__END__;

    }
	
	public function getCSV()
	{
        return $this->runReportAsCSV();
	}
	
	public function getHTML()
	{
		return $this->runReportAsHTML();
	}
	
}