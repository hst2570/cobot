<?php
define('MAX_PATH',dirname(dirname(dirname(__FILE__))));
require_once MAX_PATH . '/coin/env.php';
require_once MAX_PATH . '/coin/conf.php';
require_once MAX_PATH . '/coin/binance/ChartCalculate.php';
require_once MAX_PATH . '/coin/binance/ApiCall.php';

$db = new mysqli($GLOBALS['database_host'], $GLOBALS['database_user'], $GLOBALS['database_password'], $GLOBALS['database_name']);
$calculate = new ChartCalculate();
$api = new ApiCall();

$intervals = [
    '15m',
    '1h',
    '4h'
];

$now = $api->test_time();
$now = json_decode($now, true);
$now = $now['serverTime'];
$now = preg_replace('/([0-9]{10}).*/', '$1', $now);

$private_key = $GLOBALS['BINANCE_PRIVATE_KEY'];
$symbol_data = $api->test_symbol();

$my_account = $api->test_account([
    'recvWindow' => '5000',
    'timestamp' => $now.'000',
    'signature' => hash_hmac('sha256', http_build_query([
        'recvWindow' => '5000',
        'timestamp' => $now.'000',
    ]), $private_key)
]);

$my_account = $my_account['balances'];
$my_coin = [];

foreach ($my_account as $coin) {
    $my_coin[$coin['asset']] = $coin['free'];
}
$my_btc = $my_coin['BTC'];
unset($my_coin['BTC']);

$symbols = [];
foreach ($symbol_data['data'] as $data) {
    if ($data['status'] === 'TRADING' && preg_match('/.*BTC$/', $data['symbol'])) {
        $symbols[] = $data['symbol'];
    }
}

foreach ($symbols as $symbol) {
    $AvgMove = [];
    $AvgPrice = [];
    $RHigh = [];
    $RRow = [];
    $Rsi = [];
    $cci = [];

    foreach ($intervals as $interval) {
        $calculate->setCoin($interval, $symbol);
        $AvgMove[$interval] = $calculate->getAvgMove();
        $AvgPrice[$interval] = $calculate->getAvgPrice();
        $RHigh[$interval] = $calculate->getRHigh();
        $RRow[$interval] = $calculate->getRRow();
        $Rsi[$interval] = $calculate->getRsi();
        $cci[$interval] = $calculate->getAvgCci();
    }

    if ($calculate->is_buy($AvgMove, $AvgPrice, $RHigh, $RRow, $Rsi, $cci)) {
        $current_coin_info = $api->test_bookTicker([
            'symbol' => $symbol
        ]);
        $current_price = $current_coin_info['askPrice'];

        $q = round(($my_btc * 0.10) / $current_price, 4);

        $result = $api->test_order([
            'symbol' => $symbol,
            'side' => 'BUY',
            'type' => 'MARKET',
            'quantity' => $q,
            'recvWindow' => '5000',
            'timestamp' => $now.'000',
            'signature' => hash_hmac('sha256', http_build_query([
                'symbol' => $symbol,
                'side' => 'BUY',
                'type' => 'MARKET',
                'quantity' => $q,
                'recvWindow' => '5000',
                'timestamp' => $now.'000',
            ]), $private_key)
        ]);
        echo "Buy \n\n\n";
        $sql = 'insert into binance_trade 
                (symbol, buy_price, quantity, status) values 
                ("'.$symbol.'", '.$current_price.', '.$q.', "buy")';

        $db->query($sql);
    }
}