<?php

global $xcart_dir, $sql_tbl, $module_params; //all these global variables are needed to process payment.

include_once '../../auth.php';
include 'autoload.php';

$retunUrl = func_get_securable_current_location().DIR_CUSTOMER . "/cart.php?mode=checkout";

$payment_cc_data = func_query_first("SELECT * FROM $sql_tbl[ccprocessors] WHERE processor='checkoutapipayment.php'");

$configs['authorization'] = $payment_cc_data['param02'];
$configs['paymentToken'] = $_GET['cko-payment-token'];

$Api = CheckoutApi_Api::getApi(array('mode' => $payment_cc_data['param01']));
$result = $Api->verifyChargePaymentToken($configs);
$skey = $result->getTrackId();

//Set order status as failed
$bill_output['code'] = 2;
$bill_output['billmes'] = 'An error has occured. Please verify your credit card details and try again.  ' . $result->getId();

require($xcart_dir . '/payment/payment_ccend.php');
