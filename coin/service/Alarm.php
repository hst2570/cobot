<?php

require_once MAX_PATH . '/handle/Telegram.php';
require_once MAX_PATH . '/handle/UrlJsonParser.php';
require_once MAX_PATH . '/service/Marketcap.php';

class Alarm
{
    private $curlParser;
    private $db;
    private $telegram;
    private $Marketcap;
    private $footer;

    public function __construct($group_id, $footer = '')
    {
        $this->curlParser = UrlJsonParser::getInstance();
        $this->db = new mysqli($GLOBALS['database_host'], $GLOBALS['database_user'], $GLOBALS['database_password'], $GLOBALS['database_name']);
        $this->telegram = new Telegram($GLOBALS['BOT_TOKEN'], $group_id);
        $this->Marketcap = new Marketcap();
        $this->footer = $footer;
    }

    public function start_alarm_service($site_info)
    {
        $type = $site_info['type'];

        if ($type === 'upbit') {
            $this->upbit();
        } else {
            $this->crawling_type($site_info);
        }
    }

    private function crawling_type($site_info)
    {
        $db = $this->db;

        $url = $site_info['url'];
        $type = $site_info['type'];
        $rex = $site_info['rex'];
        if (isset($site_info['rex2'])) {
            $rex2 = $site_info['rex2'];
        } else {
            $rex2 = $site_info['rex'];
        }

        $result = $this->split_curl_data($url);

        foreach ($result as $line) {
            if (preg_match($rex, $line)) {
                $list = preg_replace($rex, '$1', $line);
                $contents = preg_replace($rex2, '$1', $line);

                if ($list !== '' && $list !== "더 보기") {
                    $sql = 'select * from alarm where contents = "'.$contents.'" and site_type = "'.$type.'"';
                    $isset = $db->query($sql)->fetch_all();

                    if (empty($isset)) {
                        $sql = 'insert into alarm (contents, site_type) VALUES ("'.$contents.'", "'.$type.'")';
                        $db->query($sql);

                        $message = "### ".$type." new Announcement ###\n\n$list\n\n";

                        if (preg_match('/.*Lists.*\((.*)\).*/i', $list)) {
                            $message = $message."exchange list\n".implode(', ', $this->Marketcap->get_markets($list));
                        }

                        $this->send_msg_to_telegram($message);
                    }
                }
            }
        }
    }

    public function upbit()
    {
        $db = $this->db;

        $sql = 'select * from alarm order by contents and site_type = "upbit" desc limit 1';
        $len = $db->query($sql)->fetch_all();
        $len = $len[0][0];

        for ($i = $len + 1 ; $i < $len + 10 ; $i++) {
            $url = 'https://api-manager.upbit.com/api/v1/notices/' . $i;

            $handle = curl_init($url);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($handle, CURLOPT_TIMEOUT, 60);
            $response = curl_exec($handle);

            $result = json_decode($response);

            if ($result->success === true) {
                $message = "## Upbit new Announcement  ##\n";
                $message = $message.$result->data->title."\n";
                $message = $message.$result->data->body."\n";

                $this->send_msg_to_telegram($message);

                $sql = 'insert into alarm (contents, site_type) VALUES ("'.$i.'", "upbit")';
                $db->query($sql);
            }
        }
    }

    private function split_curl_data($url)
    {
        $curl = $this->curlParser->getCurl($url);
        return preg_split('/\n/',$curl);
    }

    private function send_msg_to_telegram($message)
    {
        $message = $message."\n\n".$this->footer;
        $this->telegram->telegramApiRequest("sendMessage", $message);
    }
}

