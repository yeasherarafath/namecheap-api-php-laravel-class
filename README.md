# Namecheap API Integration for Laravel

This repository provides a Laravel controller to simplify interactions with the Namecheap API, allowing for easy management of domain registrations and queries.

## Requirements

- PHP 7.3 or higher
- Laravel 7 or higher
- Guzzle HTTP client (for making API requests)

---

## Installation

1. **Install Laravel Project**

   If you don't have a Laravel project, create a new one by running:

   ```bash
   composer create-project --prefer-dist laravel/laravel namecheap-api-integration
   ```

2. **Add the Controller File**

   Place the `NamecheapApiController.php` file in the following directory:

   ```
   app/Http/Controllers/Service/
   ```

3. **Configure Environment Variables**

   Add your Namecheap API credentials to your `.env` file:

   ```dotenv
   NAMECHEAP_USERNAME=your_username
   NAMECHEAP_API_KEY=your_api_key
   ```

4. **Install Guzzle HTTP Client**

   Install the Guzzle HTTP client if you haven't already:

   ```bash
   composer require guzzlehttp/guzzle
   ```

---

## Controller Setup

The main controller is `NamecheapApiController`. In this controller, API credentials are set through the constructor.

```php
public function __construct(Request $request)
{
    $this->namecheapUserName = env('NAMECHEAP_USERNAME', 'YOUR_USERNAME');
    $this->namecheapApiKey = env('NAMECHEAP_API_KEY', 'YOUR_API_KEY');
    // other initialization
}
```

Make sure to replace `'YOUR_USERNAME'` and `'YOUR_API_KEY'` with the actual credentials from your `.env` file.

---

## Available Methods

### 1. **`__construct(Request $request)`**

This is the constructor function that initializes the class with the provided request. It sets up necessary API credentials.

---

### 2. **`setApiHost($host)`**

Sets the base URL or the API host. Used to determine the target API endpoint.

```php
$this->setApiHost('api.namecheap.com');
```

---

### 3. **`initQueryParam($method, $params = [])`**

Initializes query parameters required for API requests. This is called before making an API call.

```php
$this->initQueryParam('namecheap.domains.check', ['DomainList' => $domain]);
```

---

### 4. **`setApiQueryParam($param, $value)`**

Sets a specific query parameter for an API request.

```php
$this->setApiQueryParam('ApiUser', 'your_user');
$this->setApiQueryParam('ApiKey', 'your_api_key');
```

---

### 5. **`getApiURL()`**

Returns the full URL for the API endpoint, combining the host and query parameters.

```php
$url = $this->getApiURL();
```

---

### 6. **`send()`**

Sends the API request to the server and returns the response.

```php
$response = $this->send();
```

---

### 7. **`getDomainListCommand()`**

Fetches the list of domains associated with the user.

```php
$response = $this->getDomainListCommand()->send();
```

---

### 8. **`checkDomainCommand($domain)`**

Checks whether the specified domain is available for registration.

```php
$response = $this->checkDomainCommand('example.com')->send();
```

---

### 9. **`checkDomainTransferCommand($domain)`**

Checks if a domain is eligible for transfer.

```php
$response = $this->checkDomainTransferCommand('example.com')->send();
```

---

### 10. **`makeDomainTransferCommand($domain, $eppCode)`**

Initiates the domain transfer to Namecheap.

```php
$response = $this->makeDomainTransferCommand('example.com', 'EPP_CODE')->send();
```

---

### 11. **`getDomainTransferStatus($domain)`**

Gets the current transfer status of a domain.

```php
$response = $this->getDomainTransferStatus('example.com')->send();
```

---

### 12. **`getDomainPriceCommand($domain)`**

Fetches the registration price of a domain extension (e.g., `.com`, `.net`).

```php
$response = $this->getDomainPriceCommand('com')->send();
```

---

### 13. **`createDomainCommand($domain, $years)`**

Creates a new domain registration for the given domain and period.

```php
$response = $this->createDomainCommand('example.com', 1)->send();
```

---

