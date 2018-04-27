<?php
//
define('MAX_PATH',dirname(dirname(dirname(__FILE__))));
require_once MAX_PATH . '/coin/env.php';

class ApiCall
{
    private $symbol = 'https://www.binance.com/exchange/public/product';
    private $path = 'https://api.binance.com';
    private $ping = '/api/v1/ping';
    private $time = '/api/v1/time';
    private $exchange_info = '/api/v1/exchangeInfo';
    private $depth = '/api/v1/depth';
    private $trades = '/api/v1/trades';
    private $candlestick = '/api/v1/klines';
    private $bookTicker = '/api/v3/ticker/bookTicker';
    private $account = '/api/v3/account';
    private $order = '/api/v3/order';

    public function test_ping()
    {
        return $this->curl($this->path.$this->ping);
    }

    public function test_time()
    {
        return $this->curl($this->path.$this->time);
    }

    public function test_exchange_info()
    {
        return json_decode($this->curl($this->path.$this->exchange_info), true);
    }

    public function test_depth($params)
    {
        return json_decode($this->curl($this->path.$this->depth.'?'.http_build_query($params)), true);
    }

    public function test_trades($params)
    {
        return json_decode($this->curl($this->path.$this->trades.'?'.http_build_query($params)), true);
    }

    public function test_candlestick($params)
    {
        return json_decode($this->curl($this->path.$this->candlestick.'?'.http_build_query($params)), true);
    }

    public function test_bookTicker($params)
    {
        return json_decode($this->curl($this->path.$this->bookTicker.'?'.http_build_query($params)), true);
    }

    public function test_account($params)
    {
        return json_decode($this->curl($this->path.$this->account.'?'.http_build_query($params)), true);
    }

    public function test_order($params)
    {
        return json_decode($this->curl_post($this->path.$this->order, $params), true);
    }

    public function test_symbol()
    {
        return json_decode($this->curl($this->symbol), true);
    }

    private function curl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-MBX-APIKEY: '.$GLOBALS['BINANCE_PUBLIC_KEY']));
        return curl_exec($ch);
    }

    private function curl_post($url, $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-MBX-APIKEY: '.$GLOBALS['BINANCE_PUBLIC_KEY']));
        return curl_exec($ch);
    }
}