<?php

class NewEntertainers extends StaysailReport
{
    public function getName() {
        return 'New Entertainers';
    }

    public function __construct()
    {
        parent::__construct();

        $this->sql = <<<__END__
SELECT DISTINCT CONCAT(Member.last_name, ', ', Member.first_name) AS `Member`,
                Member.email AS `email`,
                Member.name AS `Screen Name`,
                Member.online_time AS `Last Online`,
                Member.created_at AS `Created`
FROM Member
INNER JOIN Entertainer
    ON Entertainer.Member_id = Member.id
AND Member.is_deleted = 0
ORDER BY Member.created_at DESC
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
