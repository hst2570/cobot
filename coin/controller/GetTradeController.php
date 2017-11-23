<?php

require_once MAX_PATH . '/handle/xcoin_api_client.php';

class GetTradeController
{
    private $recent_transactions = '/public/recent_transactions';
    private $api;
    private $coin_type;
    private $offset = 101;
    private $offsetLength = 5;
    private $db;

    public function __construct($coin_type)
    {
        $this->db = new mysqli($GLOBALS['database_host'], $GLOBALS['database_user'], $GLOBALS['database_password'], $GLOBALS['database_name']);
        $this->coin_type = $coin_type;
        $this->api = new XCoinAPI();
    }

    public function setCondition()
    {
		$deleteSql = 'DELETE FROM traded_info WHERE registered_time < DATE_ADD(NOW(), INTERVAL -4 HOUR)';
		$this->db->query($deleteSql);
        $data = $this->getCondition();
        for ($i = 0; $i < sizeof($data); $i++) {
            $traded_date = $data[$i]->data;
            foreach ($traded_date as $item) {
                $value = array(
                    '"' . $item->transaction_date . '"',
                    '"' . $item->type . '"',
                    '"' . $item->units_traded . '"',
                    '"' . $item->price . '"',
                    '"' . $item->total . '"',
                    '"' . $this->coin_type . '"'
                );

                $sql = 'insert into traded_info (transaction_date, type, units_traded, price, total, coin_type)
                        VALUES (' . implode($value, ',') . ')
                        on duplicate key update type = ' . $value[1] . ', coin_type = ' . $value[5];
				$this->db->query($sql);
            }
        }
    }

    private function getCondition()
    {
        $result = array();
        for ($i = $this->offset; $i > 0; $i = $i - $this->offsetLength) {
            $rgParams = array(
                'offset' => $i,
                'count' => 100
            );
            $result[] = $this->api->xcoinApiCall($this->recent_transactions.'/'.$this->coin_type, $rgParams, 'GET');
            echo $i . '번째 호출 완료' .date('Y-M-D h:i:s')."\n";
        }

        return $result;
    }
}
