<?php

define('MAX_PATH',dirname(__FILE__));
require_once MAX_PATH . '/handle/Telegram.php';
require_once MAX_PATH . '/env.php';
require_once MAX_PATH . '/conf.php';
require_once MAX_PATH . '/handle/UrlJsonParser.php';
require_once MAX_PATH . '/service/Marketcap.php';

$json_parser = UrlJsonParser::getInstance();
$upbit_list = $json_parser->getJsonToArray('https://ccx.upbit.com/api/v1/market_status/all');
$db = new mysqli($GLOBALS['database_host'], $GLOBALS['database_user'], $GLOBALS['database_password'], $GLOBALS['database_name']);
$marketcap = new Marketcap();

$sql = 'select id from upbit_new';
$sql_result = $db->query($sql)->fetch_all();

$collected_coin = array();
if (!empty($sql_result)) {
    foreach ($sql_result as $item) {
        $collected_coin[$item[0]] = $item[0];
    }
}

$current_coin = array();

foreach ($upbit_list as $item) {
    $id = $item['id'];
    $name = preg_replace('/(\w+)\/(\w+)/', '$1', $item['name']);
    if (!in_array($item['id'], $collected_coin)) {
        if (!in_array($name, $collected_coin)) {
            $current_coin[] = $name;
            $message = "## Upbit new wallet 🚀🚀🚀 ##\n*------------*\n";
            $message = $message.$name."\n*------------*\n";
            $message = $message."exchange list\n".implode(', ', $marketcap->get_markets($name));
            $telegram = new Telegram($GLOBALS['BOT_TOKEN'], $GLOBALS['TELEGRAM_CHANNEL_ID']);
            $telegram->telegramApiRequest("sendMessage", $message);
            $telegram = new Telegram($GLOBALS['BOT_TOKEN'], $GLOBALS['TELEGRAM_NORMAL_CHANNEL_ID']);
            $telegram->telegramApiRequest("sendMessage", $message);
        }

        $sql = 'insert into upbit_new (id) values ("'.$id.'")';
        $db->query($sql);
    }
}


