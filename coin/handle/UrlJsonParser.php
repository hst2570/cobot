<?php

class UrlJsonParser
{
    private static $instance = null;

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new UrlJsonParser();
        }

        return self::$instance;
    }

    public function getJson($url)
    {
        $response = $this->getCurl($url);

        return json_decode($response);
    }

    public function getCurl($url)
    {
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($handle, CURLOPT_TIMEOUT, 60);
        return curl_exec($handle);
    }
}