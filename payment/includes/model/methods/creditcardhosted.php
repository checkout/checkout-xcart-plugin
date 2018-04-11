<?php

class model_methods_creditcardhosted extends model_methods_Abstract
{

  public function handleRequest() {
    global $module_params,$secure_oid;

    $config = parent::handleRequest();
  
    $returnUrl = func_get_securable_current_location().'/payment/includes/checkoutapipayment_callback.php';
    $cancelUrl = func_get_securable_current_location().DIR_CUSTOMER . "/cart.php?mode=checkout";
    $orderId = 'orderId:'.$secure_oid[0];
    $paymentToken = $this->getPaymentToken($secure_oid[0]);

    $url = "https://secure1.checkout.com/sandbox/payment/";

    if($module_params['param01'] == 'live'){
      $url = "https://secure1.checkout.com/payment/";
    }

    echo '<p><center>You will be redirected to the payment gateway.</center></p>';
    echo '<form id="payment-form" style="display:none" action="'.$url.'" method="POST">';
    echo '<input name="publicKey" value="'.$module_params['param03'].'"/>';
    echo '<input name="paymentToken" value="'.$paymentToken['token'].'"/>';
    echo '<input name="customerEmail" value="'.$config['postedParam']['email'].'"/>';
    echo '<input name="value" value="'.$config['postedParam']['value'].'"/>';
    echo '<input name="currency" value="'.$config['postedParam']['currency'].'"/>';
    echo '<input name="cardFormMode" value="cardTokenisation"/></input>';
    echo '<input name="paymentMode" value="'.$module_params['param04'].'"/>';
    echo '<input name="redirectUrl" value="'.$returnUrl.'" />';
    echo '<input name="cancelUrl" value="'.$cancelUrl.'" />';
    echo '<input name="contextId" id="contextId" value="'.$orderId.'">';
   // echo '<input name="logoUrl" value=""/>';
   // echo '<input name="title" value=""/>';
   // echo '<input name="themeColor" value=""/>';
    echo '</form>';

    echo'<script>';
    echo'document.getElementById("payment-form").submit()';
    echo'</script>';

    exit();

  }

  public function getPaymentToken($orderId){
   
    global $userinfo, $cart, $module_params, $sql_tbl;

    $version = func_query_first_cell("SELECT value FROM $sql_tbl[config] WHERE name='version'");

    $order_data = func_query_first("SELECT * FROM $sql_tbl[orders] WHERE orderid=$orderId");

    $payment_cc_data = $module_params;
    $config = array();
    $productsLoad = $cart['products'];
    $scretKey = $payment_cc_data['param02'];
    $integrationType = $payment_cc_data['param05'];
    $Api = CheckoutApi_Api::getApi(array('mode' => $payment_cc_data['param01']));
    $amountCents = $Api->valueToDecimal($cart['total_cost'],$payment_cc_data['param09']);

    $config['authorization'] = $scretKey;
    $config['mode'] = $payment_cc_data['param01'];
    $autoCapture = $payment_cc_data['param06']=='Authorize and Capture'? 'Y':'N';

    $config['postedParam'] = array(
        'autoCapture' => $autoCapture,
        'autoCapTime' => $payment_cc_data['param07']
    );

    $products = array();
    foreach ($productsLoad as $item) {

        $products[] = array(
            'name'      =>  $item['product'],
            'sku'       =>  $item['productcode'],
            'price'     =>  $item['price'],
            'quantity'  =>  $item['amount']
        );
    }

    $billPhoneLength = strlen($userinfo['b_phone']);
    $billingAddressConfig = array(
        'addressLine1'  =>  $userinfo['b_address'],
        'addressLine2'  =>  $userinfo['b_address_2'],
        'postcode'      =>  $userinfo['b_zipcode'],
        'country'       =>  $userinfo['b_country'],
        'state'         =>  $userinfo['b_statename'],
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
        'recipientName' =>  $order_data['firstname']. ' '. $order_data['lastname'],
        'addressLine1'  =>  $userinfo['s_address'],
        'addressLine2'  =>  $userinfo['s_address_2'],
        'postcode'      =>  $userinfo['s_zipcode'],
        'country'       =>  $userinfo['s_country'],
        'state'         =>  $userinfo['s_statename'],
        'city'          =>  $userinfo['s_city'],
    );
    
    if ($shipPhoneLength > 6){
        $shipPhoneArray = array(
            'phone'  => array('number' => $userinfo['s_phone'])
        );
        $shippingAddressConfig = array_merge_recursive($shippingAddressConfig, $shipPhoneArray);  
    }

    $metadata = array(
      'server'            => func_get_securable_current_location(),
      'quote_id'          => $orderId,
      'xcart_version'     => $version,
      'plugin_version'    => '1.0.0',
      'lib_version'       => CheckoutApi_Client_Constant::LIB_VERSION,
      'integration_type'  => $integrationType,
      'time'              => date('Y-m-d H:i:s'),
      'paypal_shippingAmount' => $order_data['shipping_cost'],
      'paypal_productAmount' => $order_data['subtotal'],
  );

    $config['postedParam'] = array_merge_recursive($config['postedParam'], array(
        'email'              =>   $userinfo['email'],
        'value'              =>   $amountCents,
        'currency'           =>   $payment_cc_data['param09'],
        'shippingDetails'    =>   $shippingAddressConfig,
        'products'           =>   $products,
        'billingDetails'     =>   $billingAddressConfig,
        'trackId'            =>   $orderId,
        'metadata'           =>   $metadata
           
    ));


    $paymentTokenCharge = $Api->getPaymentToken($config);

    $paymentTokenArray = array(
        'message' => '',
        'success' => '',
        'eventId' => '',
        'token' => '',
    );

    if ($paymentTokenCharge->isValid()) {
        $paymentTokenArray['token'] = $paymentTokenCharge->getId();
        $paymentTokenArray['success'] = true;
    }
    else {
        $paymentTokenArray['message'] = $paymentTokenCharge->getExceptionState()->getErrorMessage();
        $paymentTokenArray['success'] = false;
        $paymentTokenArray['eventId'] = $paymentTokenCharge->getEventId();
    }

    return $paymentTokenArray;

  }

}