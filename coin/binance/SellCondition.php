<?php

require_once MAX_PATH . '/coin/binance/ApiCall.php';
require_once MAX_PATH . '/coin/binance/ChartCalculate.php';

class SellCondition
{
    private $chartCalculate;

    public function __construct()
    {
        $this->chartCalculate = new ChartCalculate();
    }

    public function is_sell($symbol)
    {
        $this->chartCalculate->setCoin('15m', $symbol);
        $cci = $this->chartCalculate->getAvgCci();
        $rsi = $this->chartCalculate->getRsi();

        if ($cci[sizeof($cci) - 1] > $this->chartCalculate->avg($cci)) {
            return true;
        }

        if ($rsi[sizeof($rsi) - 1] > $this->chartCalculate->avg($rsi)) {
            return true;
        }

        return false;
    }
}