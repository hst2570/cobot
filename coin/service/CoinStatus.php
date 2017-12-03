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
        date_default_timezone_set('UTC');
        $date = strtotime('-'.$GLOBALS['get_data_by_hour'].' hour');
        $date = date('Y-m-d H:i:s', $date);

        $sql = 'select * from traded_info where coin_type = "'.$this->coin_type.'" 
         and registered_time > "'. $date .'"
         order by transaction_date asc';

        return $this->db->query($sql)->fetch_all();
    }

    private function getMaximumPrice()
    {
        $sql = 'select * from traded_info where coin_type = "'.$this->coin_type.'" 
         order by price desc limit 1';

        return $this->db->query($sql)->fetch_all();
    }

    private function getMinimumPrice()
    {
        $sql = 'select * from traded_info where coin_type = "'.$this->coin_type.'" 
         order by price asc limit 1';

        return $this->db->query($sql)->fetch_all();
    }

    private function getAll()
    {
        $sql = 'select * from traded_info where coin_type = "'.$this->coin_type.'" 
         order by transaction_date asc';

        return $this->db->query($sql)->fetch_all();
    }

    public function getAverageData()
    {
        $average = array();
        $result = $this->result;

        $this->current_price = $result[sizeof($result)-1][3];

        $cutline = intval(sizeof($result) / $GLOBALS['data_div_count']);
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

    public function getAverageTradedCount()
    {
        $result = $this->result;

        $cutline = intval(sizeof($result) / $GLOBALS['data_div_count']);
        $lineData = array();

        $buyCount = 0;
        $sellCount = 0;

        for ($i = 0 ; $i < sizeof($result)-1 ; $i++) {
            if ($result[$i]['1'] === 'ask') {
                $sellCount++;
            } else if ($result[$i]['1'] === 'bid'){
                $buyCount++;
            } else {
                echo "잘못된 거래 타입 \n\n";
            }
            if ($i % $cutline === 0) {
                array_push($lineData, $buyCount-$sellCount);
                $buyCount = 0;
                $sellCount = 0;
            }
        }

        return $lineData;
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

        if ($already_low > sizeof($average) * $GLOBALS['is_drop_status_value']) {
            echo "하락장!!! \n";
            return true;
        }

        return false;
    }

    public function isAlreadyDropStatusFromVolume()
    {
        $volume = $this->getAverageTradedCount();

        $volume_low = 0;

        for ($i = 0 ; $i < sizeof($volume) - 1 ; $i++) {
            if ($volume[$i] > $volume[$i + 1]) {
                $volume_low++;
            }
        }

        if ($volume_low * $GLOBALS['is_drop_status_volume_value']) {
            echo "볼륨 하락장!!! \n";
            return true;
        }

        return false;
    }

    public function isStartedDropStatus()
    {
        $average = $this->getAverageData();

        $current_prices_size = intval(sizeof($average) * $GLOBALS['is_stated_drop']);
        $step_drop_status = 0;

        for ($i = $current_prices_size ; $i < sizeof($average)-1 ; $i++) {
            if ($average[$current_prices_size] > $average[$current_prices_size + 1] * 1.002) {
                $step_drop_status++;
            }
        }

        $already_step_drop_status = (($average[sizeof($average)-2] * 1.005 > $this->current_price &&
                    $average[sizeof($average)-1] * 1.005 > $this->current_price)
                && ($average[sizeof($average)-2] * 0.995 < $this->current_price &&
                    $average[sizeof($average)-1] * 0.995 < $this->current_price))
            || (($average[sizeof($average)-2] * 1.005 > $this->current_price &&
                    $average[sizeof($average)-1] * 0.995 > $this->current_price)
                && ($average[sizeof($average)-2] * 0.995 > $this->current_price &&
                    $average[sizeof($average)-1] * 1.005 > $this->current_price));

        if ($step_drop_status >$current_prices_size * 0.85 && $already_step_drop_status) {
            echo "하락장 초입 예상\n\n";
            return true;
        }
        return false;
    }

    public function isStartedDropStatusFromVolume()
    {
        $volume = $this->getAverageTradedCount();
        $current_prices_size = intval(sizeof($volume) * $GLOBALS['is_stated_drop']);
        $volume_low = 0;

        for ($i = $current_prices_size ; $i < sizeof($volume) - 1 ; $i++) {
            if ($volume[$i] > $volume[$i + 1]) {
                $volume_low++;
            }
        }

        if ($volume_low * $GLOBALS['is_drop_status_volume_value']) {
            echo "최근 볼륨 하락 시작!!! \n";
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

        if ($already_high > sizeof($average) * $GLOBALS['is_high_status_value']) {
            echo "상승장!!! \n";
            return true;
        }

        return false;
    }

    public function isAlreadyUpStatusFromVolume()
    {
        $volume = $this->getAverageTradedCount();

        $volume_low = 0;

        for ($i = 0 ; $i < sizeof($volume) - 1 ; $i++) {
            if ($volume[$i] < $volume[$i + 1]) {
                $volume_low++;
            }
        }

        if ($volume_low * $GLOBALS['is_up_status_volume_value']) {
            echo "볼륨 상승장!!! \n";
            return true;
        }

        return false;
    }

    public function isStartedUpStatus()
    {
        $average = $this->getAverageData();

        $current_prices_size = intval(sizeof($average) * $GLOBALS['is_stated_up']);
        $step_drop_status = 0;

        for ($i = $current_prices_size ; $i < sizeof($average)-1 ; $i++) {
            if ($average[$current_prices_size] * 1.0015 < $average[$current_prices_size + 1]) {
                $step_drop_status++;
            }
        }

        if ($step_drop_status >$current_prices_size * 0.90) {
            echo "상승장 초입 예상\n\n";
            return true;
        }
        return false;
    }

    public function isStartedUpStatusFromVolume()
    {
        $volume = $this->getAverageTradedCount();
        $current_prices_size = intval(sizeof($volume) * $GLOBALS['is_stated_up']);
        $volume_low = 0;

        for ($i = $current_prices_size ; $i < sizeof($volume) - 1 ; $i++) {
            if ($volume[$i] > $volume[$i + 1]) {
                $volume_low++;
            }
        }

        if ($volume_low * $GLOBALS['is_up_status_volume_value']) {
            echo "최근 볼륨 상승 시작!!! \n";
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
