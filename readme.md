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

## Gateways

### Create gateway

https://docs.spreedly.com/gateways/adding

```php
$result = $sly->create_gateway(
						'authorize_net',
						array(
							'login' => 'YOUR_AUTHORIZE_NET_API_LOGIN',
							'password' => 'YOUR_AUTHORIZE_NET_TRANSACTION_KEY'
						)
				);
				
echo $new_gateway->gateway->token;
```

### Update gateway

You can’t update a gateway’s type, but you can update its credentials

https://docs.spreedly.com/gateways/updating

```php
$result = $sly->update_gateway(
						'GATEWAY_TOKEN',
						array(
							'login' => 'YOUR_AUTHORIZE_NET_API_LOGIN',
							'password' => 'YOUR_AUTHORIZE_NET_TRANSACTION_KEY'
						)
				);
				
echo $result->message;
```

### Redact gateway

Gateways can't be deleted, only redacted (disabled, credentials removed)

https://docs.spreedly.com/gateways/redacting

```php
$result = $sly->redact_gateway('GATEWAY_TOKEN');
				
echo $result->message;
```

### List gateways

https://docs.spreedly.com/gateways/getting#getting-all-gateways

```php
$result = $sly->list_gateways();
			
foreach( $result->gateway as $gateway )
{
	echo "(" . $gateway->name . ") ";
	echo $gateway->token;
}
```

### Show gateway

https://docs.spreedly.com/gateways/getting#getting-one-gateway

```php
$result = $sly->show_gateway('GATEWAY_TOKEN');
			
print_r($resul);
```