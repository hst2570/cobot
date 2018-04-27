<?php
define('MAX_PATH',dirname(dirname(dirname(__FILE__))));
require_once MAX_PATH . '/coin/env.php';
require_once MAX_PATH . '/coin/conf.php';
require_once MAX_PATH . '/coin/binance/ChartCalculate.php';
require_once MAX_PATH . '/coin/binance/ApiCall.php';

$calculate = new ChartCalculate();
$api = new ApiCall();

$private_key = $GLOBALS['BINANCE_PRIVATE_KEY'];

$now = $api->test_time();
$now = json_decode($now, true);
$now = $now['serverTime'];
$now = preg_replace('/([0-9]{10}).*/', '$1', $now);

$db = new mysqli($GLOBALS['database_host'], $GLOBALS['database_user'], $GLOBALS['database_password'], $GLOBALS['database_name']);
$telegram = new Telegram($GLOBALS['BOT_TOKEN'], $GLOBALS['BINANCE_TRADE_GROUP_ID']);
$sql = 'select * from binance_trade where status = "buy"';

$list = $db->query($sql);

while ($row = $list->fetch_assoc()) {
    $symbol = $row['symbol'];
    $buy_price = $row['buy_price'];
    $q = $row['quantity'];

    $current_coin_info = $api->test_bookTicker([
        'symbol' => $symbol
    ]);
    $current_price = $current_coin_info['bidPrice'];

    if ($current_price > $buy_price * 1.035) {
        $result = $api->test_order([
            'symbol' => $symbol,
            'side' => 'SELL',
            'type' => 'MARKET',
            'quantity' => $q,
            'timestamp' => $now.'000',
            'signature' => hash_hmac('sha256', http_build_query([
                'symbol' => $symbol,
                'side' => 'SELL',
                'type' => 'MARKET',
                'quantity' => $q,
                'timestamp' => $now.'000',
            ]), $private_key)
        ]);

        $sql = 'update binance_trade set status="sell" and sell_price='.$current_price.'
                where id='.$row['id'];

        $db->query($sql);
        $telegram->telegramApiRequest("sendMessage", '
            판매: '.$symbol."\n갯수: ".$q."\n가격: ".$current_price);
        echo "Sell\n\n\n";
    }
}