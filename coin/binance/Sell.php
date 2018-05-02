<?php
define('MAX_PATH',dirname(dirname(dirname(__FILE__))));
require_once MAX_PATH . '/coin/env.php';
require_once MAX_PATH . '/coin/conf.php';
require_once MAX_PATH . '/coin/binance/SellCondition.php';
require_once MAX_PATH . '/coin/binance/ApiCall.php';
require_once MAX_PATH . '/coin/handle/Telegram.php';

$api = new ApiCall();
$sell_condition = new SellCondition();
$db = new mysqli($GLOBALS['database_host'], $GLOBALS['database_user'], $GLOBALS['database_password'], $GLOBALS['database_name']);

$sql = 'select * from binance_trade where status = "buy"';
$list = $db->query($sql);

$private_key = $GLOBALS['BINANCE_PRIVATE_KEY'];

$now = $api->test_time();
$now = json_decode($now, true);
$now = $now['serverTime'];
$now = preg_replace('/([0-9]{10}).*/', '$1', $now);

$my_account = $api->test_account([
    'recvWindow' => '5000',
    'timestamp' => $now.'000',
    'signature' => hash_hmac('sha256', http_build_query([
        'recvWindow' => '5000',
        'timestamp' => $now.'000',
    ]), $private_key)
]);
$my_coin_quantity = [];
foreach ($my_account['balances'] as $balance) {
    $my_coin_quantity[$balance['asset']] = $balance['free'];
}

while ($row = $list->fetch_assoc()) {
    $symbol = $row['symbol'];
    $coin = preg_replace('/^(\w{2,6})BTC$/', '$1', $symbol);
    $buy_price = $row['buy_price'];

    $current_coin_info = $api->test_bookTicker([
        'symbol' => $symbol
    ]);
    $current_price = $current_coin_info['bidPrice'];

    $q = $my_coin_quantity[$coin];

    if ($current_price > $buy_price * 1.035) {
        if (!$sell_condition->is_sell($symbol)) {
            continue;
        }
        sell($symbol, $q, $current_price, $db, '일반');
    } else if ($current_price > $buy_price * 1.1) {
        sell($symbol, $q, $current_price, $db, '고가격');
    }

    if ($current_price < $buy_price * 0.97 && $current_price > $buy_price * 0.965) {
        if (!$sell_condition->is_sell($symbol)) {
            continue;
        }
        sell($symbol, $q, $current_price, $db, '손절');
    }
    sleep(0.5);
}

function sell($symbol, $q, $current_price, $db, $type)
{
    $api = new ApiCall();
    $telegram = new Telegram($GLOBALS['BOT_TOKEN'], $GLOBALS['BINANCE_TRADE_GROUP_ID']);
    $now = $api->test_time();
    $now = json_decode($now, true);
    $now = $now['serverTime'];
    $now = preg_replace('/([0-9]{10}).*/', '$1', $now);
    $private_key = $GLOBALS['BINANCE_PRIVATE_KEY'];
    $quantity = preg_replace('/^(\d+)\.(\d{0,3}).*/', '$1.$2', $q);

    $result = $api->test_order([
        'symbol' => $symbol,
        'side' => 'SELL',
        'type' => 'MARKET',
        'quantity' => $quantity,
        'timestamp' => $now.'000',
        'signature' => hash_hmac('sha256', http_build_query([
            'symbol' => $symbol,
            'side' => 'SELL',
            'type' => 'MARKET',
            'quantity' => $quantity,
            'timestamp' => $now.'000',
        ]), $private_key)
    ]);

    if (!isset($result['code'])) {
        $sql = 'update binance_trade set status="sell", sell_price='.$current_price.'
                where symbol="'.$symbol.'" and status = "buy"';

        $db->query($sql);
//        $telegram->telegramApiRequest("sendMessage", $type.' 판매: '.$symbol."\n갯수: ".$quantity."\n가격: ".$current_price);
    } else {
        $quantity = preg_replace('/^(\d+)\.(\d{0,1}).*/', '$1', $q);
        $result = $api->test_order([
            'symbol' => $symbol,
            'side' => 'SELL',
            'type' => 'MARKET',
            'quantity' => $quantity,
            'timestamp' => $now.'000',
            'signature' => hash_hmac('sha256', http_build_query([
                'symbol' => $symbol,
                'side' => 'SELL',
                'type' => 'MARKET',
                'quantity' => $quantity,
                'timestamp' => $now.'000',
            ]), $private_key)
        ]);
        if (!isset($result['code'])) {
            $sql = 'update binance_trade set status="sell", sell_price='.$current_price.'
                where symbol="'.$symbol.'" and status = "buy"';

            $db->query($sql);
//            $telegram->telegramApiRequest("sendMessage", $type.' 판매: '.$symbol."\n갯수: ".$quantity."\n가격: ".$current_price);
        } else {
            $telegram->telegramApiRequest("sendMessage", $type.' 판매 실패: '.$symbol."\n갯수: ".$quantity."\n가격: ".$current_price);
            var_dump($result);
        }
    }
}