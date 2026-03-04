<?php

class Payments extends StaysailReport
{

	public function __construct()
	{
		parent::__construct();
		
		$this->sql = <<<__END__
		
		SELECT DATE_FORMAT(Order.order_time, '%b %d, %X') AS `date`, Order.order_time, 
			CONCAT(Member.last_name, ', ', Member.first_name) AS `member`, Order.order_code,
			Order.authcode, Order.transactionid, Order.payment_amount
		
		FROM `Order`
		
		INNER JOIN `Member`
			ON Member.id = Order.Member_id
			
		WHERE Order.payment_amount > 0
			AND Order.authcode != '123456'
			
		ORDER BY Order.order_time DESC
		
__END__;
	}
	
	public function getCSV()
	{
		
	}
	
	public function getHTML()
	{
		$this->setSubsummaries(array('date'));
		return $this->runReportAsHTML();
	}
	
	public function getName() {return 'Payments Report';}
}