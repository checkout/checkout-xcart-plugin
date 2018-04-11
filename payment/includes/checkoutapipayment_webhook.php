<?php
define('SKIP_COOKIE_CHECK', true);

require_once '../../auth.php';
include 'autoload.php';

x_load('order');

$data = json_decode(file_get_contents("php://input"));

$logfile = func_get_securable_current_location().'/var/log/webhook.php';
 
if (empty($data)) {
    // empty request
    return http_response_code(400);
    
} else {
    
    $payment_cc_data = func_query_first("SELECT * FROM $sql_tbl[ccprocessors] WHERE processor='checkoutapipayment.php'");
    $Api = CheckoutApi_Api::getApi(array('mode' => $payment_cc_data['param01']));

    $trackId = $data->message->trackId;
    $chargeId = $data->message->id;
    $eventType = $data->eventType;

    $order_data = func_order_data($trackId);
    $orderTotal = $order_data['order']['total'];
    $orderValue = $Api->valueToDecimal($orderTotal,$payment_cc_data['param09']);
    $chargeValue = $data->message->value;

    if($eventType == 'charge.succeeded'){
        $message = 'Transaction Authorised successfully. ChargeId: '.$chargeId;
        func_change_order_status($trackId, 'A', $message); //Charge Authorised

    } elseif($eventType == 'charge.captured'){
        $message = 'Transaction Captured successfully . ChargeId: '.$chargeId;
        func_change_order_status($trackId, 'P', $message); // Charge Captured

    } elseif($eventType == 'charge.failed'){
        $message = 'Transaction Declined. ChargeId: '.$chargeId;
        func_change_order_status($trackId, 'D', $message); // Charge Failed

    } elseif ($eventType == 'charge.refunded') {
        if($chargeValue < $orderValue ){
            $message = 'Transaction Partially Refunded. ChargeId: '.$chargeId;
            func_change_order_status($trackId, 'P', $message); // Charge Refunded
        } else {
            $message = 'Transaction Refunded. ChargeId: '.$chargeId;
            func_change_order_status($trackId, 'R', $message); // Charge Refunded    
        }

    } elseif ($eventType == 'charge.voided') {
        $message = 'Transaction Voided. ChargeId: '.$chargeId;
        func_change_order_status($trackId, 'D', $message); // Charge failed
        
    } elseif ($eventType == 'invoice.cancelled') {
        $message = 'Transaction cancelled. ChargeId: '.$chargeId;
        func_change_order_status($trackId, 'D', $message); // Charge failed
        
    }

    return http_response_code(200);
}