<?php

class BuyController
{

    public function getBuy()
    {
        $is_buy = $this->checkStatus();

        if ($is_buy) {

        }
    }

    private function checkStatus()
    {
        $data = $this->getCondition();
    }
}