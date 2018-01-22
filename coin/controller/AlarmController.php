<?php

require_once MAX_PATH . '/service/Alarm.php';

class AlarmController
{
    public function start()
    {
        $alarm = new Alarm($GLOBALS['TELEGRAM_GROUP_ID']);
        $alarm->upbit('upbit');

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
            'kucoin' => array(
                'url'=>'https://news.kucoin.com/en/',
                'type'=>'kucoin',
                'rex'=>'/.*[^>].*href="https:\/\/news.kucoin.com\/en.*>(.*)<\/a>$/',
            )
        );

        foreach ($sites as $site_info) {
            $alarm->start_alarm_service($site_info);
        }
    }

    public function start_normal()
    {
        $alarm = new Alarm($GLOBALS['TELEGRAM_NORMAL_CHANNEL_ID']);
        $alarm->upbit('upbit_normal');

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
            )
        );

        foreach ($sites as $site_info) {
            $alarm->start_alarm_service($site_info);
        }
    }
}