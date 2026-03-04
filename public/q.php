<?php
session_start();
require '../private/config.php';
require '../private/staysail/Staysail.php';
require '../private/interfaces/interface.AccountType.php';
require '../private/interfaces/interface.AccountPublic.php';
// Database info only needs to be passed the first time; StaysailIO::engage() will
// return a singleton instance henceforth.
$framework = StaysailIO::engage(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$query = StaysailIO::get('q');

// Get list of entertainers whose stage names match the query
StaysailIO::cleanse($query);
if (!$query) {exit;}

$filters = array(new Filter(Filter::Where, "name LIKE '%{$query}%'"),
				 new Filter(Filter::Match, array('is_deleted' => 0)));
$entertainers = $framework->getSubset('Entertainer', $filters);

$html = '';
foreach ($entertainers as $Entertainer)
{
	$html .= $Entertainer->getSearchLink();
}
print $html;