<?php

define('MAX_PATH',dirname(__FILE__));

require_once MAX_PATH . '/env.php';
require_once MAX_PATH . '/conf.php';
require_once MAX_PATH . '/controller/CoinInfoController.php';

$info = new CoinInfoController($argv[1]);
$info->check_coin_status();
