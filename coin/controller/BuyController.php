<?php

class BuyController
{
    private $coin_type;
    private $db;
    private $api;
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
        if ($this->condition()) {
            echo 'get btc';
            $sql = 'insert into test (test) VALUES (1)';
            $this->db->query($sql);
        } else {
            echo 'not chance';
        }
    }

    private function condition()
    {
        $sql = 'select * from traded_info where coin_type = "BTC" order by transaction_date asc';

        $result = $this->db->query($sql)->fetch_all();
        $cutline = intval(sizeof($result) / 25);
        $lineData = array();

        for ($i = 0 ; $i < sizeof($result) ; $i++) {
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
                $sum = $sum + $item[4];
            }
            array_push($average, $sum / sizeof($items));
            $sum = 0;
        }

        $low = 99999999999;
        $lowLoc = 0;
        $high = 0;
        $highLoc = 0;
        $currentPrice = $result[sizeof($result)][4];

        for ($i = 0 ; $i < sizeof($average) ; $i++) {
            if ($average[$i] < $low) {
                $low = $average[$i];
                $lowLoc = $i;
            }
            if ($average[$i] > $high) {
                $high = $average[$i];
                $highLoc = $i;
            }
        }

        if ($high > $low * 1.06) {
            return false;
        }

        if ($high > $currentPrice * 1.06) {
            return false;
        }

        $is_high = $high > $currentPrice * 1.005;
        $renge_high = $highLoc - 25 > 3;
        $is_low = $low < $currentPrice;
        $renge_low = $lowLoc - 25 > 3;

        if ($is_high && $is_low && $renge_high &&$renge_low) {
            return true;
        } else {
            return false;
        }
    }
}
