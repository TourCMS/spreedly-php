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
			
print_r($result);
```

## Managing payment methods

Payment methods include credit cards, bank accounts etc. When payment methods are captured in Spreedly (perhaps via a [payment form](https://docs.spreedly.com/payment-methods/adding-with-redirect)) they are assigned a unique token for subsequent use.

### Retaining a payment method

Payment methods captured via transparent redirect are only held temporarily, use "Retain" to keep them active.

https://docs.spreedly.com/payment-methods/storing#retaining-a-payment-method

```php
$result = $sly->retain('PAYMENT_METHOD_TOKEN');
			
print $result->message;
```

### Redact a payment method

Rather than delete a payment method, in Core you “redact” it, removing all sensitive information but leaving a place for any transactions to hang off of.

https://docs.spreedly.com/payment-methods/storing#redacting-a-payment-method

```php
$result = $sly->redact('PAYMENT_METHOD_TOKEN');
			
print $result->message;
```

### Remove a payment method from the gateway

Most of the time, simply redacting a payment method will suffice because payment methods are for the most part only stored in Spreedly. There are times though when a payment method is stored on the gateway and you’d like to notify the gateway that it can no longer be used.

https://docs.spreedly.com/payment-methods/storing#removing-a-payment-method-from-a-gateway

```php
$result = $sly->remove_from_gateway('PAYMENT_METHOD_TOKEN', 'GATEWAY_TOKEN');
			
print $result->message;
```

## Using payment methods

### Purchase

A purchase call immediately takes funds from the payment method (assuming the transaction succeeds).

https://docs.spreedly.com/payment-methods/using#purchase

```php
$transaction = $sly->purchase(
					'PAYMENT_METHOD_TOKEN',
					'amount' => 100,
					'ip' => '127.0.0.1'
					....
					);
			
print_r($transaction);
```

### Authorize

An authorize works just like a purchase; the difference being that it doesn’t actually take the funds.

https://docs.spreedly.com/payment-methods/using#authorize

```php
$transaction = $sly->authorize(
					'PAYMENT_METHOD_TOKEN',
					'amount' => 100,
					'ip' => '127.0.0.1'
					....
					);
			
echo $transaction->token;
```

### Capture

A capture will actually take the funds previously reserved via an authorization.

https://docs.spreedly.com/payment-methods/using#capture

```php
$transaction = $sly->capture('TRANSACTION_TOKEN');
			
print_r($transaction);
```

### Void

Void is used to cancel out authorizations and, with some gateways, to cancel actual payment transactions within the first 24 hours (credits are used after that; see below).

https://docs.spreedly.com/payment-methods/using#void

```php
$transaction = $sly->capture('TRANSACTION_TOKEN');
			
print_r($transaction);
```

### Credit

A credit is like a void, except it actually reverses a charge instead of just canceling a charge that hasn’t yet been made. It’s a refund.

https://docs.spreedly.com/payment-methods/using#credit

```php
$transaction = $sly->capture('TRANSACTION_TOKEN');
			
print_r($transaction);
```

