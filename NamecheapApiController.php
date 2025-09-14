<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use Http;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class NamecheapApiController extends Controller
{
    protected $namecheapUserName = null;
    protected $namecheapApiKey = null;
    protected $command = null;
    protected $clientIP = null;
    protected $autoSetIp = false;

    private $setting = null;
    private $queryParam = [];

    private $apiHost = "";

    private static $allowedTransferExtensions = [
        'biz', 'ca', 'cc', 'co', 'com', 'com.es', 'com.pe', 'es',
        'in', 'info', 'me', 'mobi', 'net', 'net.pe', 'nom.es',
        'org', 'org.es', 'org.pe', 'pe', 'tv', 'us'
    ];

    public static $positiveTransferStatus = [
        1 => 'WhoIs information matches',
        5 => 'Transferred and paid successfully',
        9 => 'Awaiting auto verification of transfer request',
        10 => 'Transfer in Process - Acquiring Current Whois for Transfer Verification',
        11 => 'Auto verification of transfer request initiated',
        12 => 'Awaiting for auto transfer string validation',
        13 => 'Domain awaiting transfer initiation',
        14 => 'Domain transfer initiated and awaiting approval',
        28 => 'Fax received - awaiting registrant verification',
        29 => 'Awaiting manual fax verification',
        -1 => 'EPP Provided. Queued for Transfer / Queued for submission or Queued for Transfer or EPP Provided',
        -5 => 'Authorization mail will be sent shortly',
        -2 => 'Resubmitted - Queued for transfer',
        // -1 => 'Queued for submission or Queued for Transfer or EPP Provided',
    ];
    public static $negativeTransferStatus = [
        2 => 'Canceled due to WhoIs error',
        3 => 'Pending due to domain status',
        4 => 'Canceled due to domain status',
        6 => 'Transfer incomplete - charge problem',
        7 => 'Frozen due to charge problem',
        8 => 'NSI rejected transfer',
        15 => 'Canceled - cannot obtain domain contacts from Whois',
        16 => 'Canceled - domain contacts did not respond to verification e-mail',
        17 => 'Canceled - domain contacts did not approve transfer of domain',
        18 => 'Canceled - domain validation string is invalid',
        19 => 'Canceled - Whois information provided does not match current registrant',
        20 => 'Canceled - Domain is currently not registered and cannot be transferred',
        21 => 'Canceled - Domain is already registered in account and cannot be transferred',
        22 => 'Canceled - Domain is locked at current registrar, or is not yet 60 days old',
        23 => 'Canceled - Transfer already initiated for this domain',
        24 => 'Canceled - Unable to transfer due to unknown error',
        25 => 'Canceled - The current registrar has rejected transfer (please contact them for details)',
        26 => 'Canceled - Transfer authorization fax not received',
        27 => 'Canceled by customer',
        30 => 'Canceled - Domain name is invalid or is Invalid for Transfers',
        31 => 'Canceled - Domain is currently undergoing transfer by another Registrar',
        32 => 'Canceled - Invalid EPP/authorization key - Please contact current registrar to obtain correct key',
        33 => 'Canceled - Cannot transfer domain from name-only account',
        34 => 'Unable to complete transfer. Transfers must include a change in registrar',
        35 => 'Transfer request not yet submitted',
        36 => 'Canceled - Account is not authorized to perform domain transfers',
        37 => 'Canceled - Domain was not retagged or not retagged in time by losing registrar',
        45 => 'Order canceled',
        48 => 'Canceled - registrant to registrant transfer only allowed into Retail accounts',
        49 => 'Canceled - Maximum registration period exceeded',
        50 => 'Canceled - Cannot transfer premium name',
        51 => 'Canceled - Registrant info is missing',
        -4 => 'Canceled - Domain is locked at current registrar, or is not yet 60 days old',
        -22 => 'Canceled - Invalid entry / Waiting for EPP Transfer Code from Customer',
        -202 => 'Unable to retrieve expiration date from Whois database',
        // -22 => 'Waiting for EPP Transfer Code from Customer',
    ];
    

    function __construct(Request $request, $autoSetIp = false){

        $this->setting = getSetting();

        $this->namecheapUserName = getConfig('namecheap_username') ?? 'NO_API_USERNAME';
        $this->namecheapApiKey =  getConfig('namecheap_api_key') ??'NO_API_KEY';

        if($autoSetIp){
            $this->clientIP = $request->ip();
        }else{
            // $this->clientIP = '***.***.***.***'; //ip should be whitelisted on namecheap
        }

        $this->setApiHost();
        $this->initQueryParam();

    }

    function setApiHost(){
        $host = "";
        if($this->setting->app_mode=='live'){
            $hostSubDomain = "api";
        }else{
            $hostSubDomain = "api.sandbox";
        }
        $this->apiHost = "https://$hostSubDomain.namecheap.com/xml.response";
        return $this;
    }

    function initQueryParam(){
        $this->queryParam['ApiUser'] = $this->namecheapUserName;
        $this->queryParam['ApiKey'] = $this->namecheapApiKey;
        $this->queryParam['UserName'] = $this->namecheapUserName;
        $this->queryParam['Command'] = $this->command;
        $this->queryParam['ClientIp'] = $this->clientIP;
    }

    function setApiQueryParam($key = null,$value = null){
        if($key && $value){
            $this->queryParam[$key] = $value;
        }
        return $this;
    }

    function getApiURL(){
        // https://<service url>/xml.response?ApiUser=<api_username>&ApiKey=<api_key>&UserName=<nc_username>&Command=<cmd_name>&ClientIp=<clientIPaddress>
        $url = $this->apiHost.'?'.urldecode(Arr::query($this->queryParam));;
        return $url;
    }


    function send(){

        return Http::get($this->getApiURL());
    }

    // namecheap commands
    function getDomainListCommand(){
        $this->command = 'namecheap.domains.getTldList';
        $this->initQueryParam();
        return $this;
    }
    /**
     * Summary of checkDomainCommand
     * @param mixed $q example.com
     * @return static
     */
    function checkDomainCommand($q){
        $this->command = 'namecheap.domains.check';
        $this->initQueryParam();
        $this->setApiQueryParam('DomainList',$q);
        return $this;
    }

    /**
     * Summary of checkDomainTransferCommand
     * @param mixed $q example.com
     * @return static
     */
    function checkDomainTransferCommand($q){
        $this->command = 'namecheap.domains.transfer.getStatus';
        $this->initQueryParam();
        $this->setApiQueryParam('DomainName',$q);
        $this->setApiQueryParam('Years',1);
        return $this;
    }


    function makeDomainTransferCommand($domain, $epp, $year = 1){
        $this->command = 'namecheap.domains.transfer.create';
        $this->initQueryParam();
        $this->setApiQueryParam('DomainName',$domain);
        $this->setApiQueryParam('EPPCode',$epp);
        $this->setApiQueryParam('WGenable','yes');
        $this->setApiQueryParam('AddFreeWhoisguard','yes');
        $this->setApiQueryParam('Years',$year);
        return $this;
    }

    function getDomainTransferStatus($TransferID){
        $this->command = 'namecheap.domains.transfer.getStatus';
        $this->initQueryParam();
        $this->setApiQueryParam('TransferID',$TransferID);
        return $this;
    }

    /**
     * Summary of getDomainPriceCommand
     * @param string $domain com org net
     * @return static
     */
    function getDomainPriceCommand(string $domain){
        $this->command = 'namecheap.users.getPricing';
        $this->initQueryParam();
        $this->setApiQueryParam('ProductType','DOMAIN');
        $this->setApiQueryParam('ProductName',$domain);
        return $this;
    }

    /**
     * Create or register a new domain createDomainCommand
     * @param array $data
     * DomainName Years (2) RegistrantFirstName RegistrantLastName RegistrantAddress1 RegistrantCity RegistrantStateProvince RegistrantPostalCode RegistrantCountry RegistrantPhone RegistrantEmailAddress
     * AddFreeWhoisguard WGEnabled (yes/no)
     * @return static
     */
    function createDomainCommand(array $data){
        $this->command = 'namecheap.domains.create';
        $this->initQueryParam();
        $this->setDomainContact($data);
        return $this;
    }

    function renewDomainCommand($domain, $year){
        $this->command = 'namecheap.domains.renew';
        $this->initQueryParam();
        $this->setApiQueryParam('DomainName', $domain);
        $this->setApiQueryParam('Years', $year);
        return $this;
    }

    function setDomainContact(array $data, $isEdit = false){
        // Mandatory Fields
        if(!$isEdit){
            $this->setApiQueryParam('DomainName', $data['DomainName']);
        }

        $data['RegistrantCountry'] = $data['RegistrantCountry'] ?? 'United States';
        $data['RegistrantPhone'] = '+'.str($data['RegistrantPhone'])->remove([
            '-','+'
        ])->value();

        $this->setApiQueryParam('Years', $data['Years'] ?? 2); // Default value is 2
        $this->setApiQueryParam('RegistrantFirstName', $data['RegistrantFirstName']);
        $this->setApiQueryParam('RegistrantLastName', $data['RegistrantLastName']);
        $this->setApiQueryParam('RegistrantAddress1', $data['RegistrantAddress1']);
        $this->setApiQueryParam('RegistrantCity', $data['RegistrantCity']);
        $this->setApiQueryParam('RegistrantStateProvince', $data['RegistrantStateProvince']);
        $this->setApiQueryParam('RegistrantPostalCode', $data['RegistrantPostalCode']);
        $this->setApiQueryParam('RegistrantCountry', $data['RegistrantCountry']);
        $this->setApiQueryParam('RegistrantPhone', $data['RegistrantPhone']);
        $this->setApiQueryParam('RegistrantEmailAddress', $data['RegistrantEmailAddress']);

        // Optional Fields
        $this->setApiQueryParam('PromotionCode', $data['PromotionCode'] ?? '');
        $this->setApiQueryParam('RegistrantOrganizationName', $data['RegistrantOrganizationName'] ?? '');
        $this->setApiQueryParam('RegistrantJobTitle', $data['RegistrantJobTitle'] ?? '');
        $this->setApiQueryParam('RegistrantAddress2', $data['RegistrantAddress2'] ?? '');
        $this->setApiQueryParam('RegistrantPhoneExt', $data['RegistrantPhoneExt'] ?? '');
        $this->setApiQueryParam('RegistrantFax', $data['RegistrantFax'] ?? '');

        // Tech Contact
        $this->setApiQueryParam('TechFirstName', $data['TechFirstName'] ?? $data['RegistrantFirstName']);
        $this->setApiQueryParam('TechLastName', $data['TechLastName'] ?? $data['RegistrantLastName']);
        $this->setApiQueryParam('TechAddress1', $data['TechAddress1'] ?? $data['RegistrantAddress1']);
        $this->setApiQueryParam('TechCity', $data['TechCity'] ?? $data['RegistrantCity']);
        $this->setApiQueryParam('TechStateProvince', $data['TechStateProvince'] ?? $data['RegistrantStateProvince']);
        $this->setApiQueryParam('TechPostalCode', $data['TechPostalCode'] ?? $data['RegistrantPostalCode']);
        $this->setApiQueryParam('TechCountry', $data['TechCountry'] ?? $data['RegistrantCountry']);
        $this->setApiQueryParam('TechPhone', $data['TechPhone'] ?? $data['RegistrantPhone']);
        $this->setApiQueryParam('TechEmailAddress', $data['TechEmailAddress'] ?? $data['RegistrantEmailAddress']);

        // Admin Contact
        $this->setApiQueryParam('AdminFirstName', $data['AdminFirstName'] ?? $data['RegistrantFirstName']);
        $this->setApiQueryParam('AdminLastName', $data['AdminLastName'] ?? $data['RegistrantLastName']);
        $this->setApiQueryParam('AdminAddress1', $data['AdminAddress1'] ?? $data['RegistrantAddress1']);
        $this->setApiQueryParam('AdminCity', $data['AdminCity'] ?? $data['RegistrantCity']);
        $this->setApiQueryParam('AdminStateProvince', $data['AdminStateProvince'] ?? $data['RegistrantStateProvince']);
        $this->setApiQueryParam('AdminPostalCode', $data['AdminPostalCode'] ?? $data['RegistrantPostalCode']);
        $this->setApiQueryParam('AdminCountry', $data['AdminCountry'] ?? $data['RegistrantCountry']);
        $this->setApiQueryParam('AdminPhone', $data['AdminPhone'] ?? $data['RegistrantPhone']);
        $this->setApiQueryParam('AdminEmailAddress', $data['AdminEmailAddress'] ?? $data['RegistrantEmailAddress']);

        // AuxBilling Contact
        $this->setApiQueryParam('AuxBillingFirstName', $data['AuxBillingFirstName'] ?? $data['RegistrantFirstName']);
        $this->setApiQueryParam('AuxBillingLastName', $data['AuxBillingLastName'] ?? $data['RegistrantLastName']);
        $this->setApiQueryParam('AuxBillingAddress1', $data['AuxBillingAddress1'] ?? $data['RegistrantAddress1']);
        $this->setApiQueryParam('AuxBillingCity', $data['AuxBillingCity'] ?? $data['RegistrantCity']);
        $this->setApiQueryParam('AuxBillingStateProvince', $data['AuxBillingStateProvince'] ?? $data['RegistrantStateProvince']);
        $this->setApiQueryParam('AuxBillingPostalCode', $data['AuxBillingPostalCode'] ?? $data['RegistrantPostalCode']);
        $this->setApiQueryParam('AuxBillingCountry', $data['AuxBillingCountry'] ?? $data['RegistrantCountry']);
        $this->setApiQueryParam('AuxBillingPhone', $data['AuxBillingPhone'] ?? $data['RegistrantPhone']);
        $this->setApiQueryParam('AuxBillingEmailAddress', $data['AuxBillingEmailAddress'] ?? $data['RegistrantEmailAddress']);

        // Additional Fields
        $this->setApiQueryParam('AddFreeWhoisguard', 'yes');
        $this->setApiQueryParam('WGEnabled', 'yes');
    //     $this->setApiQueryParam('IdnCode', $_GET['IdnCode'] ?? '');
    //     $this->setApiQueryParam('ExtendedAttributes', $_GET['ExtendedAttributes'] ?? '');
    //     $this->setApiQueryParam('Nameservers', $_GET['Nameservers'] ?? '');
    //     $this->setApiQueryParam('IsPremiumDomain', $_GET['IsPremiumDomain'] ?? '');
    //     $this->setApiQueryParam('PremiumPrice', $_GET['PremiumPrice'] ?? '');
    //     $this->setApiQueryParam('EapFee', $_GET['EapFee'] ?? '');

}

    /**
     * Summary of getDomainInfoCommand
     * @param string $domain
     * @return static
     */
    function getDomainNameServerInfoCommand(string $domain){
        $this->command = 'namecheap.domains.dns.getList';
        $this->initQueryParam();
        $this->setApiQueryParam('SLD', strtolower(pathinfo($domain,PATHINFO_FILENAME)));
        $this->setApiQueryParam('TLD', strtolower(pathinfo($domain,PATHINFO_EXTENSION)));
        return $this;
    }
    function setDomainNameServerInfoCommand(string $domain, string $host){
        $this->command = 'namecheap.domains.dns.setCustom';
        $this->initQueryParam();
        $this->setApiQueryParam('SLD', strtolower(pathinfo($domain,PATHINFO_FILENAME)));
        $this->setApiQueryParam('TLD', strtolower(pathinfo($domain,PATHINFO_EXTENSION)));
        $this->setApiQueryParam('Nameservers', $host);
        return $this;
    }
    function getDomainRegisterLockCommand(string $domain){
        $this->command = 'namecheap.domains.getRegistrarLock';
        $this->initQueryParam();
        $this->setApiQueryParam('DomainName',$domain);
        return $this;
    }
    function setDomainRegisterLockCommand(string $domain,$status){
        $this->command = 'namecheap.domains.setRegistrarLock';
        $this->initQueryParam();
        $this->setApiQueryParam('DomainName',$domain);
        $this->setApiQueryParam('LockAction',$status);
        return $this;
    }

    function getDomainContactCommand(string $domain){
        $this->command = 'namecheap.domains.getContacts';
        $this->initQueryParam();
        $this->setApiQueryParam('DomainName',$domain);
        return $this;
    }
    function setDomainContactCommand(string $domain, array $data){
        $this->command = 'namecheap.domains.setContacts';
        $this->initQueryParam();
        $this->setApiQueryParam('DomainName',$domain);

        $this->setDomainContact($data,true);

        return $this;
    }

    function getDomainEmailForwardingCommand(string $domain){
        $this->command = 'namecheap.domains.dns.getEmailForwarding';
        $this->initQueryParam();
        $this->setApiQueryParam('DomainName',$domain);
        return $this;
    }

    function setDomainEmailForwardingCommand(string $domain,$data){
        $this->command = 'namecheap.domains.dns.setEmailForwarding';
        $this->initQueryParam();
        $this->setApiQueryParam('DomainName',$domain);
        foreach ($data['MailBox'] as $key => $value) {
            $this->setApiQueryParam("MailBox".($key+1),strtolower($value));
            $this->setApiQueryParam("ForwardTo".($key+1),$data['ForwardTo'][($key)]);
        }
        return $this;
    }


    function getDomainInfo(string $domain){
        $this->command = 'namecheap.domains.getInfo';
        $this->initQueryParam();
        $this->setApiQueryParam('DomainName',$domain);
        return $this;
    }


    public static function getTransferExtensions(){
        return self::$allowedTransferExtensions;
    }
}



