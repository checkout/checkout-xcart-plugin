<?php

class model_methods_creditcardpci extends model_methods_Abstract
{
    public function handleRequest()
    {
        $config = parent::handleRequest();
        $config['postedParam']['card']['name'] = $_POST['card_name'];
        $config['postedParam']['card']['number'] = (string)$_POST['card_no'];
        $config['postedParam']['card']['expiryMonth'] = $_POST['card_expdate_Month'];
        $config['postedParam']['card']['expiryYear'] = $_POST['card_expdate_Year'];
        $config['postedParam']['card']['cvv'] = $_POST['card_cvv'];
        $this->_placeorder($config);
    }

    public function handleResponse($respondCharge)
    {
        parent::handleResponse($respondCharge);
    }

}