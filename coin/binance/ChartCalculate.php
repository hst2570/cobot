<?php

require_once MAX_PATH . '/coin/binance/ApiCall.php';

class ChartCalculate
{
    private $api_call;
    private $r_high;
    private $r_row;
    private $avg_cci;
    private $rsi;
    private $avg_move;
    private $avg_price;

    public function __construct()
    {
        $this->api_call = new ApiCall();
    }

    public function setCoin($interval, $symbol)
    {
        $this->calculate($interval, $symbol);
    }

    private function calculate($interval, $symbol)
    {
        $now = $this->api_call->test_time();
        $now = json_decode($now, true);
        $now = $now['serverTime'];
        $now = preg_replace('/([0-9]{10}).*/', '$1', $now);
        $second = 60;
        $hour = $second * 60;
        $day = $hour * 24;

        if ($interval === '1m') {
            $time = $now - ($hour * 3);
        } else if ($interval === '15m') {
            $time = $now - ($day * 2);
        } else if ($interval === '1h') {
            $time = $now - ($day * 10);
        } else if ($interval === '1day') {
            $time = $now - ($day * 60);
        } else {
            return false;
        }

        $candle_data = $this->api_call->test_candlestick([
            'symbol' => $symbol,
            'interval' => $interval,
            'startTime' => $time.'000',
            'endTime' => $now.'000',
        ]);
        $avg_move = [];
        $avg_cci = [];
        $standard_day = 15;
        $rsi = [];
        $down = [];
        $up = [];
        $r_high = [];
        $r_row = [];
        $avg_price = [];
        $satoshi = 100000000;
        $volumes = [];

        foreach ($candle_data as $data) {
            $high = (float) $data[2] * $satoshi;
            $row = (float) $data[3] * $satoshi;
            $close = (float) $data[4] * $satoshi;

            $avg_price[] = ($row + $high + $close) / 3;
            $size = sizeof($avg_price) - 1;
            if ($size > 1) {
                if ($avg_price[$size - 1] > $avg_price[$size]) {
                    $down[] = $avg_price[$size - 1] - $avg_price[$size];
                    $up[] = 0;
                } else {
                    $up[] = $avg_price[$size] - $avg_price[$size - 1];
                    $down[] = 0;
                }
            }

            if (sizeof($avg_price) >= $standard_day) {
                $avg_move[] = round($this->sum($avg_price) / $standard_day, 8);
                $avg_cci[] = $avg_price[$size] - $avg_move[sizeof($avg_move) - 1];

                $up_avg = $this->sum($up) / $standard_day;
                $down_avg = $this->sum($down) / $standard_day;
                if ($up_avg === 0) {
                    $rsi[] = 0;
                } else {
                    $rsi[] = $up_avg / ($up_avg + $down_avg);
                }

                $r_high[] = $high - $avg_move[sizeof($avg_move) - 1];
                $r_row[] = $row - $avg_move[sizeof($avg_move) - 1];
                $volumes[] = $data[5];
            }
        }
        $this->r_high = $r_high;
        $this->r_row = $r_row;
        $this->avg_cci = $avg_cci;
        $this->rsi = $rsi;
        $this->avg_move = $avg_move;
        $this->avg_price = $avg_price;
    }

    private function sum($avg_price)
    {
        $sum = 0;
        $size = sizeof($avg_price) - 1;
        $len = $size - 15;
        if ($size < 15) {
            $len = 0;
        }
        for ($i = $size ; $i > $len ; $i--) {
            $sum = $sum + $avg_price[$i];
        }
        return $sum;
    }

    /**
     * @return mixed
     */
    public function getRHigh()
    {
        return $this->r_high;
    }

    /**
     * @return mixed
     */
    public function getRRow()
    {
        return $this->r_row;
    }

    /**
     * @return mixed
     */
    public function getAvgCci()
    {
        return $this->avg_cci;
    }

    /**
     * @return mixed
     */
    public function getRsi()
    {
        return $this->rsi;
    }

    /**
     * @return mixed
     */
    public function getAvgMove()
    {
        return $this->avg_move;
    }

