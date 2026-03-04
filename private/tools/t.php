<?php

require('TripleDES.php');

$des = new Crypt_TripleDES();
$des->setKey('d83298ac4300ffcc04180726');

$enc = $c = $des->encrypt('5512151512186231');


print $enc . "\n\n";

print $des->decrypt($enc) . "\n\n\n";
