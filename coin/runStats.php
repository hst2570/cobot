<?php

define('MAX_PATH',dirname(__FILE__));

require_once MAX_PATH . '/env.php';
require_once MAX_PATH . '/conf.php';
require_once MAX_PATH . '/controller/StatsController.php';

$stats = new StatsController();
$stats->total_stats();
