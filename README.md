# Namecheap API Integration for Laravel

This repository provides a Laravel controller to simplify interactions with the Namecheap API, allowing for easy management of domain registrations and queries.

## Features

- **Check Domain Availability**: Verify if a domain is available for registration.
- **Register New Domains**: Register new domains with all required information.
- **Retrieve Domain Pricing**: Get pricing details for various domain types.
- **Transfer Domains**: Check the status of domain transfers.
- **Flexible API Configuration**: Simple setup with your Namecheap API credentials.

# Namecheap API Integration

This repository contains the `NamecheapApiController` class built in Laravel, designed to interact with the Namecheap API. It allows you to manage domains, check availability, transfer statuses, and more. The controller includes various methods that facilitate communication with Namecheap's API endpoints.

## Features

- **Domain Management**
  - Get list of available TLDs.
  - Check domain availability.
  - Transfer domain and get status.
  - Fetch domain pricing.
  - Register or create a new domain.
  
## Methods

### 1. `getDomainListCommand()`
Retrieves a list of available TLDs (Top-Level Domains).

### 2. `checkDomainCommand($domainName)`
Checks the availability of a specific domain.

### 3. `checkDomainTransferCommand($domainName)`
Checks the transfer status of a domain.

### 4. `getDomainPriceCommand($domainTLD)`
Gets the pricing details for a domain extension like `.com`, `.org`, etc.

### 5. `createDomainCommand($data)`
Registers or creates a new domain. Requires details such as:
  - Domain name
  - Registrant information (First Name, Last Name, Address, etc.)
  - Other optional fields like `PromotionCode`, `Nameservers`, `TechContact`, etc.

## Helper Functions

### 1. `getDomainPriceFromXml($xml, $domainName, $actionType = 'register', $currency = 'USD')`
Parses the XML response from Namecheap to extract pricing information for domain registration, renewal, or transfer.

## Setup and Configuration

To integrate with Namecheap's API, you need to configure your API credentials in your `.env` file or a config setting:
- `NAMECHEAP_USERNAME`: Your Namecheap API username.
- `NAMECHEAP_API_KEY`: Your Namecheap API key.
- `NAMECHEAP_CLIENT_IP`: Your server's IP address for API access.

## Example Usage

```php
use App\Http\Controllers\Service\NamecheapApiController;

// Example: Check if domain is available
$controller = new NamecheapApiController($request, true);
$response = $controller->checkDomainCommand('example.com')->send();
echo $response;

// Example: Get domain pricing
$response = $controller->getDomainPriceCommand('com')->send();
