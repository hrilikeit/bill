<?php

class ClubDaily extends StaysailReport
{

	public function __construct()
	{
		parent::__construct();
		
		if (isset($_GET["mode"],$_GET["id"]) && htmlspecialchars($_GET["mode"]) == 'Administrator' && htmlspecialchars($_GET["id"]) == 'ClubDaily')
		{
			$this->sql = <<<__END__
		
		SELECT DATE_FORMAT(Order.order_time, '%b %d, %X') AS `date`, Order.order_time, 
			CONCAT(Member.last_name, ', ', Member.first_name) AS `member`, Order.order_code,
			Order.payment_amount, 
			IF(Entertainer.id IS NOT NULL AND Order_Line.description='Fan Tip','Tip', 
			 IF(Entertainer.id IS NOT NULL AND Order_Line.description !='Fan Tip', 'Subscription',
				IF(WebShow.id IS NOT NULL, 'Web Show',
					IF (Library.id IS NOT NULL, 'Image Purchase', '')))) AS `Description`
		
		FROM `Order`
		
		INNER JOIN `Order_Line`
			ON Order_Line.Order_id = Order.id
		
		INNER JOIN `Member`
			ON Member.id = Order.Member_id
			
		LEFT JOIN `Entertainer`
			ON Order_Line.entity_id = Entertainer.id
				AND Order_Line.domain_entity = 'Entertainer'
				
		LEFT JOIN `WebShow`
			ON Order_Line.entity_id = WebShow.id
				AND Order_Line.domain_entity = 'WebShow'
				
		LEFT JOIN `Library`
			ON Order_Line.entity_id = Library.id
				AND Order_Line.domain_entity = 'Library'
			
		WHERE Order.payment_amount > 0
				
		ORDER BY Order.order_time DESC
		
__END__;
		}else{
			$this->sql = <<<__END__
		
			SELECT DATE_FORMAT(Order.order_time, '%b %d, %X') AS `date`, Order.order_time, 
				CONCAT(Member.last_name, ', ', Member.first_name) AS `member`, Order.order_code,
				Order.payment_amount, 
				IF(Entertainer.id IS NOT NULL AND Order_Line.description='Fan Tip','Tip', 
				 IF(Entertainer.id IS NOT NULL AND Order_Line.description !='Fan Tip', 'Subscription',
					IF(WebShow.id IS NOT NULL, 'Web Show',
						IF (Library.id IS NOT NULL, 'Image Purchase', '')))) AS `Description`
			
			FROM `Order`
			
			INNER JOIN `Order_Line`
				ON Order_Line.Order_id = Order.id
			
			INNER JOIN `Member`
				ON Member.id = Order.Member_id
				
			LEFT JOIN `Entertainer`
				ON Order_Line.entity_id = Entertainer.id
					AND Order_Line.domain_entity = 'Entertainer'
					
			LEFT JOIN `WebShow`
				ON Order_Line.entity_id = WebShow.id
					AND Order_Line.domain_entity = 'WebShow'
					
			LEFT JOIN `Library`
				ON Order_Line.entity_id = Library.id
					AND Order_Line.domain_entity = 'Library'
				
			WHERE Order.payment_amount > 0
				AND (Entertainer.id = /%ENTERTAINER_ID%/
					OR WebShow.Entertainer_id = /%ENTERTAINER_ID%/
					OR Library.Member_id = /%MEMBER_ID%/)
				/%RANGE%/
					
			ORDER BY Order.order_time DESC
			
__END__;
		}
	}
	
	public function getCSV()
	{
		
	}
	
	public function getHTML()
	{
		$this->setSubsummaries(array('date'));
		return $this->runReportAsHTML();
	}
	
	public function getName() {return 'Daily Sales';}
}