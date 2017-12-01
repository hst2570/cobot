<?php
/**
 * Created by PhpStorm.
 * User: hwangseongtae
 * Date: 2017. 11. 30.
 * Time: PM 9:24
 */

class CoinStatus
{
    private $coin_type;
    private $db;
    private $api;
    private $current_price = 0;
    private $result;

    public function __construct($coin_type)
    {
        $this->db = new mysqli($GLOBALS['database_host'], $GLOBALS['database_user'], $GLOBALS['database_password'], $GLOBALS['database_name']);
        $this->coin_type = $coin_type;
        $this->api = new XCoinAPI();
        $this->result = $this->getTraded();
    }

    private function getTraded()
    {
        $sql = 'select * from traded_info where coin_type = "'.$this->coin_type.'" order by transaction_date asc';

        return $this->db->query($sql)->fetch_all();
    }

    public function getAverageData()
    {
        $average = array();
        $result = $this->result;

        $this->current_price = $result[sizeof($result)-1][3];

        $cutline = intval(sizeof($result) / 18);
        $lineData = array();
        $tmpArr = array();

        for ($i = 0 ; $i < sizeof($result)-1 ; $i++) {
            array_push($tmpArr, $result[$i]);
            if ($i % $cutline === 0) {
                array_push($lineData, $tmpArr);
                $tmpArr = array();
            }
        }
        $sum = 0;

        foreach ($lineData as $key => $items) {
            foreach ($items as $item) {
                $sum = $sum + $item[3];
            }
            array_push($average, $sum / sizeof($items));
            $sum = 0;
        }

        return $average;
    }

    public function isAlreadyDropStatus()
    {
        $average = $this->getAverageData();
        $already_low = 0;

        for ($i = 0 ; $i < sizeof($average) - 1 ; $i++) {
            if ($average[$i] > $average[$i + 1]) {
                $already_low++;
            }
        }

        $already_step_drop_status = (($average[count($average)-2] * 1.005 > $this->current_price &&
                $average[count($average)-1] * 1.005 > $this->current_price)
            && ($average[count($average)-2] * 0.995 < $this->current_price &&
                $average[count($average)-1] * 0.995 < $this->current_price))
        || (($average[count($average)-2] * 1.005 > $this->current_price &&
                $average[count($average)-1] * 0.995 > $this->current_price)
            && ($average[count($average)-2] * 0.995 > $this->current_price &&
                $average[count($average)-1] * 1.005 > $this->current_price));


        if ($already_low > sizeof($average) * $GLOBALS['is_drop_status_value'] && $already_step_drop_status) {

            echo "하락장!!! \n";
            return true;
        }

        return false;
    }

    public function isStartedDropStatus()
    {
        $average = $this->getAverageData();

        $current_prices_size = intval(sizeof($average) * 0.70);
        $step_drop_status = 0;

        for ($i = $current_prices_size ; $i < sizeof($average)-1 ; $i++) {
            if ($average[$current_prices_size] > $average[$current_prices_size + 1] * 1.002) {
                $step_drop_status++;
            }
        }

        if ($step_drop_status >$current_prices_size * 0.80) {
            echo "하락장 초입 예상\n\n";
            return true;
        }
        return false;
    }

    public function isAlreadyUpStatus()
    {
        $average = $this->getAverageData();
        $already_high = 0;

        for ($i = 0 ; $i < sizeof($average) - 1 ; $i++) {
            if ($average[$i] < $average[$i + 1]) {
                $already_high++;
            }
        }

        $already_high_status = ($average[count($average)-2] * 1.005 < $this->current_price &&
                    $average[count($average)-1] * 1.005 < $this->current_price);

        if ($already_high > sizeof($average) * $GLOBALS['is_high_status_value'] && $already_high_status) {
            echo "상승장!!! \n";
            return true;
        }

        return false;
    }

    public function isStartedUpStatus()
    {
        $average = $this->getAverageData();

        $current_prices_size = intval(sizeof($average) * 0.85);
        $step_drop_status = 0;

        for ($i = $current_prices_size ; $i < sizeof($average)-1 ; $i++) {
            if ($average[$current_prices_size] * 1.0015 < $average[$current_prices_size + 1]) {
                $step_drop_status++;
            }
        }

        if ($step_drop_status >$current_prices_size * 0.80) {
            echo "상승장 초입 예상\n\n";
            return true;
        }
        return false;
    }

    public function currentPrice()
    {
        $result = $this->result;

        $this->current_price = $result[sizeof($result)-1][3];
        return $this->current_price;
    }
}