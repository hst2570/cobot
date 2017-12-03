<?php

define('MAX_PATH',dirname(__FILE__));

require_once MAX_PATH . '/env.php';
require_once MAX_PATH . '/conf.php';
require_once MAX_PATH . '/controller/ApiCheckController.php';

$info = new ApiCheckController();
$info->check_api();
