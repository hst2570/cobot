<?php

require_once MAX_PATH . '/handle/xcoin_api_client.php';
require_once MAX_PATH . '/handle/Telegram.php';

class ApiCheckController
{
    private $api;
    private $monitoring_telegram;
    private $account_path = '/info/balance';

    public function __construct()
    {
        $this->api = new XCoinAPI();
        $this->monitoring_telegram = new Telegram($GLOBALS['BOT_TOKEN'], $GLOBALS['_TELEGRAM_CHAT_ID']);
    }

    public function check_api()
    {
        $this->monitoring_telegram->setGroupId($GLOBALS['_TELEGRAM_CHAT_ID']);

        $account = $this->api->xcoinApiCall($this->account_path);

        if (!isset($account->data->available_krw)) {
            $message = "!!!!!!!! API 호출 거부 !!!!!!!!!";
            $this->monitoring_telegram->telegramApiRequest("sendMessage", $message);
        }
    }
}