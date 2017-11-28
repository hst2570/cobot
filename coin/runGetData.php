<?php

define('MAX_PATH',dirname(__FILE__));

require_once MAX_PATH . '/env.php';
require_once MAX_PATH . '/conf.php';
require_once MAX_PATH . '/controller/GetTradeController.php';

$buy = new GetTradeController($argv[1]);
$buy->setCondition();
