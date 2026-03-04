<?php
session_start();
require '../../private/config.php';
require '../../private/staysail/Staysail.php';
require '../../private/interfaces/interface.AccountType.php';
require '../../private/interfaces/interface.AccountPublic.php';
// Database info only needs to be passed the first time; StaysailIO::engage() will
// return a singleton instance henceforth.
$framework = StaysailIO::engage(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$email = StaysailIO::get('email');
$members = $framework->getSubset('Member', new Filter(Filter::Match, array('email' => $email)));
foreach ($members as $Member)
{
	$Member->setEmailPrefs(Member_Email_Prefs::Optout);
}
?>
<html>
<head>
<title>Email Optout</title>
</head>

<h1>Thank you</h1>

<p>Your preferences have been updated</p>

</html>