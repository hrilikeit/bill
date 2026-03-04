<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#FFFFFF">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
<!--<script>-->
<!--    // This is the "Offline page" service worker-->
<!---->
<!--    importScripts('https://storage.googleapis.com/workbox-cdn/releases/5.1.2/workbox-sw.js');-->
<!---->
<!--    const CACHE = "pwabuilder-page";-->
<!---->
<!--    // TODO: replace the following with the correct offline fallback page i.e.: const offlineFallbackPage = "offline.html";-->
<!--    const offlineFallbackPage = "/home_new_index.html";-->
<!---->
<!--    self.addEventListener("message", (event) => {-->
<!--        if (event.data && event.data.type === "SKIP_WAITING") {-->
<!--            self.skipWaiting();-->
<!--        }-->
<!--    });-->
<!---->
<!--    self.addEventListener('install', async (event) => {-->
<!--        event.waitUntil(-->
<!--            caches.open(CACHE)-->
<!--                .then((cache) => cache.add(offlineFallbackPage))-->
<!--        );-->
<!--    });-->
<!---->
<!--    if (workbox.navigationPreload.isSupported()) {-->
<!--        workbox.navigationPreload.enable();-->
<!--    }-->
<!---->
<!--    self.addEventListener('fetch', (event) => {-->
<!--        if (event.request.mode === 'navigate') {-->
<!--            event.respondWith((async () => {-->
<!--                try {-->
<!--                    const preloadResp = await event.preloadResponse;-->
<!---->
<!--                    if (preloadResp) {-->
<!--                        return preloadResp;-->
<!--                    }-->
<!---->
<!--                    const networkResp = await fetch(event.request);-->
<!--                    return networkResp;-->
<!--                } catch (error) {-->
<!---->
<!--                    const cache = await caches.open(CACHE);-->
<!--                    const cachedResp = await cache.match(offlineFallbackPage);-->
<!--                    return cachedResp;-->
<!--                }-->
<!--            })());-->
<!--        }-->
<!--    });-->
<!--</script>-->
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
            .container_footer {
                margin-top: 200px;
        }
    
    }
 
    </style>
    <script>
        if (typeof navigator.serviceWorker !== 'undefined') {
            navigator.serviceWorker.register('sw.js')
        }
    </script>
</head>
<body>
<section id="container">
    <div class="container-fluid">
        <div class="row pt-3 pb-4">
            <div class="col-12 col-lg-7">
                <div class="models">
                    <div class="">
                        <img src="site_img/logo.png" alt="Logo" class="img-fluid models_img">
                    </div>
                    <div class="mt-5">
                        <img src="site_img/new_main_logo.png" alt="Logo Text" class="img-fluid models_text_img">
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
                            <a class="twitter_btn" href="#">
                                <i class="fa-brands fa-twitter me-2 text-light"></i>
                                SIGN IN WITH TWITTER
                            </a>
                            <?php
                            $clientID = '61534181333-4q43dngr7bj5su6knk7ck030p7uotn26.apps.googleusercontent.com';
                            $clientSecret = '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';
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
    <div content="">
        <button id="install_button">Install</button>
    </div>
<!-- Start JavaScript -->
<script>
    let deferredPrompt; // Allows to show the install prompt
    const installButton = document.getElementById("install_button");
    installButton.style.display = 'none';
    window.addEventListener("beforeinstallprompt", e => {
        console.log("beforeinstallprompt fired");
        // Prevent Chrome 76 and earlier from automatically showing a prompt
        e.preventDefault();
        // Stash the event so it can be triggered later.
        deferredPrompt = e;
        // Show the install button
        installButton.style.display = 'block';
        installButton.style.zIndex = '999';
        installButton.addEventListener("touchend", installApp);
    });

    function installApp() {
        // Show the prompt
        deferredPrompt.prompt();
        installButton.disabled = true;

        // Wait for the user to respond to the prompt
        deferredPrompt.userChoice.then(choiceResult => {
            if (choiceResult.outcome === "accepted") {
                console.log("PWA setup accepted");
                installButton.hidden = true;
            } else {
                console.log("PWA setup rejected");
            }
            installButton.disabled = false;
            deferredPrompt = null;
        });
    }
</script>
<script defer src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<!-- End JavaScript -->
</body>
</html>
