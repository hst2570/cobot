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

    public function getJsonToArray($url)
    {
        $response = $this->getCurl($url);

        return json_decode($response, true);
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
//$u = UrlJsonParser::getInstance();
//$result = $u->getJson('https://www.huobi.com/p/api/contents/pro/notice/1076');
//var_dump($result);
//$rex = '/.*<a.*class="article-list-link">(.*)<\/a>/';
//$rex2 = '/.*<a.*class="article-list-link">(.*)<\/a>/';
//$type = 'OKEX';
//
//$result = preg_split('/\n/', $result);
//
//foreach ($result as $line) {
//    if (preg_match($rex, $line)) {
//        $list = preg_replace($rex, '$1', $line);
//        $contents = preg_replace($rex2, '$1', $line);
//
//        if ($list !== '' && $list !== "더 보기") {
//
//            if (empty($isset)) {
//                $message = "### ".$type." new Announcement ###\n\n$list\n\n";
//
//                if (preg_match('/.*Lists.*\((.*)\).*/i', $list)) {
//                    $message = $message."exchange list\n".implode(', ', $this->Marketcap->get_markets($list));
//                }
//                var_dump($message);
//
////                $this->send_msg_to_telegram($message);
//            }
//        }
//    }
//}