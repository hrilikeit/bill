<?php

class ContactUsView
{
    public function __construct()
    {
    }

    public function getHTML()
    {
        $html = <<<__END__
<div class="privacy_policy">		

<h1>Contact Us</h1>

<p>To contact us for any issues related to the website, your account, billing or advertising, we can be reached at:</p>

<ul style="font-size: 13px;">
    <li>City Scene LLC</li>
    <li>World Headquarters</li>
    <li>1275 N Clyborn St 1st Floor</li>
    <li>Chicago IL 60610</li>
    <li>
        Tel <a href="tel:+18777290799" style="color: #555">1-877-729-0799</a>
    </li>
    <li>
        Email <a href="mailto:support@yourfanslive.com" style="color: #555">support@yourfanslive.com</a>
    </li>
    <li>
        For community guideline 
        contact email <a href="mailto:help@yourfanslive.com" style="color: #555">help@yourfanslive.com</a>
    </li>
</ul>

</div> <!--end of .privacy_policy -->		
__END__;

        return $html;
    }
}
