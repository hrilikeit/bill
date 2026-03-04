<?php

// Find newly-expired peeps and renew them

date_default_timezone_set('America/New_York');

require __DIR__.'/../staysail/Staysail.php';
require __DIR__.'/../config.php';

$framework = StaysailIO::engage(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$filters = array(new Filter(Filter::Match, array('email_verified' => 0)));
$members = $framework->getSubset('Member', $filters);
foreach ($members as $member){
    $createDate = $member->created_at;
    $lastDate = date('d/m/Y',strtotime('+30 days',strtotime(str_replace('/', '-', $createDate))));
    $today = strtotime("now");
    if ($today - strtotime($lastDate) > 0){
        $from = "Reply-to:emailverification@yourfanslive.com\nFrom:emailverification@yourfanslive.com";
        mail('emailverification@yourfanslive.com', 'No Email Verify', "This account $member->email did not pass email verification", $from);
    }
}

//$member->email_verified = 1;
//$member->save();