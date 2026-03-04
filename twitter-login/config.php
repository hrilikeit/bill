<?php

require_once 'vendor/autoload.php';

$config = [
    'callback' => 'https://yourfanslive.com/redirect-twitter.php',
    'keys'     => ['key' => 'NSDtySo7KZ7KG8FnTaLDMqqA5', 'secret' => '1ON1LmawscKEiGsouKE3LD2lC6uxvFT8Wa3NOCDxh7SyAvWrMY'],
    'authorize' => true
];

$adapter = new Hybridauth\Provider\Twitter( $config );

