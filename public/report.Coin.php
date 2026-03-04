<?php

$fo = fopen('coin.txt', 'w');
fwrite($fo, file_get_contents('php://input'));
fclose($fo);
