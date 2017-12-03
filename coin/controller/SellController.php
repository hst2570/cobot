<?php

require_once MAX_PATH . '/handle/xcoin_api_client.php';
require_once MAX_PATH . '/handle/Telegram.php';
require_once MAX_PATH . '/service/CoinStatus.php';

class SellController
{
    private $coin_type;
    private $db;
    private $api;
    private $current_price_path = '/public/ticker/';
    private $sell_path = '/trade/market_sell';
    private $coin_status;
    private $monitoring_telegram;
    private $sell_complete_status_message;

    public function __construct($coin_type)
    {
        $this->db = new mysqli($GLOBALS['database_host'], $GLOBALS['database_user'], $GLOBALS['database_password'], $GLOBALS['database_name']);
        $this->coin_type = $coin_type;
        $this->api = new XCoinAPI();
        $this->coin_status = new CoinStatus($coin_type);
        $this->monitoring_telegram = new Telegram($GLOBALS['BOT_TOKEN'], $GLOBALS['_TELEGRAM_CHAT_ID']);
    }

    public function sell()
    {
        $sql = 'select * from buy_result where transaction = 0 and coin_type = "'.$this->coin_type.'"';

        $no_sell_data = $this->db->query($sql)->fetch_all();

        $current_coin_call = $trade_info = $this->api->xcoinApiCall($this->current_price_path.$this->coin_type);
        $current_coin_info = $current_coin_call->data;
        $current_coin_price = $current_coin_info->sell_price;

        foreach ($no_sell_data as $data) {
            $price = $data[2];
            $units = round($data[1] - $data[4], 4);
            $buy_result_id = $data[0];

            $min_units = array(
                'BTC' => 0.001,
                'XRP' => 10,
                'ETH' => 0.01,
                'ETC' => 0.1,
                'LTC' => 0.1,
                'DASH' => 0.01,
            );

            if ($units < $min_units[$this->coin_type]) {
                echo "판매 코인수가 적다.\n\n";
                $this->change_status_low_unit($buy_result_id, 2);
                continue;
            }

            $param = array(
                'units' => $units,
                'currency' => $this->coin_type
            );

            if ($GLOBALS['cut_stop_motion'] === true) {
                if ($current_coin_price >= $price ) {
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
                $this->sell_complete_status_message = "정상 판매";
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
            $this->sell_complete_status_message = "하락장!! 반등 구간!! 손절을 시작합니다.";
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

            $this->change_status_low_unit($buy_result_id, 1);

            $message = "
                타입 : 판매\n
                상태 : ".$this->sell_complete_status_message."\n
                구매ID : ".$buy_result_id."\n
                판매코인 : ".$this->coin_type."\n
                금액 : ".$info->price."\n
                갯수 : ".$info->units."\n
                총합 : ".$info->total."\n
                수수료 : ".$info->fee."\n
            ";

            $this->monitoring_telegram->telegramApiRequest("sendMessage", $message);
        }
    }

    private function change_status_low_unit($buy_result_id, $transaction)
    {
        $sql = 'update buy_result set transaction = '.$transaction.' where buy_result_id = ' . $buy_result_id;

        $this->db->query($sql);
    }
}
