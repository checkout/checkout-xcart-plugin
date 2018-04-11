<?php

global $xcart_dir, $secure_oid, $sql_tbl, $xcart_catalogs, $cart, $module_params, $version; //all these global variables are needed to process payment.

if (isset($_POST['cko-card-token']) && isset($_POST['cko-context-id'])) { 
  
  include_once '../../auth.php';
  include 'autoload.php';

  $xcart_catalogs['customer'];
  $orderId = str_replace("orderId:", "", $_POST['cko-context-id']);
  $skey = $orderId;

  $payment_cc_data = func_query_first("SELECT * FROM $sql_tbl[ccprocessors] WHERE processor='checkoutapipayment.php'");
  $order_data = func_query_first("SELECT * FROM $sql_tbl[orders] WHERE orderid=$skey");
  $product_data = func_query("SELECT * FROM $sql_tbl[order_details] WHERE orderid=$skey");
  $config = getChargeData($payment_cc_data,$order_data,$product_data);
  $config['postedParam']['cardToken'] = $_POST['cko-card-token'];
  $config['authorization'] = $payment_cc_data['param02'];
  
  $Api = CheckoutApi_Api::getApi(array('mode' => $payment_cc_data['param01']));
  $result = $Api->createCharge($config);

  if($result->getRedirectUrl()){
    header('Location: '.$result->getRedirectUrl());
    exit();
  }

  $message = 'Transaction approved. Charge ID : ' . $result->getId();

  if (preg_match('/^1[0-9]+$/', $result->getResponseCode())) {

    if($result->getResponseCode() == '10100'){
      $message = 'Transaction has been flagged. Reason : '. $result->getResponseMessage();
    }

    $bill_output['code'] = 3;
    $bill_output['billmes'] = $message;
  }
  else {

    $bill_output['code'] = 2;
    $bill_output['billmes'] = 'An error has occured. Please verify your payment details and try again.  ' . $result->getId();
  }

  require($xcart_dir . '/payment/payment_ccend.php');

} elseif ($_REQUEST['cko-payment-token']) {

  include_once '../../auth.php';
  include 'autoload.php';
  $xcart_catalogs['customer'];
  
  $payment_cc_data = func_query_first("SELECT * FROM $sql_tbl[ccprocessors] WHERE processor='checkoutapipayment.php'");

  $configs['authorization'] = $payment_cc_data['param02'];
  $configs['paymentToken'] = $_GET['cko-payment-token'];

  $Api = CheckoutApi_Api::getApi(array('mode' => $payment_cc_data['param01']));
  $result = $Api->verifyChargePaymentToken($configs);

  $skey = $result->getTrackId();
  $message = 'Transaction approved. Charge ID : ' . $result->getId();

  if (preg_match('/^1[0-9]+$/', $result->getResponseCode())) {

    if($result->getResponseCode() == '10100'){
      $message = 'Transaction has been flagged. Reason : '. $result->getResponseMessage();
    }

    $bill_output['code'] = 3;
    $bill_output['billmes'] = $message;
  }
  else {

    $bill_output['code'] = 2;
    $bill_output['billmes'] = 'An error has occured. Please verify your payment details and try again.  ' . $result->getId();
  }

  require($xcart_dir . '/payment/payment_ccend.php');

}
else {

  header('Location: ../');
  die('Access denied');
}


function getChargeData($payment_cc_data,$order_data,$product_data){
  global $sql_tbl;

  $version = func_query_first_cell("SELECT value FROM $sql_tbl[config] WHERE name='version'");
  $secretKey = $payment_cc_data['param02'];
  $Api = CheckoutApi_Api::getApi(array('mode' => $payment_cc_data['param01']));
  $config = array();
  $autoCapture = $payment_cc_data['param04'];
  $integrationType = $payment_cc_data['param05'];
  $address = $order_data['b_address'];
  $addressParts = explode("\n", $address);
  $shipAddress = $order_data['s_address'];
  $shipAddressParts = explode("\n", $shipAddress);

  $amount = $order_data['total'];
  $value = $Api->valueToDecimal($amount,$payment_cc_data['param09'] );

  if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
  } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
  } else {
      $ip = $_SERVER['REMOTE_ADDR'];
  }

  /* START: Prepare data */
  $billingAddressConfig = array (
      'addressLine1'  => $addressParts[0],
      'addressLine2'  => $addressParts[1],
      'postcode'      => $order_data['b_zipcode'],
      'country'       => $order_data['b_country'],
      'city'          => $order_data['b_city'],
      'state'         => $order_data['b_state'],
      'phone'         => array('number' => $order_data['s_phone'])
  );

  $shippingAddressConfig = array(
      'recipientName'      => $order_data['firstname']. ' '. $order_data['lastname'],
      'addressLine1'       => $shipAddressParts[0],
      'addressLine2'       => $shipAddressParts[1],
      'postcode'           => $order_data['s_zipcode'],
      'country'            => $order_data['s_country'],
      'city'               => $order_data['s_city'],
      'state'              => $order_data['s_state'],
  );

  $products       = array();

  foreach ($product_data as $item) {
    
      $products[] = array(
          'description'   => $item['productid'],
          'name'          => $item['product'],
          'price'         => $item['price'],
          'quantity'      => $item['amount'],
          'sku'           => $item['productcode']
      );
  }

  $autoCapture = $payment_cc_data['param06'] == 'Authorize'? "N":"Y";

  $config['autoCapTime']  = $payment_cc_data['param07'];
  $config['autoCapture']  = $autoCapture;
  $config['chargeMode']   = $payment_cc_data['param08'];
  $config['value']                = $value;
  $config['currency']             = $payment_cc_data['param09'];
  $config['trackId']              = $order_data['orderid'];
  $config['customerName']         = $order_data['firstname']. ' '. $order_data['lastname'];
  $config['email']                = $order_data['email'];
  $config['customerIp']           = $ip;

  $config['shippingDetails']  = $shippingAddressConfig;
  $config['billingDetails']   = $billingAddressConfig;
  $config['products']         = $products;

  /* Meta */
  $config['metadata'] = array(
      'server'            => func_get_securable_current_location(),
      'quote_id'          => $order_data['orderid'],
      'xcart_version'     => $version,
      'plugin_version'    => '1.0.0',
      'lib_version'       => CheckoutApi_Client_Constant::LIB_VERSION,
      'integration_type'  => $integrationType,
      'time'              => date('Y-m-d H:i:s')
  );

  $result['authorization']    = $secretKey;
  $result['postedParam']      = $config;

  return $result;

}
