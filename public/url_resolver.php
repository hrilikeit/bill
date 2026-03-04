<?php
session_start();
require '../private/config.php';
require '../private/staysail/Staysail.php';
require '../private/interfaces/interface.AccountType.php';
require '../private/interfaces/interface.AccountPublic.php';
// Database info only needs to be passed the first time; StaysailIO::engage() will
// return a singleton instance henceforth.
$framework = StaysailIO::engage(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$account_number = StaysailIO::get('c');
$stage_name = StaysailIO::get('e');

$new_entertainer_id = $new_club_id = null;
/*$clubs = $framework->getSubset('Club', new Filter(Filter::Match, array('is_deleted' => 0, 'account_number' => $account_number)));
if (sizeof($clubs)) {
	$Club = $clubs[0];
	$new_club_id = $Club->id;
	
	// Find the entertainer for this club
	$Entertainer = $Club->getEntertainerWithFanURL($stage_name);
	if ($Entertainer) {
		$new_entertainer_id = $Entertainer->id;
		$new_club_id = $Club->id;
	}
}*/
$fp = fopen('url_resolver.txt','w+');
fwrite($fp, $stage_name);
if($stage_name != 'admin'){
$entertainers = $framework->getSubset('Entertainer', new Filter(Filter::Match, array('is_deleted' => 0, 'fan_url' => $stage_name)));
if(sizeof($entertainers)){
	if(count($entertainers) > 1){
		StaysailIO::setSession('stage_name',$stage_name);
	}else{
		$entertainer = $entertainers[0];
		$new_entertainer_id = $entertainer->id;

	}
}
StaysailIO::setSession('inviter_entertainer_id', $new_entertainer_id);
//StaysailIO::setSession('inviter_club_id', $new_club_id);

if ($stage_name and !$new_entertainer_id) {
	header("Location:/index.php?mode=Login&job=find");
	exit;
}

//header("Location:/index.php");
header("Location:/index.php?mode=Login&job=join");
}else{
	header("Location:/admin/index.php");
}
