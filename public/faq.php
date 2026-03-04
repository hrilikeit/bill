<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <!-- Start CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <!--    <link rel="stylesheet" href="./assets/css/main.css">-->
    <link rel="stylesheet" href="/css/htmlStyle.css">
    <!-- End CSS -->
    <link rel="icon" type="image/x-icon" href="site_img/logo.png">
    <title>FAQ</title>
</head>
<body>
<nav class="container">
    <div class="_nav">
        <a href="/">
            <img src="site_img/logo.png" width="30" alt="">
        </a>
    </div>
</nav>
<div class="container">
    <div class="creators">
        <div class="creators_box">
            <button class="creators_btn" id="creators_btn" onclick="show_creators('creators', 'up')">CREATORS</button>
            <button class="creators_btn hide" id="users_btn-show" onclick="show_creators('users', 'up')">USERS</button>
            <p class="creators_title">
                <a href="#" onclick="changeText('account_approval')" style="color: black">Account Approval/Application Requirements</a>
            </p>
            <ul>
                <li class="creators_link_item">
                    <a href="#" onclick="changeText('how_do_i_start')" class="creators_link">How do | start earning on YourFansLive?</a>
                </li>
                <li class="creators_link_item">
                    <a href="#" onclick="changeText('payout_percentages')" class="creators_link">Payout Percentages and Pricing</a>
                </li>
                <li class="creators_link_item">
                    <a href="#" class="creators_link" onclick="changeText('how_much')">How much do | recieve?</a>
                </li>
                <li class="creators_link_item">
                    <a href="#" class="creators_link" onclick="changeText('paid_account')">Paid Account vs Free Account</a>
                </li>
                <li class="creators_link_item">
                    <a href="#" class="creators_link" onclick="changeText('subscription')">Subscription Price and Posts</a>
                </li>
                <li class="creators_link_item">
                    <a href="#" class="creators_link" onclick="changeText('who_can_see')">Who can see my YourFansLive page?</a>
                </li>
                <li class="creators_link_item">
                    <a href="#" class="creators_link" onclick="changeText('creators_featured')">Creators Featured in Content</a>
                </li>
                <li class="creators_link_item">
                    <a href="#" class="creators_link" onclick="changeText('tax_forms')">Tax Forms</a>
                </li>
                <li class="creators_link_item">
                    <a href="#" class="creators_link" onclick="changeText('what_can_post')">What can | Post?</a>
                </li>
            </ul>
            <button class="creators_btn creators_btn_white hide" id="creators_btn-show" onclick="show_creators('creators', 'down')">CREATORS</button>
            <button class="creators_btn creators_btn_white" id="users_btn" onclick="show_creators('users', 'down')">USERS</button>
        </div>
        <div id="creators" class="creators_box creators_box_two">
            <p>
                <b>
                    In order to be approved as a Creator, two photos must be submitted:
                </b>
            </p>
            <p class="creators_text">
                1. A close-up photo of your government-issued ID front and back
                (passport, driver's license, ID card);
            </p>
            <p class="creators_text">
                2. A photo of you (selfie) holding the same photo ID. Make sure your
                face is fully visible and the ID is fully visible and legible.
            </p>
            <p>
                <b>
                    Please note the following requirements which must be met for ANY
                    uploaded image:
                </b>
            </p>
            <p class="creators_text">
                1. Images may not be edited, cropped or re-sized;
            </p>
            <p class="creators_text">
                2. All images must be in color;
            </p>
            <p class="creators_text">
                3. Submitted documents must be a photo or a scanned copy.
                Electronic documents or a photo of a photo will be rejected;
            </p>
            <p class="creators_text">
                4. All 4 corners of the document must be visible;
            </p>
            <p class="creators_text">
                5. The entire government issued, non-expired ID must be shown. No
                parts of the document may be redacted, covered, cut, or censored;
            </p>
            <p class="creators_text">
                6. Files must be PNG or .JPG format and under 7MB in size;
            </p>
            <p class="creators_text">
                7. Provided document shall not expire for at least 30 days from the
                date of submission.
            </p>
            <p class="creators_text">
                <b>
                    Creators will be required to pass a third-party ID verification.
                </b>
            </p>
        </div>
        <div id="users" class="d_none creators_box creators_box_two">
            <h1>USERS</h1>
            <p>
                <b>
                    In order to be approved as a Creator, two photos must be submitted:
                </b>
            </p>
            <p class="creators_text">
                1. A close-up photo of your government-issued ID front and back
                (passport, driver's license, ID card);
            </p>
            <p class="creators_text">
                2. A photo of you (selfie) holding the same photo ID. Make sure your
                face is fully visible and the ID is fully visible and legible.
            </p>
            <p>
                <b>
                    Please note the following requirements which must be met for ANY
                    uploaded image:
                </b>
            </p>
            <p class="creators_text">
                1. Images may not be edited, cropped or resized;
            </p>
            <p class="creators_text">
                2. Allimages must be in color;
            </p>
            <p class="creators_text">
                3. Submitted documents must be a photo or a scanned copy.
                Electronic documents or a photo of a photo will be rejected;
            </p>
            <p class="creators_text">
                4. All 4 corners of the document must be visible;
            </p>
            <p class="creators_text">
                5. The entire government issued, non-expired ID must be shown. No
                parts of the document may be redacted, covered, cut, or censored;
            </p>
            <p class="creators_text">
                6. Files must be PNG or .JPG format and under 7MB in size;
            </p>
            <p class="creators_text">
                7. Provided document shall not expire for at least 30 days from the
                date of submission.
            </p>
            <p class="creators_text">
                <b>
                    Creators will be required to pass a third-party ID verification.
                </b>
            </p>
        </div>
    </div>
</div>
<?php
include('footer.php')
?>
<!-- Start JavaScript -->
<script src="js/htmlJs.js"></script>
<!-- End JavaScript -->
</body>
</html>
