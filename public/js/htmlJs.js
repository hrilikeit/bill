function show_creators(type, position) {
    if (type == 'creators') {
        var creators = document.getElementById("creators");
        var users = document.getElementById("users");
        creators.classList.toggle("d_none");
        users.classList.toggle("d_none");
        if (position == 'up') {
            var us_btn = document.getElementById("users_btn-show");
            us_btn.style.display = 'block'
            var cr_btn = document.getElementById("creators_btn");
            cr_btn.style.display = 'none'
            var cre_btn = document.getElementById('creators_btn-show');
            cre_btn.style.display = 'block';
            var use_btn = document.getElementById('users_btn');
            use_btn.style.display = 'none';
        } else {
            var cre_btn = document.getElementById('creators_btn-show');
            cre_btn.style.display = 'none';
            var use_btn = document.getElementById('users_btn');
            use_btn.style.display = 'block';
            var up_c_btn = document.getElementById('creators_btn');
            up_c_btn.style.display = 'block'
            var up_u_btn = document.getElementById('users_btn-show');
            up_u_btn.style.display = 'none'
        }
    } else {
        var creators = document.getElementById("creators");
        var users = document.getElementById("users");
        creators.classList.toggle("d_none");
        users.classList.toggle("d_none");
        if (position == 'down') {
            var u_btn = document.getElementById('users_btn');
            u_btn.style.display = 'none'
            var c_btn = document.getElementById('creators_btn-show');
            c_btn.style.display = 'block'
            var up_u_btn = document.getElementById('users_btn-show');
            up_u_btn.style.display = 'block'
            var up_c_btn = document.getElementById('creators_btn');
            up_c_btn.style.display = 'none'
        } else {
            var up_u_btn = document.getElementById('users_btn-show');
            up_u_btn.style.display = 'none'
            var up_c_btn = document.getElementById('creators_btn');
            up_c_btn.style.display = 'block'
            var cre_btn = document.getElementById('creators_btn-show');
            cre_btn.style.display = 'none';
            var use_btn = document.getElementById('users_btn');
            use_btn.style.display = 'block';
        }

    }

    // if(position == 'up') {
    //     var u_btn = document.getElementById("users_btn-show");
    //     var c_btn = document.getElementById("creators_btn-show");
    //     u_btn.classList.remove("d_none");
    //     c_btn.classList.add("d_none");
    // } else {
    //     var u_btn = document.getElementById("users_btn-show");
    //     var c_btn = document.getElementById("creators_btn-show");
    //     u_btn.classList.add("d_none");
    //     c_btn.classList.remove("d_none");
    // }

}

