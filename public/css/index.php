<?php

define('AGE_OF_MAJORITY', 18); // In years

if ((!isset($_SERVER['HTTPS']) or $_SERVER['HTTPS'] == '') and $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
	$redirect = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    header("Location: $redirect");
}

session_start();
require '../private/staysail/Staysail.php';

require '../private/config.php';
require '../private/interfaces/class.LSFMetadataEntity.php';
require '../private/interfaces/interface.AccountType.php';
require '../private/interfaces/interface.AccountPublic.php';
require '../private/tools/Maps.php';
require '../private/tools/Icon.php';
require '../private/tools/TripleDES.php';
require '../private/views/ActionsView.php';
require '../private/views/BannerAdsView.php';
require '../private/views/HeaderView.php';
require '../private/views/FooterView.php';

// Database info only needs to be passed the first time; StaysailIO::engage() will
// return a singleton instance henceforth.
$framework = StaysailIO::engage(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (StaysailIO::getInt('entertainer_id')) {
	StaysailIO::setSession('Entertainer.id', StaysailIO::getInt('entertainer_id'));
}

$mode = StaysailIO::get('mode');
$page_type = '';
if (StaysailIO::session('Member.id')) {
	if (!$mode) {
		$Member = new Member(StaysailIO::session('Member.id'));
		$type = $Member->getAccountType();
		$mode = "{$type}Home";
	}

	// Logged in as someone
	$module = StaysailIO::publicModule($mode);

} else {
	// Not yet logged in: present login screen
	$module = StaysailIO::publicModule('Login');
	$page_type = 'gradient';
}

if ($mode == 'WebShowModule') {
	$no_banner = <<<__END__
	<style>
	.container_19 .grid_19 + .clear + .grid_19 {
    background-image: none;
    </style>
__END__;
} else {
	$no_banner = '';
}

if ($mode == 'EntertainerGallery' and !StaysailIO::get('job')) {
	$gallery = <<<__END__
<link href="ppgallery/css/ppgallery.css" rel="stylesheet" type="text/css" />
<link href="ppgallery/css/dark-hive/jquery-ui-1.8.6.custom.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.6/jquery-ui.min.js"></script>
<script type="text/javascript" src="ppgallery/js/ppgallery.js"></script> 
<script type="text/javascript">
$(document).ready(function() {
	$('#gallery').ppGallery();
});
</script>
__END__;
} else {
	$gallery = <<<__END__
<script type="text/javascript" src="/js/jquery-1.7.2.js"></script>
<script type="text/javascript" src="/js/tiny_mce/jquery.tinymce.js"></script>
<script>
// Initializes all textareas with the tinymce class
$(document).ready(function() {
   $('textarea.richtext').tinymce({
      script_url : '/js/tiny_mce/tiny_mce.js',
      theme : "advanced",
      theme_advanced_toolbar_location : "top"
   });
});

</script>
__END__;
}

if ($mode == 'Login') {
	$page_type = 'gradient';
}

$body = $module->getHTML();

$html = <<<__END__
<!DOCTYPE html>
<html>

<head>

<link rel="stylesheet" href="/css/reset.css" />
<link rel="stylesheet" href="/css/19_col.css" />
<link rel="stylesheet" href="/css/lsf.css" />
<link rel="stylesheet" href="/css/lsf_form.css" />
<link rel="stylesheet" href="/css/lsf_cal.css" />
<link rel="stylesheet" href="/css/lsf_table.css" />
<link rel="stylesheet" href="/css/calendar.css" />
<link rel="stylesheet" href="/css/notification.css" />

<script type="text/javascript" src="/js/uniValidate.js"></script>
<script type="text/javascript" src="/js/interface.js"></script>
<script type="text/javascript" src="/jwplayer/jwplayer.js"></script>
<script type="text/javascript" src="/js/calendar.js"></script>
<script type="text/javascript" src="/js/NetroMedia.js"></script>

<script language="javascript">
window.onload = function()
{
	monitorNotifications();
    if (el('squarifier')) {
    	el('squarifier').onmousedown = startDrag;
    }	
}
</script>

{$gallery}

{$no_banner}

</head>
<body>
<div class="{$page_type}">

{$body}

</div>

<div id="player_container">
<a href="#" onclick="closeTraining()">Close</a>
<div id="playerwindow"></div>
</div>

<div id="notification_window">
<span class="closer"><a href="#" onclick="el('notification_window').style.display='none';return false">[X]</a><span>
<div id="notification_content"></div>
</div>

<div id="popup" style="display:none">
<div id="popup_results">
</div>
</div>

</body>
</html>
__END__;

print $html;