<?php

require_once MAX_PATH . '/handle/xcoin_api_client.php';
require_once MAX_PATH . '/handle/Telegram.php';
require_once MAX_PATH . '/service/CoinStatus.php';

class StatsController
{
    private $db;
    private $api;
    private $monitoring_telegram;

    public function __construct()
    {
        $this->db = new mysqli($GLOBALS['database_host'], $GLOBALS['database_user'], $GLOBALS['database_password'], $GLOBALS['database_name']);
        $this->api = new XCoinAPI();
        $this->monitoring_telegram = new Telegram($GLOBALS['BOT_TOKEN'], $GLOBALS['_TELEGRAM_CHAT_ID']);
    }

    private function daily_stats()
    {
        date_default_timezone_set('UTC');
        $date = time();
        $date = date('Y-m-d H:i:s', $date);
        $message = "## Daily Stats Start ##\n";

        $sql = "select
          s.coin_type,
          avg(b.price) as buy_price,
          avg(s.price) as sell_price,
          sum(b.units) as buy_units,
          sum(s.units) as sell_units,
          sum(b.fee) as buy_fee,
          sum(s.fee) as sell_fee,
          sum(b.total) as buy_total,
          sum(s.total) as sell_total
           from sell_result as s
           inner join buy_result as b on s.coin_type = b.coin_type
          where s.registered_time > CONVERT_TZ('".$date."', '+09:00', '+00:00')
          and b.registered_time > CONVERT_TZ('".$date."', '+09:00', '+00:00')
          group by coin_type";

        $daily_result = $this->db->query($sql)->fetch_all();

        $buy_total = 0;
        $sell_total = 0;

        foreach ($daily_result as $result) {
            $message = $message. "* 코인 타입: <". $result[0] .">\n";
            $message = $message. "* 평균 구매가: ". $result[1] ."\n";
            $message = $message. "* 평균 판매가: ". $result[2] ."\n";
            $message = $message. "* 총 구매갯수: ". $result[3] ."\n";
            $message = $message. "* 총 판매갯수: ". $result[4] ."\n";
            $message = $message. "* 총 구매수수료: ". $result[5] ."\n";
            $message = $message. "* 총 판매수수료: ". $result[6] ."\n";
            $message = $message. "* 총 구매액: ". $result[7] ."\n";
            $message = $message. "* 총 판매액: ". $result[8] ."\n";
            $message = $message. "---------------------------------\n\n";

            $buy_total = $buy_total + $result[5];
            $sell_total = $sell_total + $result[6];
        }
        $message = $message. "총 매수액: ". $buy_total ."\n";
        $message = $message. "총 매도액: ". $sell_total. "\n\n";
        $message = $message. "\n";

        return $message;
    }

    public function total_stats()
    {
        $message = $this->daily_stats();
        date_default_timezone_set('UTC');
        $message = $message. "## 총 Stats Start ##\n";

        $sql = "select
          s.coin_type,
          avg(b.price) as buy_price,
          avg(s.price) as sell_price,
          sum(b.units) as buy_units,
          sum(s.units) as sell_units,
          sum(b.fee) as buy_fee,
          sum(s.fee) as sell_fee,
          sum(b.total) as buy_total,
          sum(s.total) as sell_total
           from sell_result as s
           inner join buy_result as b on s.coin_type = b.coin_type
           where b.transaction = 1
          group by coin_type";

        $total_result = $this->db->query($sql)->fetch_all();

        $buy_total = 0;
        $sell_total = 0;

        foreach ($total_result as $result) {
            $message = $message. "* 코인 타입: <". $result[0] .">\n";
            $message = $message. "* 평균 구매가: ". $result[1] ."\n";
            $message = $message. "* 평균 판매가: ". $result[2] ."\n";
            $message = $message. "* 총 구매갯수: ". $result[3] ."\n";
            $message = $message. "* 총 판매갯수: ". $result[4] ."\n";
            $message = $message. "* 총 구매수수료: ". $result[5] ."\n";
            $message = $message. "* 총 판매수수료: ". $result[6] ."\n";
            $message = $message. "* 총 구매액: ". $result[7] ."\n";
            $message = $message. "* 총 판매액: ". $result[8] ."\n";
            $message = $message. "---------------------------------\n\n";

            $buy_total = $buy_total + $result[5];
            $sell_total = $sell_total + $result[6];
        }
        $message = $message. "총 매수액: ". $buy_total ."\n";
        $message = $message. "총 매도액: ". $sell_total ."\n";
        $message = $message. "총 차액: ". $sell_total - $buy_total. "\n\n";
        $message = $message. "\n";

        $message = $message. "## 미채결 거래내역 통계 ##\n";

        $sql = "select
          b.coin_type,
          avg(b.price) as buy_price,
          sum(b.units) as buy_units,
          sum(b.fee) as buy_fee,
          sum(b.total) as buy_total
           from buy_result as b
           where b.transaction = 0
          group by coin_type";

        $wait_buy = $this->db->query($sql)->fetch_all();

        foreach ($wait_buy as $result) {
            $message = $message. "* 코인 타입: <". $result[0] .">\n";
            $message = $message. "* 평균 구매액: ". $result[1] ."\n";
            $message = $message. "* 총 구매갯수: ". $result[2] ."\n";
            $message = $message. "* 총 구매수수료: ". $result[3] ."\n";
            $message = $message. "* 총 구매액: ". $result[4] ."\n";
            $message = $message. "---------------------------------\n\n";

            $buy_total = $buy_total + $result[4];
        }

        $message = $message. "총 미채결액: ". $buy_total;

        $this->monitoring_telegram->telegramApiRequest("sendMessage", $message);
    }
}