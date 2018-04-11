<?php

abstract class model_methods_Abstract
{

    public function handleRequest()
    { 
        global $module_params, $userinfo, $sql_tbl, $cart, $secure_oid, $XCARTSESSID;

        $orderid = $secure_oid[0];

        db_query("REPLACE INTO $sql_tbl[cc_pp3_data] (ref,sessid,trstat)
                VALUES ('" . addslashes($orderid) . "','" . $XCARTSESSID . "','GO|" . implode('|', $secure_oid) . "')");

        $config = array();
        $Api = CheckoutApi_Api::getApi(array('mode' => $module_params['param01']));
        $amountCents = $Api->valueToDecimal($cart['total_cost'],$module_params['param09']);
        $config['authorization'] = $module_params['param02'];
        $config['mode'] = $module_params['param01'];
        
         if ($module_params['param06'] == 'Authorize and Capture') {
            $config = array_merge_recursive($this->_captureConfig(), $config);
            
        } else {     
            $config = array_merge_recursive($this->_authorizeConfig(), $config);    
        }
        
        $products = array();
        foreach ($cart['products'] as $item) {

            $products[] = array(
                'name'     => $item['product'],
                'sku'      => $item['productcode'],
                'price'    => $item['price'],
                'quantity' => $item['amount']
            );
        }
        
        $billPhoneLength = strlen($userinfo['b_phone']);
        $billingAddressConfig = array(
            'addressLine1'  =>  $userinfo['b_address'],
            'addressLine2'  =>  $userinfo['b_address_2'],
            'postcode'      => $userinfo['b_zipcode'],
            'country'       =>  $userinfo['b_country'],
            'state'         => $userinfo['b_statename'],
            'city'          =>  $userinfo['b_city'],
        );
        
        if ($billPhoneLength > 6){
          $bilPhoneArray = array(
              'phone'  => array('number' => $userinfo['b_phone'])
          );
          $billingAddressConfig = array_merge_recursive($billingAddressConfig, $bilPhoneArray);  
       }
       
        $shipPhoneLength = strlen($userinfo['s_phone']);
        $shippingAddressConfig = array(
            'addressLine1'  =>  $userinfo['s_address'],
            'addressLine2'  =>  $userinfo['s_address_2'],
            'postcode'      => $userinfo['s_zipcode'],
            'country'       =>  $userinfo['s_country'],
            'state'         => $userinfo['s_statename'],
            'city'          =>  $userinfo['s_city'],
        );
        
        if ($shipPhoneLength > 6){
          $shipPhoneArray = array(
              'phone'  => array('number' => $userinfo['s_phone'])
          );
          $shippingAddressConfig = array_merge_recursive($shippingAddressConfig, $shipPhoneArray);  
        }

        $config['postedParam'] = array_merge_recursive($config['postedParam'], array(
            'email'           => $userinfo['email'],
            'value'           => $amountCents,
            'currency'        => $module_params['param09'],
            'trackId'         => $orderid,
            'description'     => "Order number::$orderid",
            'shippingDetails' => $shippingAddressConfig,
            'products'        => $products,
            'card'     => array(
                            'billingDetails' => $billingAddressConfig
                            )
            ));
        return $config;
    }

    public function handleResponse($respondCharge)
    {
        global $xcart_dir, $secure_oid, $sql_tbl,$xcart_catalogs, $cart,$module_params; //all these global variables are needed to process payment.

        $skey = $secure_oid[0];

        $xcart_catalogs['customer'];
        $Api = CheckoutApi_Api::getApi(array('mode' => $module_params['param01']));
        $amountCents = $Api->valueToDecimal($cart['total_cost'],$module_params['param09']);
 
         $toValidate = array(
          'currency' => $module_params['param09'],
          'value' => $amountCents,
          'trackId' => $skey,
        );
        
        if ($respondCharge->isValid()) {
          
            if (preg_match('/^1[0-9]+$/', $respondCharge->getResponseCode())) {

                // update charge trackId
                $chargeUpdated = $Api->updateTrackId($respondCharge, $skey);
                $charge['chargeId'] = $respondCharge->getId(); 
                $respondCharge = $Api->getCharge($charge);
                $validateRequest = $Api::validateRequest($toValidate,$respondCharge);
                $message = 'Transaction approved. Charge ID : ' . $respondCharge->getId();
                if($validateRequest['status']){  
                      foreach($validateRequest['message'] as $errormessage){
                        $message .= $errormessage . '. ';
                      }
                }
                $bill_output['code'] = 1;
                $bill_output['billmes'] = $message;
                
            } else { 
                $bill_output['code'] = 2;
                $bill_output['billmes'] = 'An error has occured. Please verify your credit card details and try again.  ' . $respondCharge->getId();
            }
        } else {
            $bill_output['code'] = 4;
            $bill_output['billmes'] = 'An error has occured. Please verify your credit card details and try again.';

        }
        require($xcart_dir . '/payment/payment_ccend.php');
    }

    protected function _placeorder($config)
    {
        //building charge
        $respondCharge = $this->_createCharge($config);
        return $this->handleResponse($respondCharge);
    }

    public function _createCharge($config)
    {
        global $module_params;
        
        $Api = CheckoutApi_Api::getApi(array('mode' => $module_params['param01']));
        return $Api->createCharge($config);
    }

    public function _captureConfig()
    {
        global $module_params;
        
        $to_return['postedParam'] = array(
            'autoCapture' => CheckoutApi_Client_Constant::AUTOCAPUTURE_CAPTURE,
            'autoCapTime' => (int)$module_params['param07']
        );
        return $to_return;
    }

    public function _authorizeConfig()
    {
        $to_return['postedParam'] = array(
            'autoCapture' => CheckoutApi_Client_Constant::AUTOCAPUTURE_AUTH,
            'autoCapTime' => 0
        );
        return $to_return;
    }

}