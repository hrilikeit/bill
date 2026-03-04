<?php

//if ((!isset($_SERVER['HTTPS']) or $_SERVER['HTTPS'] == '') and $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
//	$redirect = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
//    header("Location: $redirect");
//    exit;
//}

session_start();
require '../private/config.php';
require '../private/staysail/Staysail.php';
require '../private/interfaces/interface.AccountType.php';
require '../private/interfaces/interface.AccountPublic.php';
require '../private/tools/Maps.php';
require '../private/tools/Icon.php';
require '../private/views/ActionsView.php';
require '../private/views/BannerAdsView.php';
require '../private/views/HeaderView.php';
require '../private/views/FooterView.php';

// Database info only needs to be passed the first time; StaysailIO::engage() will
// return a singleton instance henceforth.
$framework = StaysailIO::engage(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$module = StaysailIO::publicModule('Administrator');

$admin_id = StaysailIO::session('Admin.id');
if ($admin_id) {
	$body = $module->getHTML();
} else {
	$body = $module->login();
}

$html = <<<__END__
<!DOCTYPE html>
<html>

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="/css/reset.css" />
<link rel="stylesheet" href="/css/19_col.css" />
<link rel="stylesheet" href="/css/lsf.css" />
<link rel="stylesheet" href="/css/lsf_form.css" />
<link rel="stylesheet" href="/css/lsf_cal.css" />
<link rel="stylesheet" href="/css/lsf_admin.css" />
<link rel="stylesheet" href="/css/_main_admin.css" />

<script type="text/javascript" src="/js/jquery-1.7.2.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

<!--<script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>-->
<script src="/ckeditor/ckeditor.js"></script>


<!--<script type="text/javascript" src="/js/tiny_mce/jquery.tinymce.js"></script>-->
<script type="text/javascript" src="/js/uniValidate.js"></script>
<script type="text/javascript" src="/js/interface.js"></script>
<script type="text/javascript" src="/js/admin_interface.js"></script>

<script>
// Initializes all textareas with the tinymce class
// $(document).ready(function() {
//    $('textarea.richtext').tinymce({
//       script_url : '/js/tiny_mce/tiny_mce.js',
//       theme : "advanced",
//       theme_advanced_toolbar_location : "top"
//    });
// });

</script>

</head>
<body>
<div">

{$body}

</div>
</body>
</html>
__END__;

print $html;
