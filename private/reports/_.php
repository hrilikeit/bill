<?php

class NeverSignBackFans extends StaysailReport
{
    public function getName() {
        return 'Never sign back Fans';
    }

    public function __construct()
    {
        parent::__construct();

//        $this->sql = <<<__END__
//
//SELECT CONCAT(Member.last_name, ', ', Member.first_name) AS `Full Name`,
//    Fan.`name` AS `Screen Name`,
//    Member.active_time AS `Active Since`,
//    Member.expire_time AS `Expiration`,
//    Member.online_time AS `Last Online`,
//    Member.email AS `Email Address`,
//    Member.phone AS `Phone Number`,
//    IF(COUNT(`Order_Line`.id) < 1, '30 Day Code', 'Paid Member') AS `Type`
//FROM `Fan`
//
//INNER JOIN `Member`
//    ON Member.id = Fan.Member_id
//
//LEFT JOIN `Order`
//    ON `Order`.Member_id = Member.id
//
//LEFT JOIN `Order_Line`
//    ON Order_Line.Order_id = `Order`.id
//        AND Order_Line.description = 'Monthly Member Fee'
//
//WHERE Member.expire_time > NOW()
//
//GROUP BY Member.id, Member.last_name, Member.first_name, Fan.`name`, Member.active_time, Member.expire_time, Member.online_time, Member.email, Member.phone
//ORDER BY Member.last_name, Member.first_name
//
//__END__;


        $this->sql = <<<__END__
SELECT DISTINCT CONCAT(Member.last_name, ', ', Member.first_name) AS `Member`,
                Member.email AS `email`,
                Member.name AS `Screen Name`,
                Member.online_time AS `Last Online`,
                Member.created_at AS `Created`
FROM Member
INNER JOIN Fan
    ON Fan.Member_id = Member.id
AND Member.is_deleted = 0
AND DATE(Member.created_at) = DATE(Member.last_login)
ORDER BY Member.created_at DESC
__END__;

    }

    public function getCSV()
    {
        // Реализация для получения CSV
    }

    public function getHTML()
    {
        return $this->runReportAsHTML();
    }
}
