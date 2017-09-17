<?php

require_once MAX_PATH . '/handle/xcoin_api_client.php';

class BuyController
{
    private $account = "/info/balance";
    private $recent_transactions = '/public/recent_transactions';
    private $api;
    private $coin_type;
    private $offset = 1001;
    private $offsetLength = 200;

    public function __construct($coin_type)
    {
        $this->coin_type = $coin_type;
        $this->api = new XCoinAPI();
    }

    public function getBuy()
    {
        $this->setCondition();
        $is_buy = $this->checkStatus();

        if ($is_buy) {

        }
    }

    private function setCondition()
    {
        $data = $this->getCondition();

        for ($i = 0; $i < sizeof($data); $i++) {
            $traded_date = $data[$i]->data;
            foreach ($traded_date as $item) {
                $value = array(
                    '"'. $item->transaction_date.'"',
                    '"'.$item->type.'"',
                    '"'.$item->units_traded.'"',
                    '"'.$item->price.'"',
                    '"'.$item->total.'"'
                );

                $sql = 'insert into traded_info (transaction_date, type, units_traded, price, total)
                        VALUES (' . implode($value, ',') . ')
                        on duplicate key update type = '.$value[1];
                echo $sql."\n";
            }
        }
    }

    private function getCondition()
    {
        $result = array();
        for ($i = $this->offset; $i > 0; $i = $i - $this->offsetLength) {
            echo $i . '번째 호출' . date('Y-M-D h:i:s')."\n";
            $rgParams = array(
                'offset' => $i,
                'count' => 100
            );
            $result[] = $this->api->xcoinApiCall($this->recent_transactions.'/'.$this->coin_type, $rgParams, 'GET');
            echo $i . '번째 호출 완료' .date('Y-M-D h:i:s')."\n";
        }

        return $result;
    }

    private function checkStatus()
    {
        $sql = 'select * from traded_info';
        $traded_info = '';


    }
}