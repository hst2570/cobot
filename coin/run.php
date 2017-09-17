<?php

define('MAX_PATH',dirname(__FILE__));

require_once MAX_PATH . '/controller/BuyController.php';

$buy = new BuyController('ETH');
$buy->getBuy();