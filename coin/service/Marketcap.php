<?php

require_once MAX_PATH . '/handle/UrlJsonParser.php';

class Marketcap
{
    private $curlParser;

    public function __construct()
    {
        $this->curlParser = UrlJsonParser::getInstance();
    }

    public function get_markets($list)
    {
        $name = preg_replace('/.*Lists(.*)\((.*)\).*/i', '$1', $list);
        $name = trim($name);
        $name = preg_replace('/( )/', '-', $name);

        $marketcap_url = 'https://coinmarketcap.com/currencies/'.$name;
        $curl = $this->curlParser->getCurl($marketcap_url);
        $result = preg_split('/\n/', $curl);
        $flag = false;
        $markets = [];

        foreach ($result as $line) {
            if (preg_match('/\<table.*id="markets-table".*/', $line)) {
                $flag = true;
            }

            if ($flag === true && preg_match('/.*<\/table>.*/', $line)) {
                break;
            }

            if ($flag === true && preg_match('/href="\/exchanges\//', $line)) {
                $market = preg_replace('/.*href="\/exchanges\/.*>(.*)<\/a><\/td><td>.*/', '$1', $line);
                $markets[$market] = $market;
            }
        }

        return $markets;
    }
}