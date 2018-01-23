<?php

define('MAX_PATH',dirname(__FILE__));
require_once MAX_PATH . '/handle/Telegram.php';
require_once MAX_PATH . '/env.php';
require_once MAX_PATH . '/conf.php';
require_once MAX_PATH . '/handle/UrlJsonParser.php';
require_once MAX_PATH . '/service/Marketcap.php';

$json_parser = UrlJsonParser::getInstance();
$binance_json = $json_parser->getJsonToArray('https://www.binance.com/assetWithdraw/getAllAsset.html');
$db = new mysqli($GLOBALS['database_host'], $GLOBALS['database_user'], $GLOBALS['database_password'], $GLOBALS['database_name']);
$marketcap = new Marketcap();

$sql = 'select id from binance_new';
$sql_result = $db->query($sql)->fetch_all();

$collected_coin = array();
$coin_list = array();

foreach ($binance_json as $item) {
    $coin_list[] = $item['assetName'];
}

if (!empty($sql_result)) {
    foreach ($sql_result as $item) {
        $collected_coin[$item[0]] = $item[0];
    }
}

foreach ($coin_list as $list) {
    $list = strtolower($list);
    if (!array_search($list, $collected_coin)) {

        $message = "## Binance new wallet 🚀🚀🚀 ##\n*------------*\n";
        $message = $message.$list."\n*------------*\n";
        $message = $message."exchange list\n".implode(', ', $marketcap->get_markets($list));

        $telegram = new Telegram($GLOBALS['BOT_TOKEN'], $GLOBALS['TELEGRAM_CHANNEL_ID']);
        $telegram->telegramApiRequest("sendMessage", $message);

        $sql = 'insert into binance_new (id) values ("'.$list.'")';
        $db->query($sql);
    }
}