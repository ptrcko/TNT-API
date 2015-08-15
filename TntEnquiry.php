<?php
/**
 * TNT Rate and Transit Time Class
 *
 * TNT provide an API to retrieve Rate and Transit Times 
 * for a given account, collection address, delivery address and
 * item type, weight and dimensions.
 *
 * This class constructs the required XML and handles sending and 
 * receiving messages, as well as some error handling and validation
 *
 * @author Patrick O'Sullivan>
 * @copyright 2015 Smarter Better Business
 */
 
 
class TntEnquiry
{

    /** As this is account specific, the TntConfig.ini must be
     *  updated with credentials and default collection address
     *  This can be placed outside the webroot and given 0600 permissions
     *  for security.  Use a relative path e.g. '../' 
     */
    
    public $configLocation = 'TntConfig.ini'; 
    
    public $config;
    
    public $xmlnsurl;
    

    public $senderAccount;
    public $username;
    public $password;
    
    /* Get request URL and namespace */
    public $requestURL;
    
    public function __construct(){
        
        /* Set some defaults at run time */
        if (!$config = parse_ini_file($this->configLocation, TRUE)) throw new exception('Unable to open ' . $configLocation . '.');
        
        $this->xmlnsurl = $config['tnt_urls']['xmlnsurl'];
        
        $this->requestURL = $config['tnt_urls']['request_url'];
        
        $this->collectAddress  = array(
            'suburb'    =>$config['collectionAddress']['suburb'],
            'postCode'  =>$config['collectionAddress']['post_code'],
            'state'     =>$config['collectionAddress']['state']
        );
            
                
        $this->shipDate       = date("Y-m-d"); //Default to today
        
         /* Get credentials */
        $this->senderAccount  = $config['credentials']['account'];
        $this->username       = $config['credentials']['username'];
        $this->password       = $config['credentials']['password'];
        
        /* Get request URL */
        $this->requestURL     = $config['tnt_urls']['request_url'];

    }

    public $shipDate;
     
    /* Set some other defaults that aren't defined at runtime*/
    public $deliveryAddress = array(
    );

    public $dangerous      = "false";
    
    public $packageType    = "N"; //N=NonDocs D=Docs
    
    public $payer          = "S"; //S=Sender R=Receiver
    
    
    
    /* Create some setters and getters for the properties */
    
    public function setCollectAddress($collectAddress){
        /* Expects an array */
        
        if(!$this->$collectAddress['suburb']=$collectAddress['suburb']) throw new exception('Collect address does not include suburb.');
        if(!$this->$collectAddress['postCode']=$collectAddress['postCode']) throw new exception('Collect address does not include postcode.');
        if(!$this->$collectAddress['state']=$collectAddress['state']) throw new exception('State address does not include postcode.');

    } 
    public function getCollectAddress(){
        
        return $this->collectAddress;
    }  
    public function setDeliveryAddress($deliveryAddress){
        /* Expects an array */

        $this->deliveryAddress['suburb']   =$deliveryAddress['suburb'];
        $this->deliveryAddress['postCode'] =$deliveryAddress['postCode'];
        $this->deliveryAddress['state']    =$deliveryAddress['state'];
        return $this->deliveryAddress;
    }
    public function getDeliveryAddress(){
        
        return $this->deliveryAddress;
    }
    public function setDangerous($dangerous){
       
        return  $this->dangerous = $dangerous;
    }
    public function getDangerous(){
        return $this->dangerous;
    }
    public function setPackageType($packageType){   
        return $this->packageType = $packageType;
    }
    public function getPackageType(){
        return $this->packageType;
    }
    public function setShipDate($shipDate){
         //Must be in format 'date("Y-m-d")
         return $this->shipDate = $shipDate;
    }
    public function setPayer($payer){
        /*Only use S or R */
        return($this->payer=$payer);
    }
    public function getShipDate(){
        return $this->shipDate;
    }
    
