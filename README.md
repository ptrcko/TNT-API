# TNT-API
Send parcel information to TNT for accurate rate and transit time information.

Contact TNT on 1300 851 131 to get a username and password for the API

Add this information to the TntConfig.ini file.  I recommend keeping this outside the root directory
for your website and using approprite file permissions

Include this class in your application.  Examples of calling the code are :


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


I put this together over about 12 hours.  I would appriciate any feedback or comments on the code and how to improve it.
