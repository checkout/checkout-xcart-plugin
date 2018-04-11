<?php

class Model
{
    protected $_instance;

    public function handleRequest()
    {
        $this->getInstance()->handleRequest();
    }

    public function handleResponse()
    {
        $this->getInstance()->handleResponse();
    }

    public function getInstance()
    {
        global $module_params;
        $methodType =  $module_params['param05'];
        if(!$this->_instance) {
            switch($methodType) {
                case 'pci':
                    $this->_instance = CheckoutApi_Lib_Factory::getInstance('model_methods_creditcardpci');
                    break;
                case 'hosted':
                    $this->_instance = CheckoutApi_Lib_Factory::getInstance('model_methods_creditcardhosted');
                    break;
                default :
                    $this->_instance =  CheckoutApi_Lib_Factory::getInstance('model_methods_creditcard');

                    break;
            }
        }

        return $this->_instance;

    }
}