    public function createBaseXML(){
        
        /* create a dom document with encoding utf8 */
        $dom = new DOMDocument('1.0');
        $dom->formatOutput = true;

        /* create the root element of the xml tree */
        $xmlRoot = $dom->createElement('enquiry');
        
        /* add attribute */
        $xmlRootAttribute = $dom->createAttribute('xmlns');
        
        /* Value for the attribute */
        $xmlRootAttribute->value=$this->xmlnsurl;
        
        
        /* add the attribute to the document */
        $xmlRoot->appendChild($xmlRootAttribute);
        
        /* append it to the document created */
        $xmlRoot = $dom->appendChild($xmlRoot);
       
        /* build the core XML */
        
    
        $ratedTransitTimeEnquiry    = $this->addNode($dom,$xmlRoot,"ratedTransitTimeEnquiry");
        
        $cutOffTimeEnquiry          = $this->addNode($dom,$ratedTransitTimeEnquiry,"cutOffTimeEnquiry");

        
        /* add the variable XML data */
        $collectionAddress          = $this->addNode($dom,$cutOffTimeEnquiry,"collectionAddress");
        
        $this->addNodeElement($dom,$collectionAddress,"suburb",$this->collectAddress['suburb']);
        
        $this->addNodeElement($dom,$collectionAddress,"postCode",$this->collectAddress['postCode']);
        
        $this->addNodeElement($dom,$collectionAddress,"state",$this->collectAddress['state']);
          
        $deliveryAddress = $this->addNode($dom,$cutOffTimeEnquiry,"deliveryAddress");
        
        $this->addNodeElement($dom,$deliveryAddress,"suburb",$this->deliveryAddress['suburb']);
        
        $this->addNodeElement($dom,$deliveryAddress,"postCode",$this->deliveryAddress['postCode']);
        
        $this->addNodeElement($dom,$deliveryAddress,"state",$this->deliveryAddress['state']);
        
        $this->addNodeElement($dom,$cutOffTimeEnquiry,"shippingDate",$this->shipDate);
        
        /* this sets the date a little early. If the send request is sent later, it might be worth having a setter */
        $this->addNodeElement($dom,$cutOffTimeEnquiry,"userCurrentLocalDateTime",date(DATE_ATOM));
        
        $dangerous = $this->addNode($dom,$cutOffTimeEnquiry,"dangerousGoods");
        
        $this->addNodeElement($dom,$dangerous,"dangerous",$this->dangerous);
       /* add a placeholder for items to ship */
        $packageLines               = $this->addNodeWithAttribute($dom,$cutOffTimeEnquiry,"packageLines","packageType","N");

        
        $termsOfPayment = $this->addNode($dom,$ratedTransitTimeEnquiry,"termsOfPayment");
        
        $this->addNodeElement($dom,$termsOfPayment,"senderAccount",$this->senderAccount);
        /* we only care about shipping on our account. Other people might want to get a price for 
         * receiver pays.  This can be added to the TntConfig.ini file */
        $this->addNodeElement($dom,$termsOfPayment,"payer","S");
       
        return $dom;
    }
    
    private function addNode(&$dom,&$parent,$node){
        $newNode = $dom->createElement($node);
        $newNode = $parent->appendChild($newNode);
        return $newNode;
    }
    
    private function addNodeElement(&$dom,$node,$element,$value){
        $element=$dom->createElement($element,$value);
        return $node->appendChild($element);
    }
    
    private function addNodeWithAttribute(&$dom,&$parent,$node,$attribute,$value){
        
        $node = $dom->createElement($node);
        
        $nodeAttribute = $dom->createAttribute($attribute);
        
        $nodeAttribute->value = $value;
        
        $node->appendChild($nodeAttribute);
        
        return $parent->appendChild($node);
        
    }  
  
    public function addItem($dom,$numberOfPackages=1,$packWeight=1,$lenght=1,$width=1,$height=1,$dimensionUnit="cm",$weightUnit="kg"){
        /*This function does not access the dom by ref.  This allows for the base XML to be reused in loops */
      
        /* set package lines */
        $packageLines = $dom->getElementsByTagName('packageLines')->item(0);

        /* add packageLine */
        $packageLine = $this->addNode($dom,$packageLines,"packageLine");
        
               
        /* add number of packages */
        //$packageLine = $this->addNodeElement($dom,$packageLine,"numberOfPackages",(int)ceil($numberOfPackages));
      /* add number of packages */
        $packageLine->appendChild($dom->createElement("numberOfPackages",ceil($numberOfPackages)));
        
    
        /* add dimensions for packages */
        $dimensions = $this->addNodeWithAttribute($dom,$packageLine,"dimensions","unit",$dimensionUnit);
      
        /* add dimensions */
        $this->addNodeElement($dom,$dimensions,"length",ceil($lenght));
        $this->addNodeElement($dom,$dimensions,"width",ceil($width));
        $this->addNodeElement($dom,$dimensions,"height",ceil($height));
       
        /* create weight */
        $weight = $this->addNodeWithAttribute($dom,$packageLine,"weight","unit",$weightUnit);
        
        /* add weight */
        $weight = $this->addNodeElement($dom,$weight,"weight",$packWeight);
        
        
        return $dom;
    }   
    
    public function send($xml){
         
         $data = array ('xmlRequest' => $xml,  'username' => $this->username, 'password' => $this->password);
         
         $data = http_build_query($data);
         $data = str_replace('&amp;','&',$data);
         $data = str_replace('+','%20',$data) ;
         $data = str_replace('%2F','/',$data) ;
         echo '<!--'.$xml.'-->';
         $TNTURL = $this->requestURL;
         $ch = curl_init($TNTURL);
         curl_setopt($ch, CURLOPT_POST, 1);
         curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
         curl_setopt($ch, CURLOPT_TIMEOUT, 60);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
         curl_setopt($ch, CURLOPT_VERBOSE, true);
         $output = curl_exec($ch);
         
         return $output;
         curl_close($ch);   
        
    } 

}
 
