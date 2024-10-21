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

    private $allowedTransferExtensions = [
        'biz', 'ca', 'cc', 'co', 'com', 'com.es', 'com.pe', 'es', 
        'in', 'info', 'me', 'mobi', 'net', 'net.pe', 'nom.es', 
        'org', 'org.es', 'org.pe', 'pe', 'tv', 'us'
    ];

    function __construct(Request $request, $autoSetIp = false){

        $this->setting = getSetting();

        $this->namecheapUserName = getConfig('namecheap_username') ?? 'NO_API_USERNAME';
        $this->namecheapApiKey =  getConfig('namecheap_api_key') ??'NO_API_KEY';

        if($autoSetIp){
            $this->clientIP = $request->ip();
        }else{
            $this->clientIP = '***.***.***.***';
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
        $url = $this->apiHost.'?'.Arr::query($this->queryParam);;
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
        // Mandatory Fields
        $this->setApiQueryParam('DomainName', $data['DomainName']);
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
        $this->setApiQueryParam('IdnCode', $_GET['IdnCode'] ?? '');
        $this->setApiQueryParam('ExtendedAttributes', $_GET['ExtendedAttributes'] ?? '');
        $this->setApiQueryParam('Nameservers', $_GET['Nameservers'] ?? '');
        $this->setApiQueryParam('AddFreeWhoisguard', $_GET['AddFreeWhoisguard'] ?? 'no');
        $this->setApiQueryParam('WGEnabled', $_GET['WGEnabled'] ?? 'no');
        $this->setApiQueryParam('IsPremiumDomain', $_GET['IsPremiumDomain'] ?? '');
        $this->setApiQueryParam('PremiumPrice', $_GET['PremiumPrice'] ?? '');
        $this->setApiQueryParam('EapFee', $_GET['EapFee'] ?? '');

        return $this;
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