### 14. **`renewDomainCommand($domain, $years)`**

Renews the specified domain for the given number of years.

```php
$response = $this->renewDomainCommand('example.com', 1)->send();
```

---

### 15. **`setDomainContact($domain, $data)`**

Sets the contact information for the domain (e.g., registrant, admin).

```php
$response = $this->setDomainContact('example.com', [
    'RegistrantFirstName' => 'John',
    'RegistrantLastName' => 'Doe',
])->send();
```

---

### 16. **`getDomainNameServerInfoCommand($domain)`**

Fetches the nameserver information for a specific domain.

```php
$response = $this->getDomainNameServerInfoCommand('example.com')->send();
```

---

### 17. **`setDomainNameServerInfoCommand($domain, $nameservers)`**

Sets custom nameservers for a domain.

```php
$response = $this->setDomainNameServerInfoCommand('example.com', 'ns1.example.com,ns2.example.com')->send();
```

---

### 18. **`getDomainRegisterLockCommand($domain)`**

Fetches the registrar lock status of a domain.

```php
$response = $this->getDomainRegisterLockCommand('example.com')->send();
```

---

### 19. **`setDomainRegisterLockCommand($domain, $lockStatus)`**

Sets the registrar lock status for a domain (e.g., 'LOCK' or 'UNLOCK').

```php
$response = $this->setDomainRegisterLockCommand('example.com', 'LOCK')->send();
```

---

### 20. **`getDomainContactCommand($domain)`**

Gets the contact details associated with a domain.

```php
$response = $this->getDomainContactCommand('example.com')->send();
```

---

### 21. **`setDomainContactCommand($domain, $data)`**

Updates the contact information for a domain.

```php
$response = $this->setDomainContactCommand('example.com', [
    'RegistrantFirstName' => 'John',
    'RegistrantLastName' => 'Doe',
])->send();
```

---

### 22. **`getDomainEmailForwardingCommand($domain)`**

Fetches the email forwarding settings for a domain.

```php
$response = $this->getDomainEmailForwardingCommand('example.com')->send();
```

---

### 23. **`setDomainEmailForwardingCommand($domain, $emailForwardingData)`**

Sets the email forwarding configuration for a domain.

```php
$response = $this->setDomainEmailForwardingCommand('example.com', [
    'ForwardTo' => 'contact@example.com',
])->send();
```

---

### 24. **`getDomainInfo($domain)`**

Gets the full information about a domain.

```php
$response = $this->getDomainInfo('example.com')->send();
```

---

### 25. **`getTransferExtensions()`**

Fetches a list of domain extensions that are eligible for transfer.

```php
$response = $this->getTransferExtensions()->send();
```

---

### 26. **`getDomainPriceFromXml($xmlResponse, $domain, $command, $currency)`**

Extracts the price of a domain from the XML response.

```php
$price = $this->getDomainPriceFromXml($xmlResponse, 'com', 'register', 'USD');
```

---

### 27. **`getDomainPrice($request, $domain, $command, $currency, $withRenewalPrice)`**

Fetches the domain price along with renewal prices if needed.

```php
$price = $this->getDomainPrice($request, 'com', 'register', 'USD', true);
```

---

### 28. **`domainPriceToMuliYearPrice($price, $years)`**

Converts a domain registration price to its multi-year equivalent.

```php
$multiYearPrice = $this->domainPriceToMuliYearPrice($price, 3); // for 3 years
```

---

## Error Handling

If an error occurs, the response will include an error message. You can check the status of the API response to handle errors.

```php
$response = (new NamecheapApiController($request))->checkDomainCommand('example.com')->send();
if ($response->status == 'ERROR') {
    echo 'Error: ' . $response->error->message;
}
```

---

## Conclusion

This integration allows you to interact with Namecheap's API for domain management tasks, such as checking domain availability, transferring domains, updating DNS settings, and more. Simply add your Namecheap credentials to the `.env` file, and you’re ready to use these features in your Laravel project

.

For additional information, please refer to the official [Namecheap API documentation](https://www.namecheap.com/support/api/).
