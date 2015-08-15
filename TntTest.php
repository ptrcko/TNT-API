<?php

include 'TntEnquiry.php';

$enquiry = new TntEnquiry();


var_dump($enquiry->setShipDate("2015-08-19"));


$deliveryAddress = array(
    'suburb'=>'Brighton',
    'postCode'=>'3186',
    'state'=>'vic'
);
$enquiry->setDeliveryAddress($deliveryAddress);


$dom = $enquiry->createBaseXML();

$dom = $enquiry->addItem($dom,2,5,22,33,10);

$dom = $dom->saveXML();
$output = $enquiry->send($dom);

var_dump($output);
?>
