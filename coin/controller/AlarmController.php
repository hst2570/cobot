<?php

require_once MAX_PATH . '/service/Alarm.php';

class AlarmController
{
    private $channel;
    private $footer;

    public function __construct($channel, $footer = '')
    {
        $this->channel = $channel;
        $this->footer = $footer;
    }

    public function start()
    {
        $alarm = new Alarm($this->channel);
        $alarm->upbit('upbit');
        $alarm->huobi('huobi');

        $sites = array(
            'bithubm' => array(
                'url'=>'http://bithumb.cafe/notice',
                'type'=>'bithubm',
                'rex'=>'/.*href="http:\/\/bithumb.cafe\/archives.*>(.*)<\/a>$/',
                'rex2'=>'/.*href="http:\/\/bithumb.cafe\/archives\/(.*)".*>(.*)<\/a>$/'
            ),
            'binance' => array(
                'url' => 'support.binance.com/hc/en-us/sections/115000106672-New-Listings',
                'type'=>'binance',
                'rex'=>'/.*class="article-list-link">(.*)<\/a>/',
                'rex2'=>'/.*class="article-list-link">(.*)<\/a>/'
            ),
            'okex_coin' => array(
                'url'=>'https://support.okex.com/hc/en-us/sections/115000447632-New-Token',
                'type'=>'okex_coin',
                'rex'=>'/.*<a.*class="article-list-link">(.*)<\/a>/',
            ),
            'okex_noti' => array(
                'url'=>'https://support.okex.com/hc/en-us/sections/360000030652-Latest-Announcement',
                'type'=>'okex_noti',
                'rex'=>'/.*<a.*class="article-list-link">(.*)<\/a>/',
            )
        );

        foreach ($sites as $site_info) {
            $alarm->start_alarm_service($site_info);
        }
    }

    public function start_normal()
    {
        $alarm = new Alarm($GLOBALS['TELEGRAM_NORMAL_CHANNEL_ID'], $this->footer);
        $alarm->upbit('upbit_normal');
        $alarm->huobi('huobi_normal');

        $sites = array(
            'bithubm_normal' => array(
                'url'=>'http://bithumb.cafe/notice',
                'type'=>'bithubm_normal',
                'rex'=>'/.*href="http:\/\/bithumb.cafe\/archives.*>(.*)<\/a>$/',
                'rex2'=>'/.*href="http:\/\/bithumb.cafe\/archives\/(.*)".*>(.*)<\/a>$/'
            ),
            'binance_normal' => array(
                'url' => 'support.binance.com/hc/en-us/sections/115000106672-New-Listings',
                'type'=>'binance_normal',
                'rex'=>'/.*class="article-list-link">(.*)<\/a>/',
                'rex2'=>'/.*class="article-list-link">(.*)<\/a>/'
            ),
            'kucoin_normal' => array(
                'url'=>'https://news.kucoin.com/en/',
                'type'=>'kucoin_normal',
                'rex'=>'/.*[^>].*href="https:\/\/news.kucoin.com\/en.*>(.*)<\/a>$/',
            ),
            'okex_coin' => array(
                'url'=>'https://support.okex.com/hc/en-us/sections/115000447632-New-Token',
                'type'=>'okex_coin_normal',
                'rex'=>'/.*<a.*class="article-list-link">(.*)<\/a>/',
            ),
            'okex_noti' => array(
                'url'=>'https://support.okex.com/hc/en-us/sections/360000030652-Latest-Announcement',
                'type'=>'okex_noti_normal',
                'rex'=>'/.*<a.*class="article-list-link">(.*)<\/a>/',
            )
        );

        foreach ($sites as $site_info) {
            $alarm->start_alarm_service($site_info);
        }
    }
}