<?php

class ClubBreakdown extends StaysailReport
{

	public function __construct()
	{
		parent::__construct();
		
		if (isset($_GET["mode"],$_GET["id"]) && htmlspecialchars($_GET["mode"]) == 'Administrator' && htmlspecialchars($_GET["id"]) == 'ClubBreakdown') {
				
			$this->sql = <<<__END__
	
			SELECT DATE_FORMAT(Order.order_time, '%b %d, %X') AS `date`, Order.order_time, 
				IF (Library_Entertainer.name != '', Library_Entertainer.name, 
					IF (WebShow_Entertainer.name != '', WebShow_Entertainer.name, Entertainer.name)) AS `entertainer`,
				CONCAT(Member.last_name, ', ', Member.first_name) AS `member`, Order.order_code,
				Order.payment_amount, 
				
				IF(Entertainer.id IS NOT NULL AND Order_Line.description='Fan Tip','Fan Tip', 
					IF(Entertainer.id IS NOT NULL AND Order_Line.description !='Fan Tip', 'Subscription',
						IF(WebShow.id IS NOT NULL, 'Web Show',
							IF (Library.id IS NOT NULL, 'Image Purchase', '')))) AS `Description`
			
			FROM `Order`
			
			INNER JOIN `Order_Line`
				ON Order_Line.Order_id = Order.id
					AND Order_Line.cancel = 0
			
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
					
			LEFT JOIN `Entertainer` AS `Library_Entertainer`
				ON Library_Entertainer.Member_id = Library.Member_id	
	
			LEFT JOIN `Entertainer` AS `WebShow_Entertainer`
				ON WebShow_Entertainer.id = WebShow.Entertainer_id
					
			INNER JOIN `Entertainer_Club`
				ON (Entertainer_Club.Entertainer_id = Entertainer.id
					OR Entertainer_Club.Entertainer_id = WebShow.Entertainer_id
					OR Entertainer_Club.Entertainer_id = Library_Entertainer.id)
				
			WHERE Order.payment_amount > 0
				AND Order.cancel = 0
				
			ORDER BY `Description`
__END__;
		}else{

			$this->sql = <<<__END__
	
	SELECT DATE_FORMAT(Order.order_time, '%b %d, %X') AS `date`, Order.order_time, 
		IF (Library_Entertainer.name != '', Library_Entertainer.name, 
			IF (WebShow_Entertainer.name != '', WebShow_Entertainer.name, Entertainer.name)) AS `entertainer`,
		CONCAT(Member.last_name, ', ', Member.first_name) AS `member`, Order.order_code,
		Order.payment_amount, 
		
		IF(Entertainer.id IS NOT NULL AND Order_Line.description='Fan Tip','Fan Tip', 
			IF(Entertainer.id IS NOT NULL AND Order_Line.description !='Fan Tip', 'Subscription',
				IF(WebShow.id IS NOT NULL, 'Web Show',
					IF (Library.id IS NOT NULL, 'Image Purchase', '')))) AS `Description`
	
	FROM `Order`
	
	INNER JOIN `Order_Line`
		ON Order_Line.Order_id = Order.id
			AND Order_Line.cancel = 0
	
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
			
	LEFT JOIN `Entertainer` AS `Library_Entertainer`
		ON Library_Entertainer.Member_id = Library.Member_id	

	LEFT JOIN `Entertainer` AS `WebShow_Entertainer`
		ON WebShow_Entertainer.id = WebShow.Entertainer_id
			
	INNER JOIN `Entertainer_Club`
		ON (Entertainer_Club.Entertainer_id = Entertainer.id
			OR Entertainer_Club.Entertainer_id = WebShow.Entertainer_id
			OR Entertainer_Club.Entertainer_id = Library_Entertainer.id)
		
	WHERE Order.payment_amount > 0
		AND Order.cancel = 0
		AND Entertainer_Club.Club_id = /%CLUB_ID%/
		/%RANGE%/
		
	ORDER BY `Description`
__END__;
		}

	}
	
	public function getCSV()
	{
		
	}
	
	public function getHTML()
	{
		$this->setSubsummaries(array('Description'));
		return $this->runReportAsHTML();
	}
	
	public function getName() {return 'Daily Sales';}
}