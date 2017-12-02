<?php

require_once MAX_PATH . '/handle/xcoin_api_client.php';
require_once MAX_PATH . '/service/CoinStatus.php';

class SellController
{
    private $coin_type;
    private $db;
    private $api;
    private $current_price_path = '/public/ticker/';
    private $sell_path = '/trade/market_sell';
    private $coin_status;

    public function __construct($coin_type)
    {
        $this->db = new mysqli($GLOBALS['database_host'], $GLOBALS['database_user'], $GLOBALS['database_password'], $GLOBALS['database_name']);
        $this->coin_type = $coin_type;
        $this->api = new XCoinAPI();
        $this->coin_status = new CoinStatus($coin_type);
    }

    public function sell()
    {
        $sql = 'select * from buy_result where transaction = 0 and coin_type = "'.$this->coin_type.'"';

        $no_sell_data = $this->db->query($sql)->fetch_all();

        $current_coin_call = $trade_info = $this->api->xcoinApiCall($this->current_price_path.$this->coin_type);
        $current_coin_info = $current_coin_call->data;
        $current_coin_price = $current_coin_info->sell_price;

        var_dump($no_sell_data);

        foreach ($no_sell_data as $data) {
            $price = $data[2];
            $units = round($data[1] - $data[4], 4);
            $buy_result_id = $data[0];

            $param = array(
                'units' => $units,
                'currency' => $this->coin_type
            );

            if ($GLOBALS['cut_stop_motion'] === true) {
                if ($current_coin_price >= $price * 0.99) {
                    $this->stop_motion($param, $buy_result_id);
                }
            }

            if ($current_coin_price > $price * $GLOBALS['sell_fee']) {
                if ($this->coin_status->isStartedUpStatus() && $this->coin_status->isStartedUpStatusFromVolume()) {
                    echo '상승장 시작!!!!';
                    continue;
                }

                if ($this->coin_status->isAlreadyUpStatus() && $this->coin_status->isStartedDropStatus()
                    && $this->coin_status->isAlreadyUpStatusFromVolume()) {
                    echo '상승중!!!!';
                    continue;
                }

                var_dump($param);

                $this->sell_coin($param, $buy_result_id);
                echo $data[0]. " 판매완료\n";
            }
        }
    }

    private function stop_motion($param, $buy_result_id)
    {
        if ($this->coin_status->isAlreadyDropStatus() &&
            $this->coin_status->isStartedUpStatus()) {
            echo "하락장!! 반등 구간!! 손절을 시작합니다.\n\n";
            $this->sell_coin($param, $buy_result_id);
        }
    }

    private function sell_coin($param, $buy_result_id)
    {
        $sell_info = $trade_info = $this->api->xcoinApiCall($this->sell_path, $param);

        var_dump($sell_info);

        foreach ($sell_info->data as $info) {

            $value = array(
                $info->units,
                $info->price,
                $info->total,
                $info->fee,
                '"' . $this->coin_type . '"',
            );
            $sql = 'insert into sell_result (units, price, total, fee, coin_type)
                            VALUES (' . implode($value, ',') . ')';
            $this->db->query($sql);

            $sql = 'update buy_result set transaction = 1 where buy_result_id = ' . $buy_result_id;
            $this->db->query($sql);
        }
    }
}
