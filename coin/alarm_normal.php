<?php

define('MAX_PATH',dirname(__FILE__));
require_once MAX_PATH . '/env.php';
require_once MAX_PATH . '/conf.php';
require_once MAX_PATH . '/controller/AlarmController.php';

$support_wallet = "-- 여러분의 후원으로 더 좋은 알람 서비스를 만들어 가겠습니다. --\nQtum wallet: QXoPcHjx51m92qc4mpFwBSbbmKp4oVn9nt \n";

$instance = new AlarmController('', $support_wallet);
$instance->start_normal();

