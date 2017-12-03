<?php

require_once MAX_PATH . '/handle/xcoin_api_client.php';
require_once MAX_PATH . '/handle/Telegram.php';
require_once MAX_PATH . '/service/CoinStatus.php';

class CoinInfoController
{
    private $coin_type;
    private $db;
    private $api;
    private $current_price = 0;
    private $coin_status;
    private $monitoring_telegram;

    public function __construct($coin_type)
    {
        $this->db = new mysqli($GLOBALS['database_host'], $GLOBALS['database_user'], $GLOBALS['database_password'], $GLOBALS['database_name']);
        $this->coin_type = $coin_type;
        $this->api = new XCoinAPI();
        $this->coin_status = new CoinStatus($coin_type);
        $this->current_price = $this->coin_status->currentPrice();
        $this->monitoring_telegram = new Telegram($GLOBALS['BOT_TOKEN'], $GLOBALS['TELEGRAM_GROUP_ID']);
    }

    public function check_coin_status()
    {
        $average = $this->coin_status->getAverageData();

        $low = 99999999999;
        $high = 0;

        $started_drop = $this->coin_status->isStartedDropStatus();
        $message = '';
        $message = $message. '코인타입: '.$this->coin_type. "\n";
        $message = $message. '최고가: '.$high. "\n";
        $message = $message. '최저가: '.$low. "\n";
        $message = $message. '현재전가: '.$average[count($average)-1] . "\n";
        $message = $message. '현재전전가: '.$average[count($average)-2] . "\n";
        $message = $message. '현재가: '.$this->current_price. "\n";
        $message = $message. '최고가 최저가 평균: '.($high + $low) / 2 . "\n";
        $message = $message. "하락장 인가: ". $this->coin_status->isAlreadyDropStatus(). "\n";
        $message = $message. "하락장 초입인가: ". $this->coin_status->isStartedDropStatus(). "\n\n";

        if ($started_drop) {
            $current_prices_size = intval(sizeof($average) * 0.70);
            $step_drop_status = 0;

            for ($i = $current_prices_size ; $i < sizeof($average)-1 ; $i++) {
                if ($average[$current_prices_size] > $average[$current_prices_size + 1] * 1.02) {
                    $step_drop_status++;
                }
            }
            if ($step_drop_status >$current_prices_size * 0.60) {
                $message = $message. "대하락장 시작 존버 또는 손절 요망!!!";
                $this->monitoring_telegram->telegramApiRequest("sendMessage", $message);
            }
        }

        if ($this->coin_status->isStartedUpStatusFromVolume()
            && !$this->coin_status->isStartedDropStatusFromVolume()
            && $started_drop) {
            if ($high > $low * $GLOBALS['is_very_drop_per']) {
                $message = $message. "폭락장... 존버 또는 손절 요망!!!";
                $this->monitoring_telegram->telegramApiRequest("sendMessage", $message);
                return false;
            }

            if ($high > $this->current_price * $GLOBALS['is_very_drop_per']) {
                $message = $message. "폭락장... 존버 또는 손절 요망!!!";
                $this->monitoring_telegram->telegramApiRequest("sendMessage", $message);
                return false;
            }
            $message = $message. "사봄직한 타이밍??";
            $this->monitoring_telegram->telegramApiRequest("sendMessage", $message);
            return true;
        } else if ($this->coin_status->isAlreadyUpStatus() && $this->coin_status->isStartedDropStatus()
            && $this->coin_status->isAlreadyUpStatusFromVolume()
            && $started_drop){
            $message = $message. "전체적인 상승장에 조정 기간 예측된다.";
            $this->monitoring_telegram->telegramApiRequest("sendMessage", $message);
            return true;
        } else if ($this->coin_status->isStartedUpStatus()
            && $this->coin_status->isStartedUpStatusFromVolume()
            && !$started_drop){
            $message = $message. "떡상이다. 탄다!!!! 가즈아!!!";
            $this->monitoring_telegram->telegramApiRequest("sendMessage", $message);
            return true;
        } else {
            return false;
        }
    }
}