function changeText(text) {
    var creators = document.getElementById('creators')
    switch (text) {
        case 'account_approval':
            creators.innerHTML = `
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
            `;
            break;
        case 'how_do_i_start':
            creators.innerHTML = `
                <p class="creators_text"><b>To start earning, please do the following via the BANK tab in your menu:</b></p>
                <ul>
                  <li>Make an account on MassPay.io then add your bank account information</li>
                  <li>The bank account should be in the same country as the country that issued the verified photo ID of the Creator. MassPay offers multiple options for payout type,</li>
                  <li>Make sure your account is verified, an account is verified once we receive your IDs</li>
                  <li>Make sure you have set your subscription price can be free or starting at $4.97 per month, you choose your pricing.</li>
                  <li>You can also earn through the PPV post feature and through Tips on your profile, posts or live shows. 90% payout on all tip earnings.</li>
                  <li>Automated Payouts are every Monday to your MassPay wallet, minimum $100 Instant Payouts are available $20 minimum. Make sure your masspay email and account email are the same in order to receive payouts. </li>
                  <li>YourFansLive is not responsible for any fees MassPay may charge you for your payment type of choice on withdrawal.</li>
                </ul>`;
            break;
        case 'payout_percentages':
            creators.innerHTML = `
                <p><b>Preators receive a percentage of the revenue on their monthly earnings including
                subscriptions, paid messages and tips. These percentages vary based on subscription
                and PPV earnings and they are:</b>
                </p>
                <p>- 85% for earning up to $5,000</p>
                <p>- 90% for earning $5,001 up to $10,000</p>
                <p>- 95% for earning $10,001 or higher</p>
                <p>- Tip earnings are always a 90% payout</p>
                <p>- Referrals are 1% for life of the earnings of the creator you referred as long as
                they have an account on the site.</p>
                <p>The remaining percentage we take covers referral payments, payment processing,
                hosting, support, and all other services.</p>
                <p><b>Payouts are initiated Weekly or Bi-weekly based on earnings, the minimum payout for
                each are:</b></p>
                <p>-  Weekly payouts are a minimum of $100</p>
                <p>- Instant Payout minimum of $20</p>
                <p><b>Subscription, PPV pricing and Tips</b></p>
                <p>- Paid Subscription pages are a minimum of $4.97 or any price you prefer</p>
                <p>- PPV posts are a minimum of $3 or any price you prefer</p>
                <p>- Tips are a minimum of $2 or whatever the User prefers</p>
            `;
            break;
        case 'how_much':
            creators.innerHTML = `
            <p>The amount that you see in your current net balance is the exact amount in USD that will be
            sent to your payment method of choice. Your bank may charge you currency conversion
            or transfer fees to receive the money. Additionally, your e-wallet company may charge
            you a fee for accessing the money. Payouts go out in USD and will be converted to the
            currency of your account through MassPay.</p>
            <p>YourFansLive does not have control over currency exchange rates or fees from MassPay to your payout
            option of choice.</p>
            `;
            break;
        case 'paid_account':
            creators.innerHTML = `
            <p>Paid accounts require Fans to pay a monthly subscription fee to view your feed.</p>
            <p>Free accounts allow Fans to subscribe without paying a monthly subscription fee.</p>
            <p>However, all accounts allow for paid posts and paid live streams, you can choose your
            post option in the drop down menu on a new post. These options are:</p>
            <p>-  Public: Free post that any user can view wording/media and can
            like/comment/tip</p>
            <p>- PPV: Locked post that any user can view wording on but must pay to see media.
            Once paid they can like/comment/tip</p>
            <p>-  Subscribers: Locked post than any user can view wording on but user must be a
            subscriber to view media, like/comment/tip</p>
            <p>Otherwise, there are no differences between the two types of accounts.</p>
            <p>If you’d like more detailed information on how purchases work between fans and creators please refer
            to our <span> Standard Contract Between Fan and Creator</span></p>
            `;
            break;
        case 'subscription':
            creators.innerHTML = `
            <p>Subscriptions are Monthly fees a subscriber pays to view your subscriber only content.</p>
            <p>You can set the profile as free if you like but our posts have options to make the post
            itself Public(free), PPV or for subscribers only. We recommend setting a subscription
            price so your subscribers can have a VIP experience on the site.</p>
            <p>Minimum Subscription Price is $4.97 but you can set it to Free or any price above that you wish.</p>
            <p>We recommend using the Public posts as a way to post teaser content for users, you
            can post pictures, videos, previews for your Locked Content or whatever you wish.</p>
            <p>Public posts are able to be commented on, liked and tipped on by any registered user
            who visits your page. They are a great way to gain interest in your PPV and Subscriber
            only content.</p>
            `;
            break;
        case 'who_can_see':
            creators.innerHTML = `
            <p><b>All Users who use your link to go to the site will be prompted to sign up or log in and then be redirected
            back to your profile page.</b></p>
            <p><b>Free Account</b></p>
            <ul>
                <li> Anyone registered to YourFansLive can subscribe to your free account and
                see all of your past and current posts on your page.
                </li>
                <li>Please note that all content must comply with our Terms of Service or be
                subject to suspension or closure.
                </li>
                <li>Users can see your feed and any wording on PPV/Subscriber locked posts but
                they can only see media on posts marked Public.</li>
                <li>Fans can view media on all your current posts and future posts marked as
                public or for subscribers after they subscribe.</li>
                <li>The media content contained within your posts is hidden from nonsubscribers unless it is marked as Public.</li>
            </ul>
            <p>You can set your profile to PRIVATE in your Profile Editing section. If set to Private your
            profile can only be seen by someone who has/uses your URL link.</p>

            `;
            break;
        case 'creators_featured':
            creators.innerHTML = `
            <p>If your content contains someone other than you, you must either:</p>
            <ul>
                <li>Tag that person’s YourFansLive account; or</li>
                <li>If they do not have a YourFansLive account, please ask them to make one;</li>
                <li>If they cannot or do not want to make a YourFansLive account, you will need
                to provide a copy of their photo ID and release documents before the content
                can be posted. You can send the documents in an email to support@yourfanslive.com with the subject
                line as follows
                2257/ID – (your screen name) for (scene partner stage name)</li>
            </ul>
            <p>In the body of the email repeat the subject line and include their Legal Name as well and attach the
            documents into the email. These will go into our encrypted system for safe keeping. Once we have the
            ID/2257 you can post any content with that scene partner that you’d like.</p>
            <p>Documents/ID needed are 2257 and image of Photo ID and Photo of them Holding the Photo ID next to
            their face. All images must be legible and not blurry. If you can’t read it, neither can we. </p>
            <p>To tag another Creator’s account, simply put their Screen Name in the post. You can also include their full
            on your post. Their Stage Name may be different than the screen name listed on the site so make sure
            you check it before posting </p>
            <p>Creator accounts must feature the person whose ID is on file with YourFansLive and
            who has passed all of the ID verification requirements. Whether as another creator on
            the site or through the file submission email.</p>
            <p>If an account features other creators, please note that the majority of the content must
            feature the person whose ID is on file with YourFansLive and who has passed all of the
            ID verification requirements.</p>

            `;
            break
        case 'tax_forms':
            creators.innerHTML = `
            <p>All USA Creators will receive a 1099 NEC document each year if they have earned and
            withdrawn over $600 dollars in the calendar year. Copies will be filed with the IRS and
            emailed to Creators to the email address on file.
            </p>
            <p>If you are not a USA based creator you can get your yearly earnings statement in the Reports section of
            your menu and file taxes as needed for your home country. </p>

            `;
            break;
        case 'what_can_post':
            creators.innerHTML = `
            <p>We don’t have many rules but there are a few we abide by. You can see these in detail in
            our <span>Acceptable Use Policy</span>, <span>Terms of Service</span> and <span>Privacy Policy</span></p>
            <p>Upload Limits are as follows:</p>
            <p>- 3gb per post photo or video, 1 video up to 20 photos per post within the 3gb limit.</p>
            <p>You can refer to your welcome email for a visual breakdown of our content guidelines.</p>

            `;
            break;

    }
}

// function show_users() {
//     var creators = document.getElementById("creators");
//     var users = document.getElementById("users");
//     creators.classList.add("d_none");
//     users.classList.remove("d_none");
// }
