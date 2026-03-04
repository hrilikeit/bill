<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="apple-touch-icon" sizes="180x180" href="https://yourfanslive.com/site_img/logo-new.png">
    <link rel="icon" type="image/png" sizes="32x32" href="https://yourfanslive.com/site_img/logo.png">
    <link rel="icon" type="image/png" sizes="192x192" href="https://yourfanslive.com/site_img/logo-new.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://yourfanslive.com/site_img/logo.png">
    <link rel="manifest" href="https://yourfanslive.com/site_img/logo-new.png">
    <link rel="mask-icon" href="https://yourfanslive.com/site_img/logo-new.png">
    <link rel="shortcut icon" href="https://yourfanslive.com/site_img/logo-new.ico">
    <link rel="manifest" href="https://yourfanslive.com/manifest.json">

    <!-- Start CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <!--    <link rel="stylesheet" href="./assets/css/main.css">-->
    <link rel="stylesheet" href="/css/newStyles.css">
    <!-- End CSS -->
    <link rel="icon" type="image/x-icon" href="site_img/logo.png">
    <title>YourFansLive</title>
    <style>
        .container_footer{
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .footer a{
            color: black !important;
        }
        .footer{
            color: black !important;
        }

        .gradient{
            display: none;
        }

        @media only screen and (max-width: 990px) and (min-width: 320px) {
            /*.container_footer {*/
            /*    margin-top: 200px;*/
            /*}*/

        }
        .main_logo_index_95{
            max-width: 70%;
            max-height: 70%;
        }

        .main_logo_index_text{
        max-width: 70%;
            max-height: 70%;
        }
        #container{
            height:auto !important;
        }

    </style>
</head>
<body style="background:#fff !important;">
<section id="container">
    <div class="container-fluid">
        <div class="row pt-3 pb-4">
            <div class="col-12 col-lg-7">
                <div class="models">
                    <div>
                        <img src="site_img/lage_logo.jpg" width="400px" height="261px" alt="Your Fans Live Logo" class="img-fluid">
                    </div>
                    <div>
                        <img src="site_img/95_perce.jpg" alt="Your Fans Live Logo" width="400px" height="208px" class="img-fluid">
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div id="form">
                    <div class="">
                        <h3 class="form_title">YOURFANSLIVE</h3>
                        <form method="post" action="?mode=Login&job=authorize">
                            <input type="text" class="text-dark" placeholder="USERNAME" name="username" required>
                            <input type="password"  name="password" class="text-dark" placeholder="PASSWORD" required>

                                                        <a class="twitter_btn" href="https://yourfanslive.com/redirect-twitter.php">
                                                            <i class="fa-brands fa-twitter me-2 text-light"></i>
                                                            SIGN IN WITH TWITTER
                                                        </a>

                                                        <?php
                                                        $clientID = '61534181333-4q43dngr7bj5su6knk7ck030p7uotn26.apps.googleusercontent.com';
                                                        $clientSecret = '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';
                                                        //$redirectUri = 'https://stage.yourfanslive.com/redirect.php';
                                                        $redirectUri = 'https://yourfanslive.com/redirect.php';

                                                        $client = new Google_Client();
                                                        $client->setClientId($clientID);
                                                        $client->setClientSecret($clientSecret);
                                                        $client->setRedirectUri($redirectUri);
                                                        $client->addScope("email");
                                                        $client->addScope("profile");
                                                        ?>
                                                        <a href="<?php echo $client->createAuthUrl() ?>" class="google_btn">
                                                            <i class="fa-brands fa-google me-2 text-light"></i>
                                                            SIGN IN WITH GOOGLE
                                                        </a>
                            <input type="hidden" name="new_design" value="1">
                            <?php if(isset($loginError)):?>
                                <h1 class="text-light">Sorry...</h1>
                                <p class="text-light mb-4">The username/password combination that you entered is not valid.  Please try again.</p>
                            <?php endif ?>
                            <input type="submit"  class="btn btn-light text-dark rounded-pill" value="LOGIN">
                            <a href="?mode=Login&job=forgot_pw">Forgot your password?</a>
                        </form>
                        <p class="text-white fw-bolder">DON'T HAVE AN ACCOUNT? </p>
                        <a href="?mode=Login&job=join" class="btn btn-light text-dark rounded-pill">SIGN UP</a>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-1"></div>
        </div>
    </div>
</section>
<?php
include('footer.php')
?>
<!-- Start JavaScript -->
<script defer src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
<!--<script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>-->
<!-- End JavaScript -->
</body>
</html>
