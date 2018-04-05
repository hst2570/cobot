<?php

define('MAX_PATH',dirname(__FILE__));
require_once MAX_PATH . '/env.php';
require_once MAX_PATH . '/conf.php';
require_once MAX_PATH . '/controller/AlarmController.php';

$support_wallet = "-- 후원 부탁드립니다 --\n
Ethereum wallet: 0xd585CbfBB7b0ADf690A524E3fED146b35d5eE90c\n
Bitcoin wallet: 1J3sfgdyGQMhVdouSHiu8ZLYvZLyrDY82g\n";

$instance = new AlarmController('', $support_wallet);
$instance->start_normal();

