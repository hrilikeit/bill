<?php

class InitialEntertainers extends StaysailReport
{
    public function getName() {
        return 'First Entertainer Subscription per Fan';
    }

    public function __construct()
    {
        parent::__construct();

        $this->sql = <<<__END__
        
SELECT CONCAT(Fan_Member.last_name, ', ', Fan_Member.first_name) AS `Fan Name`, 
       CONCAT(Ent_Member.last_name, ', ', Ent_Member.first_name) AS `Entertainer Name`,
       Entertainer.name AS `Stage Name`,
       Fan_Subscription.active_time AS `Subscription Active`,
       IF(Fan_Member.active_time = 0, 'No', 'Yes') AS `Paid`,
       IF(Fan_Member.expire_time > NOW(), 'Active', IF(Fan_Member.expire_time = 0, '--', CONCAT('Expired ', Fan_Member.expire_time))) AS `Status`

FROM Fan

INNER JOIN Member AS `Fan_Member`
    ON Fan_Member.id = Fan.Member_id

INNER JOIN Fan_Subscription
    ON Fan_Subscription.Fan_id = Fan.id

INNER JOIN Entertainer
    ON Entertainer.id = Fan_Subscription.Entertainer_id

INNER JOIN Member AS `Ent_Member`
    ON Ent_Member.id = Entertainer.Member_id

WHERE Fan_Member.is_deleted = 0

GROUP BY Fan_Member.id, Ent_Member.id, Entertainer.id, Fan_Subscription.id

ORDER BY Fan_Member.last_name, Fan_Subscription.id
        
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
