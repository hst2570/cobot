<?php

define('MAX_PATH',dirname(__FILE__));

require_once MAX_PATH . '/env.php';
require_once MAX_PATH . '/controller/GetTradeController.php';

$buy = new BuyController('ETH');
$buy->getBuy();