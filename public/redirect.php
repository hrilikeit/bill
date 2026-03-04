<?php

require_once '../google/vendor/autoload.php';



// init configuration
$clientID = '61534181333-4q43dngr7bj5su6knk7ck030p7uotn26.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri = 'https://yourfanslive.com/redirect.php';

// create Client Request to access Google API
$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope("email");
$client->addScope("profile");


// authenticate code from Google OAuth Flow
session_start();

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token['access_token']);

    // get profile info
    $google_oauth = new Google_Service_Oauth2($client);
    $google_account_info = $google_oauth->userinfo->get();
    $email =  $google_account_info->email;
    $name =  $google_account_info->name;
    $_SESSION["google_user"]=[
        'name' => $name,
        'email' => $email,
        'id' => $google_account_info->id,
    ];


    header("Location:/index.php?mode=Login&job=join-google");

    // now you can use this profile info to create account in your website and make user logged in.
} else {
    $_SESSION["google_user"]= [];
    header("Location:/index.php?mode=Login&job=join");
}
?>