// helper functions

/**
 * 
 * 
 * @param mixed $xml = $domain_result->attributes()
 * @param mixed $actionType reactivate register renew transfer
 * @param mixed $domainName com net org etc
 * @return string|string[]
 */
function getDomainPriceFromXml($xml, $domainName, $actionType = 'register',$currency = 'USD') {
    if ($xml === false) {
        return -1;
    }

    foreach ($xml->CommandResponse->UserGetPricingResult?->ProductType as $productType) {
        foreach ($productType->ProductCategory as $productCategory) {
            if ((string)$productCategory['Name'] === $actionType) {
                foreach ($productCategory->Product as $product) {
                    if ((string)$product['Name'] === $domainName) {
                        foreach ($product->Price as $key => $price) {
                            if($price['Currency']==$currency){
                                $response = [
                                    'Duration' => (string)$price['Duration'], // The duration of the product
                                    'DurationType' => (string)$price['DurationType'], // The duration type of the product
                                    'Price' => (string)$price['Price'], // Indicates Final price (it can be from regular, user price, special price, promo price, tier price)
                                    'RegularPrice' => (string)$price['RegularPrice'], // Indicates regular price
                                    'YourPrice' => (string)$price['YourPrice'], // The user’s price for the product
                                    'AdditionalCost' => (string)$price['AdditionalCost'], // Additional cost associated with the product
                                    'RegularAdditionalCost' => (string)$price['RegularAdditionalCost'], // Regular additional cost
                                    'YourAdditionalCost' => (string)$price['YourAdditonalCost'], // User's additional cost
                                    'PromotionPrice' => (string)$price['PromotionPrice'], // Promotion price
                                    'Currency' => (string)$price['Currency'], // Currency in which the price is listed
                                    'ProductCategory' => (string)$productCategory['Name'], // Product category (e.g., reactivate, register, renew, transfer)
                                    'PricingType' => (string)$price['PricingType'], // Type of pricing (e.g., MULTIPLE)
                                    'RegularPriceType' => (string)$price['RegularPriceType'], // Regular price type
                                    'YourPriceType' => (string)$price['YourPriceType'], // User price type
                                    'RegularAdditionalCostType' => (string)$price['RegularAdditionalCostType'], // Type of regular additional cost
                                    'YourAdditionalCostType' => (string)$price['YourAdditonalCostType'], // Type of user's additional cost
                                ];

                                return $response;
                                
                            }
                        }
                        
                    }
                }
            }
        }
    }

    return 404;
}
/**
 * Summary of getDomainPrice
 * @param Illuminate\Http\Request $request
 * @param mixed $tld_name com net org etc
 * @param mixed $actionType reactivate register renew transfer
 * @param mixed $currency usd
 * @return string|string[]
 */
function getDomainPrice(Request $request ,$tld_name,$actionType = 'register',$currency = 'USD',$withRenewPrice=false){
    $namecheap = (new NamecheapApiController($request))->getDomainPriceCommand($tld_name)->send()->body();
    $price = getDomainPriceFromXml(parseXml($namecheap),$tld_name,$actionType = 'register',$currency = 'USD');
    
    if($withRenewPrice){
        $renewPrice = getDomainPriceFromXml(parseXml($namecheap),$tld_name,$actionType = 'renew',$currency = 'USD');
        if(is_array($price)){
            $price['renewPrice'] = $renewPrice;
        }
    }
    
    return $price;
}

/**
 * Summary of domainPriceToMuliYearPrice
 * @param array $priceData
 * @param int $year
 * @return array [0] = price, [1] icann fee, [2] unit price
 */
function domainPriceToMuliYearPrice(array $priceData, int $year = 1){
    if($year == 1){
        return [
            $priceData['Price'], $priceData['AdditionalCost'], $priceData['Price']
        ];
    }else{
        return [
            $priceData['RegularPrice'] * $year, $priceData['AdditionalCost'] * $year, $priceData['RegularPrice']
        ];
    }
}
