<?php

define('MAX_PATH',dirname(__FILE__));
require_once MAX_PATH . '/env.php';
require_once MAX_PATH . '/conf.php';
require_once MAX_PATH . '/controller/AlarmController.php';

$support_wallet = "-- 여러분의 후원으로 더 좋은 알람 서비스를 만들어 가겠습니다. --\nEthereum wallet: 0xd585CbfBB7b0ADf690A524E3fED146b35d5eE90c\n";

$instance = new AlarmController('', $support_wallet);
$instance->start_normal();

