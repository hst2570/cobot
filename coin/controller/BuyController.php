<?php

require_once MAX_PATH . '/handle/xcoin_api_client.php';

class BuyController
{
    private $coin_type;
    private $db;
    private $api;
    private $account_path = '/info/balance';
    private $trade_path = '/trade/market_buy';
    private $current_price = 0;
    private $field = array(
        'traded_info_id',
        'transaction_date',
        'type',
        'units_traded',
        'price',
        'total',
        'coin_type',
        'registered_time'
    );

    public function __construct($coin_type)
    {
        $this->db = new mysqli($GLOBALS['database_host'], $GLOBALS['database_user'], $GLOBALS['database_password'], $GLOBALS['database_name']);
        $this->coin_type = $coin_type;
        $this->api = new XCoinAPI();
    }

    public function getBuy()
    {
        if (!$this->condition()) {
            echo 'not chance';
        } else {
//$this->current_price = 11150000;
            $this->buyConin();
        }
    }

    private function condition()
    {
        $sql = 'select * from traded_info where coin_type = "'.$this->coin_type.'" order by transaction_date asc';

        $result = $this->db->query($sql)->fetch_all();
        $cutline = intval(sizeof($result) / 25);
        $lineData = array();
        $tmpArr = array();

        for ($i = 0 ; $i < sizeof($result)-1 ; $i++) {
            array_push($tmpArr, $result[$i]);
            if ($i % $cutline === 0) {
                array_push($lineData, $tmpArr);
                $tmpArr = array();
            }
        }

        $average = array();
        $sum = 0;

        foreach ($lineData as $key => $items) {
            foreach ($items as $item) {
                $sum = $sum + $item[3];
            }
            array_push($average, $sum / sizeof($items));
            $sum = 0;
        }

        $low = 99999999999;
        $lowLoc = 0;
        $high = 0;
        $highLoc = 0;
        $currentPrice = $result[sizeof($result)-1][3];
        $this->current_price = $currentPrice;

        for ($i = 0 ; $i < sizeof($average)-1 ; $i++) {
            if ($average[$i] < $low) {
                $low = $average[$i];
                $lowLoc = $i;
            }
            if ($average[$i] > $high) {
                $high = $average[$i];
                $highLoc = $i;
            }
        }

        if ($high > $low * 1.10) {
echo $high. "\n";
echo $low. "\n";
echo '폭락';
            return false;
        }

        if ($high > $currentPrice * 1.10) {
echo $high. "\n";
echo $currentPrice. "\n";
echo '폭락2';
            return false;
        }

        $is_high = $high > $currentPrice * 1.010;
        $renge_high = 25 - $highLoc > 3;
        $is_low = $low < $currentPrice;
        $renge_low = 25 - $lowLoc > 3;

        if ($is_high && $is_low && $renge_high && $renge_low) {
            return true;
        } else {
echo $high. "\n";
echo $low. "\n";
echo $currentPrice. "\n";
echo $highLoc. "\n";
echo $lowLoc. "\n";

var_dump($is_high);
var_dump($is_low);
var_dump($renge_high);
var_dump($renge_low);
            return false;
        }
    }

    private function buyConin()
    {
        $rgParams['order_currency'] = $this->coin_type;
        $rgParams['payment_currency'] = 'KRW';
        $account = $this->api->xcoinApiCall($this->account_path);
        $current_krw = $account->data->available_krw;

        if ($current_krw < 1500 ) {
		echo '예산 부족';            
		return false;
        }

        $using_krw = $current_krw / 7;

        if ($using_krw < 2000) {
            $using_krw = $current_krw;
        }
        $using_krw = $using_krw / $this->current_price;
echo $current_krw;
if (round($using_krw, 4) < 0.001) {
echo $current_krw / $this->current_price;
$using_krw = $current_krw / $this->current_price;
}
        $trade_params = array(
            'units' => round($using_krw, 4),
            'currency' => $this->coin_type
        );
var_dump($trade_params);
        $trade_info = $this->api->xcoinApiCall($this->trade_path, $trade_params);
var_dump($trade_info);
        /*
         * 지갑을 확인하고
         * 현재금액의 5% 구입
         * 디비에 정보를 쌓는다
         * 디비 구조는
         *
          `buy_result_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
          `cont_id` mediumint(20) NOT NULL,
          `units` double DEFAULT NULL,
          `price` double DEFAULT NULL,
          `total` double DEFAULT NULL,
          `fee` double DEFAULT NULL,
          `sell` double DEFAULT NULL,
          `coin_type` varchar(32) DEFAULT NULL,
          `transaction` mediumint(1) DEFAULT NULL, 판매유무
          `registered_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`buy_result_id`) USING BTREE
         */
        foreach ($trade_info->data as $info) {
            $value = array(
                $info->units,
                $info->price,
                $info->total,
                $info->fee,
                '"'.$this->coin_type.'"',
                0,
            );
            $sql = 'insert into buy_result (units, price, total, fee, coin_type, transaction)
                VALUES (' . implode($value, ',') . ')';
var_dump($sql);
            var_dump($this->db->query($sql));
        }
	echo '구매완료';
    }
}
