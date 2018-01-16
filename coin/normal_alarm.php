<?php

define('MAX_PATH',dirname(__FILE__));
require_once MAX_PATH . '/handle/Telegram.php';
require_once MAX_PATH . '/env.php';
require_once MAX_PATH . '/conf.php';
require_once MAX_PATH . '/handle/UrlJsonParser.php';

$db = new mysqli($GLOBALS['database_host'], $GLOBALS['database_user'], $GLOBALS['database_password'], $GLOBALS['database_name']);
$sql = 'select * from upbit_n order by row_number desc limit 1';
$len = $db->query($sql)->fetch_all();
$len = $len[0][0];

$support_wallet = "\n## 여러분의 후원으로 더 좋은 알람 서비스를 만들어 가겠습니다. ##\n@ Qtum: QXoPcHjx51m92qc4mpFwBSbbmKp4oVn9nt \n";

for ($i = $len + 1 ; $i < $len + 10 ; $i++) {
    $url = 'https://api-manager.upbit.com/api/v1/notices/' . $i;

    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($handle, CURLOPT_TIMEOUT, 60);
    $response = curl_exec($handle);

    $result = json_decode($response);

    if ($result->success === true) {
        $message = "## Upbit new Announcement  ##\n";
        $message = $message.$result->data->title."\n";
        $message = $message.$result->data->body."\n";
        $message = $message.$support_wallet;

        $telegram = new Telegram($GLOBALS['BOT_TOKEN'], $GLOBALS['TELEGRAM_NORMAL_CHANNEL_ID']);
        $telegram->telegramApiRequest("sendMessage", $message);
        $sql = 'insert into upbit_n (row_number) VALUES ('.$i.')';
        $db->query($sql);
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
        $sql = 'select * from binance_n where contents="'.$list.'"';
        $isset = $db->query($sql)->fetch_all();

        if (empty($isset)) {
            $sql = 'insert into binance_n (contents) VALUES ("'.$list.'")';
            $db->query($sql);

            $message = "### Binance new Announcement ###\n\n$list";
            $message = $message.$support_wallet;
            $telegram = new Telegram($GLOBALS['BOT_TOKEN'], $GLOBALS['TELEGRAM_NORMAL_CHANNEL_ID']);
            $telegram->telegramApiRequest("sendMessage", $message);
        }
    }
}

$curl = $in->getCurl('https://news.kucoin.com/en/');

$result = preg_split('/\n/',$curl);
$rex = '/.*[^>].*href="https:\/\/news.kucoin.com\/en.*>(.*)<\/a>$/';
$list = array();
$new_article = false;
$next = false;
$contents = '';
foreach ($result as $line) {
    if ($new_article === true && preg_match('/.*div.*post-content.*/', $line)) {
        $next = true;
    }
    if ($new_article === true && $next === true && preg_match('/.*<\/div>/', $line)) {

        $contents =  strip_tags($contents);
        $contents = preg_replace('/(\t)/', '', $contents);
        $message = "### KuCoin new Announcement ###\n\n@".$list."\n".$contents."\n";
        $message = $message.$support_wallet;
        $telegram = new Telegram($GLOBALS['BOT_TOKEN'], $GLOBALS['TELEGRAM_NORMAL_CHANNEL_ID']);
        $telegram->telegramApiRequest("sendMessage", $message);

        $new_article = false;
        $next = false;
        $contents = '';
    }
    if ($next === true) {
        $contents = $contents ."\n". $line;
    }
    if (preg_match($rex, $line)) {
        $list = preg_replace($rex, '$1', $line);
        $sql = 'select * from kucoin_n where contents="'.$list.'"';
        $isset = $db->query($sql)->fetch_all();

        if (empty($isset)) {
            $new_article = true;
            $sql = 'insert into kucoin_n (contents) VALUES ("'.$list.'")';
            $db->query($sql);
        }
    }
}

$curl = $in->getCurl('http://bithumb.cafe/notice');

$result = preg_split('/\n/',$curl);
$rex = '/.*href="http:\/\/bithumb.cafe\/archives.*>(.*)<\/a>$/';
$list = array();

foreach ($result as $line) {
    if (preg_match($rex, $line)) {
        $list = preg_replace($rex, '$1', $line);
        $no = preg_replace('/.*href="http:\/\/bithumb.cafe\/archives\/(.*)".*>(.*)<\/a>$/', '$1', $line);
        if ($list !== '' && $list !== "더 보기") {
            $sql = 'select * from bithumb_n where contents="'.$no.'"';
            $isset = $db->query($sql)->fetch_all();

            if (empty($isset)) {
                $sql = 'insert into bithumb_n (contents) VALUES ("'.$no.'")';
                $db->query($sql);

                $message = "### bithumb new Announcement ###\n\n$list";
                $message = $message.$support_wallet;

                $telegram = new Telegram($GLOBALS['BOT_TOKEN'], $GLOBALS['TELEGRAM_NORMAL_CHANNEL_ID']);
                $telegram->telegramApiRequest("sendMessage", $message);
            }
        }
    }
}