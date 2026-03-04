<?php

define('AGE_OF_MAJORITY', 18); // In years

if ((!isset($_SERVER['HTTPS']) or $_SERVER['HTTPS'] == '') and $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
	$redirect = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    header("Location: $redirect");
    exit;
}

session_start();

require '../videotool/autoloader.php';
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
require '../private/tools/SMSSender.php';

// Database info only needs to be passed the first time; StaysailIO::engage() will
// return a singleton instance henceforth.
$framework = StaysailIO::engage(DB_HOST, DB_USER, DB_PASS, DB_NAME);


if (StaysailIO::getInt('entertainer_id')) {
	StaysailIO::setSession('Entertainer.id', StaysailIO::getInt('entertainer_id'));
}

$mode = StaysailIO::get('mode');
$page_type = '';
if (StaysailIO::session('Member.id') || $mode=='Api'|| $mode=='PublicLive') {
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
<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
<script type="text/javascript" src="/js/jquery-1.7.2.js"></script>
<!--<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>-->
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.6/jquery-ui.min.js"></script>
<script type="text/javascript" src="ppgallery/js/ppgallery.js"></script>
<link href="https://vjs.zencdn.net/7.8.4/video-js.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.3/dist/js/splide.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.3/dist/css/splide.min.css">
<script type="text/javascript" src="/js/_main.js"></script>
<script type="text/javascript" src="/js/_mobile.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	$('#gallery').ppGallery();
});
</script>
__END__;
} else {
	$gallery = <<<__END__
<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
<script type="text/javascript" src="/js/jquery-1.7.2.js"></script>
<script type="text/javascript" src="/js/tiny_mce/jquery.tinymce.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.3/dist/js/splide.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.3/dist/css/splide.min.css">
<script type="text/javascript" src="/js/_main.js"></script>
<script type="text/javascript" src="/js/_mobile.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- or link to the CDN -->

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

if($mode == 'EntertainerGallery' && StaysailIO::get('job') == 'add'){
	$pic_price_js = <<<__END__
	<style> #field_prices { display:none; }</style>
	<script type="text/javascript">
		jQuery(document).ready(function(){
		  jQuery("select[name='placement']").change(function(){
		    if(jQuery(this).val() == 'sale'){
		      jQuery("#field_prices").show();
		    }else{
		      jQuery("#field_prices").hide();
		    }
		  })
		})
	</script>
__END__;
}else{
	$pic_price_js = '';
}

if($mode == 'EntertainerGallery' && StaysailIO::get('job') == 'add_video'){
	$vid_price_js = <<<__END__
	<style> #field_prices { display:none; }</style>
	<script type="text/javascript">
		jQuery(document).ready(function(){
		  jQuery("select[name='placement']").change(function(){
		    if(jQuery(this).val() == 'sale'){
		      jQuery("#field_prices").show();
		    }else{
		      jQuery("#field_prices").hide();
		    }
		  })
		})
	</script>
__END__;
}else{
	$vid_price_js = '';
}

if($mode == 'Login' && (StaysailIO::get('job') == 'new_member' || StaysailIO::get('job') == 'join') ){
	$new_js = <<<__END__
	<script type="text/javascript">
		$(".middle-container").css("background","none");
	</script>
__END__;
}else{
	$new_js = '';
}

if($mode == 'Login' && StaysailIO::get('job') == 'join' && StaysailIO::get('e') == ''){
	$ajax_js = <<<__END__
	<script type="text/javascript" src="/js/stage_name_ajax.js"></script>
__END__;
}else{
	$ajax_js = '';
}

$entertainer = StaysailIO::get('entertainer_id');

if ($mode == 'EntertainerProfile' and $entertainer != '') {
	$js = <<<__END__
	<script type="text/javascript" src="/js/custom.js"></script>
__END__;
}else{
	$js = '';
}

if ($mode == 'Login') {
	$page_type = 'gradient';
}

if ($mode == 'FanHome' && StaysailIO::get('job') == 'all_models') {
    $page_type = 'all_models';
}

if ($module != null) {
    $body = $module->getHTML();
} else {
    session_destroy();
    header("Location:/index.php");
}

$html = <<<__END__
<!DOCTYPE html>
<html>

<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<title>Yourfanslive</title>
<link rel="icon" type="image/x-icon" href="/site_img/logo-new.png">
<link rel="stylesheet" href="/css/reset.css" />
<link rel="stylesheet" href="/css/19_col.css" />
<link rel="stylesheet" href="/css/lsf.css" />
<link rel="stylesheet" href="/css/lsf_form.css" />
<link rel="stylesheet" href="/css/lsf_cal.css" />
<link rel="stylesheet" href="/css/lsf_table.css" />
<link rel="stylesheet" href="/css/calendar.css" />
<link rel="stylesheet" href="/css/notification.css" />
<link rel="stylesheet" href="/css/_main.css" />
<link rel="stylesheet" href="/css/_mobile.css" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
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
<img id="loader" src="site_img/icons/loading.gif" />
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
{$new_js}
{$js}
{$pic_price_js}
{$vid_price_js}
{$ajax_js}
<script type="text/javascript" src="/js/show.js"></script>
</html>
__END__;

print $html;
