<?php

$auth = isset($_GET['auth']) ? $_GET['auth'] : '';

header("Location:/?mode=Login&job=reset_password&pwauth={$auth}");