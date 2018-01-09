<?php
define('MAX_PATH',dirname(dirname(__FILE__)));
require_once MAX_PATH . '/handle/UrlJsonParser.php';

class UpbitParser
{
    /*
     * KRW-BTC
     */
    private $ticks_url = 'https://crix-api-endpoint.upbit.com/v1/crix/trades/ticks?code=CRIX.UPBIT.%s&count=%s';
    private $days_url = 'https://crix-api-endpoint.upbit.com/v1/crix/candles/days?code=CRIX.UPBIT.%s&count=%s';
    private $minutes_url = 'https://crix-api-endpoint.upbit.com/v1/crix/candles/minutes/%s?code=CRIX.UPBIT.%s&count=%s';
    private $lines_url = 'https://crix-api-endpoint.upbit.com/v1/crix/candles/lines?code=CRIX.UPBIT.%s';
    private $status_url = 'https://ccx.upbit.com/api/v1/market_status/all';

    private $parser;

    public function __construct()
    {
        $this->parser = UrlJsonParser::getInstance();
    }

    function getTicks($currency, $count)
    {
        $url = sprintf($this->ticks_url, $currency, $count);
        return $this->parser->getJson($url);
    }

    function getDays($currency, $count)
    {
        $url = sprintf($this->days_url, $currency, $count);
        return $this->parser->getJson($url);
    }

    function getMinutes($minute, $currency, $count)
    {
        $url = sprintf($this->minutes_url, $minute, $currency, $count);
        return $this->parser->getJson($url);
    }

    function getLines($count)
    {
        $url = sprintf($this->lines_url, $count);
        return $this->parser->getJson($url);
    }

    function getCoinStatus()
    {
        return $this->parser->getJson($this->status_url);
    }
}