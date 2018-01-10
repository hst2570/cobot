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

$a = UrlJsonParser::getInstance();
$curl = $a->getCurl('http://bithumb.cafe/notice');

$result = preg_split('/\n/',$curl);
$rex = '/.*href="http:\/\/bithumb.cafe\/archives.*>(.*)<\/a>$/';
$list = array();

foreach ($result as $line) {
    if (preg_match($rex, $line)) {
        $list = preg_replace($rex, '$1', $line);
        if ($list !== '' && $list !== "더 보기") {
            var_dump($list);
        }
//        $sql = 'select * from binance where contents="'.$list.'"';
//        $isset = $db->query($sql)->fetch_all();

//        if (empty($isset)) {
//            $sql = 'insert into binance (contents) VALUES ("'.$list.'")';
//            $db->query($sql);
//
//            $message = "### 바이넨스 new lists ###\n\n$list\n\n$date";
//
//        }
    }
}