    /**
     * @return mixed
     */
    public function getAvgPrice()
    {
        return $this->avg_price;
    }

    public function is_buy($avgMove, $avgPrice, $rHigh, $rRow, $rsi, $cci)
    {
        /*
         * RSI 가 낮으면 매수 / 높으면 매도
         * CCI가 낮으면 매수 / 높으면 매도
         *
         * 매수 타이밍 정리
         * 4시간 1시간 15분 평균 RSI 가 35이하
         * 1시간 15분 평균 RSI 가 30이하
         * 15분 RSI 가 25이하
         * 4시간 1시간 15분 평균 CCI 가 LOW 80이하
         * 1시간 15분 평균 CCI 가 LOW 100이하
         * 15분 평균 CCI 가 LOW 120이하
         * 4시간 1시간 15분 중 2개 이상의 하이와 로우값의 차가 점점 작아질 때
         * 평균값 - row 보다 낮은 가격일 때 구매
         */

        $rsi_4h_avg = $this->avg($rsi['4h']);
        $rsi_4h = $rsi['4h'][sizeof($rsi['4h']) - 1];

        $rsi_1h_avg = $this->avg($rsi['1h']);
        $rsi_1h = $rsi['1h'][sizeof($rsi['1h']) - 1];

        $rsi_15m_avg = $this->avg($rsi['15m']);
        $rsi_15m = $rsi['15m'][sizeof($rsi['15m']) - 1];


        $cci_4h_avg = $this->avg($cci['4h']);
        $cci_4h = $cci['4h'][sizeof($cci['4h']) - 1];

        $cci_1h_avg = $this->avg($cci['1h']);
        $cci_1h = $cci['1h'][sizeof($cci['1h']) - 1];

        $cci_15m_avg = $this->avg($cci['15m']);
        $cci_15m = $cci['15m'][sizeof($cci['15m']) - 1];

        $bolland = $this->bolland($rHigh, $rRow);
        $size_of_avg_price = sizeof($avgPrice) - 1;

        for ($i = sizeof($bolland) - 1 ; $i > sizeof($bolland) - 10 ; $i--) {
            if ($i < 0) {
                break;
            }
            if (!$avgPrice[$size_of_avg_price] * 0.13 < $bolland[$i]) {
                echo "NOT 행보 중\n";
                return false;
            }
        }

        if ($rsi_4h_avg < $rsi_4h &&
            $rsi_1h_avg * 0.50 > $rsi_1h &&
            $rsi_15m_avg * 0.30 > $rsi_15m) {
            echo "1-1 pass\n";
        } else if ($rsi_1h_avg * 0.40 > $rsi_1h &&
            $rsi_15m_avg * 0.30 > $rsi_15m) {
            echo "1-2 pass\n";
        } else if ($rsi_15m_avg * 0.25 > $rsi_15m) {
            echo "1-3 pass\n";
        } else {
            echo "1 break\n";
            return false;
        }

        if ($cci_4h_avg < $cci_4h &&
            $cci_1h_avg * 0.50 > $cci_1h &&
            $cci_15m_avg * 0.30 > $cci_15m) {
            echo "2-1 pass\n";
        } else if ($cci_1h_avg * 0.40 > $cci_1h &&
            $cci_15m_avg * 0.30 > $cci_15m) {
            echo "2-2 pass\n";
        } else if ($cci_15m_avg * 0.25 > $cci_15m) {
            echo "2-3 pass\n";
        } else {
            echo "2 break\n";
            return false;
        }

        return true;
    }

    private function avg($array)
    {
        $sum = 0;
        foreach ($array as $a) {
            $sum = $sum + $a;
        }
        return $sum / sizeof($array);
    }

    private function bolland($rHigh, $rRow)
    {
        $size = sizeof($rHigh) - 1;
        $band = [];

        for ($i = $size ; $i > 0 ; $i--) {
            if (isset($rHigh[$i]) && isset($rRow[$i])) {
                $band[] = $rHigh[$i] - $rRow[$i];
            }
        }

        return $band;
    }
}