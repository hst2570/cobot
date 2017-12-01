<?php

require_once MAX_PATH . '/handle/xcoin_api_client.php';
require_once MAX_PATH . '/service/CoinStatus.php';

class BuyController
{
    private $coin_type;
    private $db;
    private $api;
    private $account_path = '/info/balance';
    private $trade_path = '/trade/market_buy';
    private $current_price = 0;
    private $coin_status;
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
        $this->coin_status = new CoinStatus($coin_type);
        $this->current_price = $this->coin_status->currentPrice();
    }

    public function getBuy()
    {
        if (!$this->condition()) {
            echo "\n\n not chance \n\n";
        } else {
            $this->buyConin();
        }
    }

    private function condition()
    {
        $average = $this->coin_status->getAverageData();

        $low = 99999999999;
        $lowLoc = 0;
        $high = 0;
        $highLoc = 0;

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

        $is_high = $high > $this->current_price * $GLOBALS['buy_fee'];
        $renge_high = $GLOBALS['data_div_count'] - $highLoc > 2;
        $already_low = $this->coin_status->isAlreadyDropStatus();
        $is_low_average_value = ($high + $low) / 2 > $this->current_price * 0.99;

        echo '최고가: '.$high. "\n";
        echo '최저가: '.$low. "\n";
        echo '현재전가: '.$average[count($average)-1] . "\n";
        echo '현재전전가: '.$average[count($average)-2] . "\n";
        echo '현재가: '.$this->current_price. "\n";
        $high_loc_current = $GLOBALS['data_div_count'] - $highLoc;
        echo '현재가와 최고가 틱차이: '.$high_loc_current. "\n";
        $low_loc_current = $GLOBALS['data_div_count'] - $lowLoc;
        echo '현재가와 최저가 틱차이: '.$low_loc_current. "\n";
        echo '최고가 최저가 평균: '.($high + $low) / 2;
        echo "\n\n";

        echo "최고가인가?";
        var_dump($is_high);
        echo "최고가와 시간차가 좀 나야한다";
        var_dump($renge_high);
        echo "여전히 떨어지고 있는가?";
        var_dump($already_low);
        echo "최고 최저 평균보다 현재값이 낮아야한다";
        var_dump($is_low_average_value);

        if ($is_high && $renge_high && !$already_low && $is_low_average_value) {
            if ($high > $low * $GLOBALS['is_very_drop_per']) {
                echo $high. "\n";
                echo $low. "\n";
                echo "폭락\n\n";
                return false;
            }

            if ($high > $this->current_price * $GLOBALS['is_very_drop_per']) {
                echo $high. "\n";
                echo $this->current_price. "\n";
                echo "폭락22\n\n";
                return false;
            }
            return true;
        } else {
            return false;
        }
    }

    private function buyConin()
    {
        $rgParams['order_currency'] = $this->coin_type;
        $rgParams['payment_currency'] = 'KRW';
        $account = $this->api->xcoinApiCall($this->account_path);
        $current_krw = $account->data->available_krw;

        $using_krw = $current_krw / $GLOBALS['budget_div'];

        if ($current_krw < 15000 ) {
            $using_krw = $current_krw;
        }

        $using_krw = $using_krw / $this->current_price;

        echo $current_krw;

        $min_units = array(
            'BTC' => 0.001,
            'XRP' => 10,
            'ETH' => 0.1,
            'ETC' => 0.1,
            'LTC' => 0.1,
        );

        if (round($using_krw, 4) < $min_units[$this->coin_type]) {
            echo $current_krw / $this->current_price;
            $using_krw = $current_krw / $this->current_price;
        }

        $sql = 'select registered_time from buy_result where coin_type = "'.$this->coin_type.'" order by registered_time desc limit 1';
                $last_register = $this->db->query($sql)->fetch_all();
        var_dump($last_register);
        var_dump(strtotime($last_register[0][0]) + $GLOBALS['buy_term'], time());

        $register_time = strtotime($last_register[0][0]);
        if ($register_time + $GLOBALS['buy_term'] > time()) {
            echo '최근 구매';
            return false;
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

            var_dump($this->db->query($sql));
        }
	    echo '구매완료';
    }
}
