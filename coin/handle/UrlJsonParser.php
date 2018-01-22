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

    public function getPapago()
    {
        $url = "https://openapi.naver.com/v1/papago/n2mt";
        $handle = curl_init($url);
//        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
//        curl_setopt($handle, CURLOPT_TIMEOUT, 60);
        curl_setopt($handle, CURLOPT_HEADER, "Content-Type: application/x-www-form-urlencoded;charset=UTF-8");
        curl_setopt($handle, CURLOPT_HEADER, "X-Naver-Client-Id: 6OZZQQgUoBTAspqaiBvp");
        curl_setopt($handle, CURLOPT_HEADER, "X-Naver-Client-Secret: e15HFBMjul");
        curl_setopt($handle, CURLOPT_POST, "source=ko&target=en&text=만나서 반갑습니다.");
//        curl_setopt($handle, CURLOPT_HEADER, "X-Naver-Client-Id: 6OZZQQgUoBTAspqaiBvp");
//        curl_setopt($handle, CURLOPT_HEADER, "X-Naver-Client-Secret: e15HFBMjul");
        return curl_exec($handle);
    }
}