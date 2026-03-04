<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
<!--    <link rel="preconnect" href="https://fonts.googleapis.com" />-->
<!--    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />-->
<!--    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet"/>-->



    <!-- Start CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <!--    <link rel="stylesheet" href="./assets/css/main.css">-->
    <link rel="stylesheet" href="/css/htmlStyle.css">

    <link rel="icon" type="image/x-icon" href="site_img/logo.png">
    <title>Document</title>
</head>
<body>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Montserrat", sans-serif;
    }

    .pageContainer {
        width: 100%;
        max-width: 1600px;
        margin: 0 auto;
        padding: 0 20px;
    }

    section#section-one {
        width: 100%;
        padding: 130px 0;
        display: flex;
        align-items: center;
        background: url("site_img/icons/line2.svg");
        background-repeat: no-repeat;
        background-position: right;
    }

    .sectionOneContainer {
        width: 100%;
        max-width: 70%;
        display: flex;
        flex-direction: column;
        gap: 50px;
    }

    .sectionOne-title {
        text-align: center;
        color: #b102a6;
        font-size: 80px;
        text-transform: uppercase;
        letter-spacing: 4px;
        margin-bottom: 36px;
    }

    .sectionOneItem-button {
        width: 100%;
    }

    .sectionOneItem-button button {
        background: #b102a6;
        border: none;
        padding: 10px;
        text-align: center;
        font-size: 26px;
        color: white;
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 12px;
        letter-spacing: 0.5px;
    }

    .sectionOneItem-text p {
        font-size: 24px;
        font-weight: 500;
    }

    section#section-two {
        width: 100%;
        display: flex;
        align-items: center;
        background: linear-gradient(182.25deg, #9817c4 0%, #291761 97.49%);
        padding: 60px 0;
    }

    .sectionTwoContainer h1 {
        text-align: center;
        color: white;
        font-size: 80px;
        text-transform: uppercase;
        letter-spacing: 4px;
    }

    section#section-three {
        width: 100%;
        margin: 40px 0;
    }

    .sectionThree-title {
        text-align: center;
        font-size: 52px;
        text-transform: uppercase;
    }

    .sectionThreeBox {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 32px;
        text-align: center;
        margin: 40px 0;
    }

    .sectionThreeBox-item {
        padding: 20px 20px 40px;
        border: 4px solid;
    }

    .item-border-one {
        border-image: linear-gradient(to right, #b115a5, #d27acc) 1;
    }

    .item-border-two {
        border-image: linear-gradient(to right, #9e1fd2, #b451dc) 1;
    }

    .item-border-three {
        border-image: linear-gradient(to right, #532db0, #6c4abc) 1;
    }

    .item-border-four {
        border-image: linear-gradient(to right, #2545b5, #5168bb) 1;
    }

    .item-border-five {
        border-image: linear-gradient(to right, #2c157a, #5e4f9a) 1;
    }

    .sectionThreeBoxItem-number {
        font-size: 72px;
    }

    .number-one {
        background: -webkit-linear-gradient(#b115a5, #d27acc);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .number-two {
        background: -webkit-linear-gradient(#9e1fd2, #b451dc);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .number-three {
        background: -webkit-linear-gradient(#532db0, #6c4abc);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .number-four {
        background: -webkit-linear-gradient(#2545b5, #5168bb);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .number-five {
        background: -webkit-linear-gradient(#2c157a, #5e4f9a);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .sectionThree-button {
        text-align: center;
    }

    .sectionThree-button a {
        background: linear-gradient(182.25deg, #9817c4 0%, #7d72a0 97.49%);
        border: none;
        padding: 14px 26px;
        border-radius: 24px;
        color: white;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    h2.sectionThreeBoxItem-text {
        text-transform: uppercase;
    }
    @media (max-width: 1200px) {
        .sectionOneContainer {
            max-width: 100%;
        }

        .sectionThreeBox {
            grid-template-columns: repeat(3, 1fr);
        }

        .sectionOne-title,
        .sectionTwoContainer h1 {
            font-size: 60px;
        }
    }

    @media (max-width: 768px) {
        .sectionOne-title,
        .sectionTwoContainer h1 {
            font-size: 40px;
        }

        .sectionThree-title {
            font-size: 32px;
        }

        .sectionThreeBox {
            grid-template-columns: repeat(2, 1fr);
        }

        .sectionOneItem-text p {
            font-size: 18px;
        }

        .sectionOneItem-button button {
            font-size: 20px;
        }

        .sectionThreeBoxItem-number {
            font-size: 48px;
        }
    }

    @media (max-width: 480px) {
        .sectionOneContainer {
            gap: 30px;
        }

        .sectionThreeBox {
            grid-template-columns: 1fr;
        }

        .sectionThreeBox-item {
            padding: 20px;
        }

        .sectionThree-button a {
            width: 100%;
            display: inline-block;
            text-align: center
        }
    }
</style>
<section id="section-one">
    <div class="pageContainer">
        <h1 class="sectionOne-title">Payouts</h1>
        <div class="sectionOneContainer">
            <div class="sectionOne-item">
                <div class="sectionOneItem-button">
                    <button>Payouts</button>
                </div>
                <div class="sectionOneItem-text">
                    <p>YourFansLive pays out every monday</p>
                </div>
            </div>
            <div class="sectionOne-item">
                <div class="sectionOneItem-button">
                    <button>Minimum payout threshold</button>
                </div>
                <div class="sectionOneItem-text">
                    <p>
                        Minimum Payout is $100 for weekly automated payouts. If your
                        account does not meet the minimum payout threshhold then your
                        funds will be rolled over to the following payout until you hit
                        the $100 minimum.
                    </p>
                </div>
            </div>
            <div class="sectionOne-item">
                <div class="sectionOneItem-button">
                    <button>Payout method</button>
                </div>
                <div class="sectionOneItem-text">
                    <p>
                        Payouts are done through MassPay. Be sure to set up your MassPay
                        accou and Profile to have the same email address.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="section-two">
    <div class="pageContainer">
        <div class="sectionTwoContainer">
            <h1>earnings</h1>
        </div>
    </div>
</section>

<section id="section-three">
    <div class="pageContainer">
        <div class="sectionThreeContainer">
            <h1 class="sectionThree-title">
                ALL NEW CREATORS GET 6 MONTHS AT 95% PAYOU AFTER 6 MONTHS PAYOUTS
                ARE...
            </h1>
            <div class="sectionThreeBox">
                <div class="sectionThreeBox-item item-border-one">
                    <h1 class="sectionThreeBoxItem-number number-one">85%</h1>
                    <h2 class="sectionThreeBoxItem-text">
                        FOR MONTHLY EARNINGS UP TO $5000
                    </h2>
                </div>
                <div class="sectionThreeBox-item item-border-two">
                    <h1 class="sectionThreeBoxItem-number number-two">90%</h1>
                    <h2 class="sectionThreeBoxItem-text">
                        FOR MONTHLY EARNINGS FROM $5,001 TO $10,000
                    </h2>
                </div>
                <div class="sectionThreeBox-item item-border-three">
                    <h1 class="sectionThreeBoxItem-number number-three">95%</h1>
                    <h2 class="sectionThreeBoxItem-text">
                        FOR MONTHLY EARNINGS OF $10,000+
                    </h2>
                </div>
                <div class="sectionThreeBox-item item-border-four">
                    <h1 class="sectionThreeBoxItem-number number-four">90%</h1>
                    <h2 class="sectionThreeBoxItem-text">
                        ON ALL TIPS YOU EARN ON THE SITE
                    </h2>
                </div>
                <div class="sectionThreeBox-item item-border-five">
                    <h1 class="sectionThreeBoxItem-number number-five">1%</h1>
                    <h2 class="sectionThreeBoxItem-text">
                        ON ALL CREATOR REFERRALS FOR LIFE
                    </h2>
                </div>
            </div>

            <div class="sectionThree-button">
                <a href="/">Join Us</a>
            </div>
        </div>
    </div>
</section>
<?php
    include('footer.php')
?>
</body>
</html>
