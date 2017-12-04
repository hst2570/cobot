<?php

define('MAX_PATH',dirname(__FILE__));
require_once MAX_PATH . '/handle/Telegram.php';


$db = new mysqli($GLOBALS['database_host'], $GLOBALS['database_user'], $GLOBALS['database_password'], $GLOBALS['database_name']);
$sql = 'select * from upbit order by row_number desc limit 1';
$len = $this->db->query($sql);
$len = $len[0];

for ($i = $len + 1 ; $i < $len + 10 ; $i++) {
    $url = 'https://api-manager.upbit.com/api/v1/notices/' . $i;

    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($handle, CURLOPT_TIMEOUT, 60);
    $response = curl_exec($handle);

    $result = json_decode($response);

    if ($result->success === true) {
        $message = "##업비트 새로운 공지##\n";
        $message = $message.$result->data->title."\n";
        $message = $message.$result->data->body."\n";
        $telegram = new Telegram($GLOBALS['BOT_TOKEN'], $GLOBALS['TELEGRAM_GROUP_ID']);
        $sql = 'insert into upbit (row_number) VALUES ('.$i.')';
        $this->db->query($sql);
    }
}




