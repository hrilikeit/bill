<?php

class DailySalesCreator extends StaysailReport
{


	public function __construct()
	{
        if (isset($_SESSION['entertainer_creator_id'])) {
            $entertainer_creator_id = $_SESSION['entertainer_creator_id'];
        } else {
            $entertainer_creator_id = 422;
        }
        
       // $entertainer_creator_id = $_SESSION['entertainer_creator_id'];
		parent::__construct();

		$this->sql = <<<__END__
		
		SELECT  
    DATE_FORMAT(Order.order_time, '%b %d, %X') AS `date`, 
    Order.order_time, 
    IF(GoalEntertainer.id IS NOT NULL, GoalEntertainer.name,
        IF(WebShowEntertainer.id IS NOT NULL, WebShowEntertainer.name,
            IF(Order_Line.description = 'Monthly Member Fee', '-',
                IF(EntertainerLibrary.id IS NOT NULL, EntertainerLibrary.name, 
                    Entertainer.name)))) AS `entertainer`,
    CONCAT(Member.last_name, ', ', Member.first_name) AS `member`, 
    Order.order_code,
    Order.payment_amount, 
    IF(Entertainer.id IS NOT NULL AND Order_Line.description='Fan Tip','Fan Tip', 
        IF(Entertainer.id IS NOT NULL AND Order_Line.description !='Fan Tip', 'Subscription',
            IF(WebShow.id IS NOT NULL, 'Web Show',
                IF (Library.id IS NOT NULL AND Order_Line.description !='Video Purchase', 'Image Purchase', 
                    Order_Line.description)))) AS `Description`
FROM `Order`
INNER JOIN `Order_Line` ON Order_Line.Order_id = Order.id
INNER JOIN `Member` ON Member.id = Order.Member_id
LEFT JOIN `Entertainer` ON Order_Line.entity_id = Entertainer.id AND Order_Line.domain_entity = 'Entertainer'
LEFT JOIN `WebShow` ON Order_Line.entity_id = WebShow.id AND Order_Line.domain_entity = 'WebShow'
LEFT JOIN `Entertainer` AS `WebShowEntertainer` ON WebShowEntertainer.id = WebShow.Entertainer_id
LEFT JOIN `Library` ON Order_Line.entity_id = Library.id AND Order_Line.domain_entity = 'Library'
LEFT JOIN `Entertainer` AS `EntertainerLibrary` ON EntertainerLibrary.Member_id = Library.Member_id
LEFT JOIN `Goal` ON Goal.id = Order.goal_id
LEFT JOIN `Entertainer` AS `GoalEntertainer` ON GoalEntertainer.id = Goal.Entertainer_id
WHERE Order.payment_amount > 0
    AND (GoalEntertainer.id = $entertainer_creator_id
         OR WebShowEntertainer.id = $entertainer_creator_id
         OR EntertainerLibrary.id = $entertainer_creator_id
         OR Entertainer.id = $entertainer_creator_id)
ORDER BY Order.order_time DESC;


		
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
	
	public function getName() {return 'Daily Sales for Creator';}

}



//SELECT  DATE_FORMAT(Order.order_time, '%b %d, %X') AS `date`, Order.order_time,
//		IF(WebShowEntertainer.id IS NOT NULL, WebShowEntertainer.name,
//				IF(Order_Line.description = 'Monthly Member Fee', '-',
//					IF (EntertainerLibrary.id IS NOT NULL, EntertainerLibrary.name,
//					Entertainer.name))) AS `entertainer`,
//
//			CONCAT(Member.last_name, ', ', Member.first_name) AS `member`, Order.order_code,
//			Order.payment_amount,
//			IF(Entertainer.id IS NOT NULL AND Order_Line.description='Fan Tip','Fan Tip',
//			  IF(Entertainer.id IS NOT NULL AND Order_Line.description !='Fan Tip', 'Subscription',
//				IF(WebShow.id IS NOT NULL, 'Web Show',
//					IF (Library.id IS NOT NULL AND Order_Line.description !='Video Purchase', 'Image Purchase',
//					Order_Line.description)))) AS `Description`
//
//		FROM `Order`
//
//		INNER JOIN `Order_Line`
//			ON Order_Line.Order_id = Order.id
//
//		INNER JOIN `Member`
//			ON Member.id = Order.Member_id
//
//		LEFT JOIN `Entertainer`
//			ON Order_Line.entity_id = Entertainer.id
//AND Order_Line.domain_entity = 'Entertainer'
//
//		LEFT JOIN `WebShow`
//			ON Order_Line.entity_id = WebShow.id
//AND Order_Line.domain_entity = 'WebShow'
//
//		LEFT JOIN `Entertainer` AS `WebShowEntertainer`
//			ON WebShowEntertainer.id = WebShow.Entertainer_id
//
//		LEFT JOIN `Library`
//			ON Order_Line.entity_id = Library.id
//AND Order_Line.domain_entity = 'Library'
//
//			LEFT JOIN `Entertainer` as `EntertainerLibrary`
//			ON EntertainerLibrary.Member_id = Library.Member_id
//
//		WHERE Order.payment_amount > 0
//
//		ORDER BY Order.order_time DESC