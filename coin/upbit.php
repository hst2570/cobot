<?php

define('MAX_PATH',dirname(__FILE__));
require_once MAX_PATH . '/handle/Telegram.php';
require_once MAX_PATH . '/env.php';
require_once MAX_PATH . '/conf.php';
require_once MAX_PATH . '/handle/UrlJsonParser.php';

$db = new mysqli($GLOBALS['database_host'], $GLOBALS['database_user'], $GLOBALS['database_password'], $GLOBALS['database_name']);
$sql = 'select * from upbit order by row_number desc limit 1';
$len = $db->query($sql)->fetch_all();
$len = $len[0][0];
date_default_timezone_set('UTC');
$date = strtotime('now');
$date = date('Y-m-d H:i:s', $date);

for ($i = $len + 1 ; $i < $len + 10 ; $i++) {
    $url = 'https://api-manager.upbit.com/api/v1/notices/' . $i;

    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($handle, CURLOPT_TIMEOUT, 60);
    $response = curl_exec($handle);

    $result = json_decode($response);

    if ($result->success === true) {
        $message = "##업비트 새로운 공지##\n";
        $message = $message.$result->data->title."\n";
        $message = $message.$result->data->body."\n";
        /* 그룹 */
        $telegram = new Telegram($GLOBALS['BOT_TOKEN'], $GLOBALS['TELEGRAM_GROUP_ID']);
        $telegram->telegramApiRequest("sendMessage", $message);
        $sql = 'insert into upbit (row_number) VALUES ('.$i.')';
        $db->query($sql);

        /* 채널 */
        $telegram->setGroupId($GLOBALS['TELEGRAM_CHANNEL_ID']);
        $telegram->telegramApiRequest("sendMessage", $message);
    }
}

$in = UrlJsonParser::getInstance();
$curl = $in->getCurl('support.binance.com/hc/en-us/sections/115000106672-New-Listings');

$result = preg_split('/\n/',$curl);
$rex = '/.*class="article-list-link">(.*)<\/a>/';
$list = array();

foreach ($result as $line) {
    if (preg_match($rex, $line)) {
        $list = preg_replace('/.*class="article-list-link">(.*)<\/a>/', '$1', $line);
        $sql = 'select * from binance where contents="'.$list.'"';
        $isset = $db->query($sql)->fetch_all();

        if (empty($isset)) {
            $sql = 'insert into binance (contents) VALUES ("'.$list.'")';
            $db->query($sql);

            $message = "### 바이넨스 new lists ###\n\n
                $list\n\n
                $date";

            $telegram = new Telegram($GLOBALS['BOT_TOKEN'], $GLOBALS['TELEGRAM_GROUP_ID']);
            $telegram->telegramApiRequest("sendMessage", $message);

            $telegram->setGroupId($GLOBALS['TELEGRAM_CHANNEL_ID']);
            $telegram->telegramApiRequest("sendMessage", $message);
        }
    }
}