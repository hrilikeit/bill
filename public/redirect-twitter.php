<?php
require_once  '../twitter-login/config.php';
require_once  '../twitter-login/logout.php';


try {
    $adapter->authenticate();
    $userProfile = $adapter->getUserProfile();
    $accessToken = $adapter->getAccessToken();

    session_start();
    $_SESSION["google_user"] =[];

    if ($userProfile) {

        $name = $userProfile->firstName;
        $email = $userProfile->email;
        $twitter_account_id = $userProfile->identifier;

        $_SESSION["twitter_user"]=[
            'name' => $name,
            'email' => $email,
            'id' => $twitter_account_id,
        ];

        header("Location:/index.php?mode=Login&job=join-twitter");

    }else{
        $_SESSION["twitter_user"]= [];
        $_SESSION["google_user"]= [];
        header("Location:/index.php?mode=Login&job=join");
    }

} catch(Exception $e) {
    // var_dump($e->getMessage());
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twitter Authentication</title>
</head>
<body>
<script>
    console.log("Script loaded and running...");

    const checkUrlAndReload = () => {
        console.log("Checking URL...");
        if (window.location.href === "https://yourfanslive.com/redirect-twitter.php") {
            console.log("URL matches the expected URL. Stopping checks.");
            location.reload();
        } else {
            console.log("URL does not match the expected URL. Reloading...");
            clearInterval(interval);

        }
    }
    const interval = setInterval(checkUrlAndReload, 1000);
</script>
</body>
</html>
