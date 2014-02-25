# PHP wrapper for the Spreedly Core API

https://spreedly.com/

## Basic setup

Rename `config-example.php` to `config.php` and load your Spreedly API settings:

```php
// Set up Spreedly object

// Environment (e.g. Test)
$sly_environment = 'YOUR_ENVIRONMENT_KEY';

// Access secret (can be personal or app specific)
$sly_access_secret = 'YOUR_ACCESS_SECRET';

// Optional, used for signing callbacks, e.g. for PayPal etc
$sly_signing_secret = 'YOUR_SIGNING_SECRET';

$sly = new Spreedly($sly_environment, $sly_access_secret, $sly_signing_secret);
```

## API methods

### Create gateway

https://docs.spreedly.com/gateways/adding

```php
$new_gateway = $sly->create_gateway(
						'authorize_net',
						array(
							'login' => 'YOUR_AUTHORIZE_NET_API_LOGIN',
							'password' => 'YOUR_AUTHORIZE_NET_TRANSACTION_KEY'
						)
				);
				
echo $new_gateway->gateway->token;
```