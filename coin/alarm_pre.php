<?php

define('MAX_PATH',dirname(__FILE__));
require_once MAX_PATH . '/env.php';
require_once MAX_PATH . '/conf.php';
require_once MAX_PATH . '/controller/AlarmController.php';

$instance = new AlarmController($GLOBALS['TELEGRAM_CHANNEL_ID']);
$instance->start();

//$instance2 = new AlarmController($GLOBALS['TELEGRAM_GROUP_ID']);
//$instance2->start();