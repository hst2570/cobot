<?php

require_once MAX_PATH . '/handle/xcoin_api_client.php';
require_once MAX_PATH . '/controller/BuyController.php';

class SellController
{
    private $coin_type;
    private $db;
    private $api;
    private $current_price_path = '/public/ticker/';
    private $sell_path = '/trade/market_sell';

    public function __construct($coin_type)
    {
        $this->db = new mysqli($GLOBALS['database_host'], $GLOBALS['database_user'], $GLOBALS['database_password'], $GLOBALS['database_name']);
        $this->coin_type = $coin_type;
        $this->api = new XCoinAPI();
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
            if ($current_coin_price > $data[2] * $GLOBALS['sell_fee']) {
                $buy = new BuyController($this->coin_type);
                $average = $buy->getAverageData();

                if ($average[count($average) - 1] < $current_coin_price) {
                    echo '상승중!!!!';
                    continue;
                }

                $param = array(
                    'units' => round($data[1] - $data[4], 4),
                    'currency' => $this->coin_type
                );
                var_dump($param);

                $sell_info = $trade_info = $this->api->xcoinApiCall($this->sell_path, $param);

                var_dump($sell_info);
                foreach ($sell_info->data as $info) {
                    /*
                     * sell_result | CREATE TABLE `sell_result` (
                      `sell_result_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
                      `cont_id` mediumint(20) NOT NULL,
                      `units` double DEFAULT NULL,
                      `price` double DEFAULT NULL,
                      `total` double DEFAULT NULL,
                      `fee` double DEFAULT NULL,
                      `sell` double DEFAULT NULL,
                      `coin_type` varchar(32) DEFAULT NULL,
                      `registered_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      PRIMARY KEY (`sell_result_id`) USING BTREE
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1 |
                     */
                    $value = array(
                        $info->units,
                        $info->price,
                        $info->total,
                        $info->fee,
                        '"'.$this->coin_type.'"',
                    );
                    $sql = 'insert into sell_result (units, price, total, fee, coin_type)
                            VALUES (' . implode($value, ',') . ')';
                    $this->db->query($sql);

                    $sql = 'update buy_result set transaction = 1 where buy_result_id = '.$data[0];
                    $this->db->query($sql);
                }
                echo $data[0]. " 판매완료\n";
            }
        }
    }
}
