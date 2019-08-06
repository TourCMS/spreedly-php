# PHP wrapper for the Spreedly Core API

https://spreedly.com/

## Basic setup

```php
// Set up Spreedly object

// Third parameter, signing secret, is only required for hosted payment pages / 3D Secure
// Fourth parameter, response format, defaults to xml if not provided, can be set to 'json'

$sly = new Spreedly('YOUR_ENVIRONMENT_KEY', 'YOUR_ACCESS_SECRET', 'YOUR_SIGNING_SECRET', 'RESPONSE_FORMAT');
```
### Override response format

The default response format is xml, that can either be changed in the constructor (above) or changed at any point

```php
echo $sly->get_response_format();
$sly->set_response_format('json');
echo $sly->get_response_format();
```

```
xml
json
```

### Override base url

Occasionally Spreedly provide a different URL for testing purposes.

```php
echo $sly->get_base_url();
$sly->set_base_url('https://core-test.spreedly.com/v1');
echo $sly->get_base_url();
```

```
https://core.spreedly.com/v1
https://core-test.spreedly.com/v1
```


## Gateways

### List supported gateways

List supported gateways, including configuration settings
```php
$result = $sly->list_supported_gateways();

foreach($resut->gateway as $g) {
	echo $g->gateway_name . "(" . $g->gateway_type . ")<br />";
}
```

### Show supported gateway

Return the details about a specific supported gateway

```php
$result = $sly->show_supported_gateway('authorize_net');

$print_r($result);
```


### Create gateway

https://docs.spreedly.com/gateways/adding

Spreedly test gateway:

```php
$result = $sly->create_gateway('test');

echo $new_gateway->gateway->token;
```

Real example:

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

Payment methods include credit cards, bank accounts etc. When payment methods are captured in Spreedly (perhaps via a [payment form](https://docs.spreedly.com/payment-methods/adding-with-redirect)) they are assigned a unique token for subsequent purchase/authorization.

### Retaining a payment method

Payment methods captured via transparent redirect are only held temporarily, use "Retain" if you need to keep them active.

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

### Show payment method

Get full details of a payment method.

https://docs.spreedly.com/reference/api/v1/payment_methods/show/

```php
$payment_method = show_payment_method('PAYMENT_METHOD_TOKEN');
print_r($payment_method);
```

### List payment method transactions

Get the list of transactions made on a payment method, across all gateways on an environment. Will include internal Spreedly transactions such as adding and redacting the payment method, in addition to things like purchases.

https://docs.spreedly.com/reference/api/v1/payment_methods/transactions/

```php
$transactions = list_payment_method_transactions('PAYMENT_METHOD_TOKEN');
print_r($transactions);
```

## Using payment methods

### Purchase

A purchase call immediately takes funds from the payment method (assuming the transaction succeeds).

https://docs.spreedly.com/payment-methods/using#purchase

```php
$transaction = $sly->purchase(
					'PAYMENT_METHOD_TOKEN',
					array(
						'amount' => 100,
						'ip' => '127.0.0.1'
						....
					));

print_r($transaction);
```

### Authorize

An authorize works just like a purchase; the difference being that it doesn’t actually take the funds.

https://docs.spreedly.com/payment-methods/using#authorize

```php
$transaction = $sly->authorize(
					'PAYMENT_METHOD_TOKEN',
					array(
						'amount' => 100,
						'ip' => '127.0.0.1'
						....
					));

echo $transaction->token;
```

### Capture

A capture will actually take the funds previously reserved via an authorization.

https://docs.spreedly.com/payment-methods/using#capture

```php
$transaction = $sly->capture('TRANSACTION_TOKEN');

print_r($transaction);
```

Optionally specify an amount - less than the original authorization -  to capture.

```php
$transaction = $sly->capture('TRANSACTION_TOKEN', 50);

print_r($transaction);
```

Or more detailed transaction information.

```php
$transaction = $sly->capture(
						'TRANSACTION_TOKEN',
						array(
							'order_id' => 'ABC123',
							'amount' => 50
						));

print_r($transaction);
```

### Void

Void is used to cancel out authorizations and, with some gateways, to cancel actual payment transactions within the first 24 hours (credits are used after that; see below).

https://docs.spreedly.com/payment-methods/using#void

```php
$transaction = $sly->void('TRANSACTION_TOKEN');

print_r($transaction);
```

Or provide more detailed transaction information.


```php
$transaction = $sly->void(
						'TRANSACTION_TOKEN',
						array(
							'order_id' => 'ABC123'
						));

print_r($transaction);
```

### Credit (Refund)

A credit is like a void, except it actually reverses a charge instead of just canceling a charge that hasn’t yet been made. It’s a refund.

https://docs.spreedly.com/payment-methods/using#credit

```php
$transaction = $sly->credit('TRANSACTION_TOKEN');

print_r($transaction);
```

Optionally specify an amount to refund.

```php
$transaction = $sly->credit('TRANSACTION_TOKEN', 50);

print_r($transaction);
```

Or more detailed transaction information.

```php
$transaction = $sly->credit(
						'TRANSACTION_TOKEN',
						array(
							'order_id' => 'ABC123',
							'amount' => 50
						));

print_r($transaction);
```

## Transactions

### List Transactions

List the transactions on a gateway, paginated, default ordering is oldest first.

https://docs.spreedly.com/reference/api/v1/gateways/transactions/

```php
$transactions = $sly->list_transactions('GATEWAY_TOKEN');
```

Also supports changing the ordering to view the most recent first, plus pagination by providing the "SINCE_TOKEN":

```php
$transactions = $sly->list_transactions('GATEWAY_TOKEN', 'desc', 'SINCE_TOKEN');
```

### Show Transaction

View details of a single gateway transaction.

https://docs.spreedly.com/reference/api/v1/receivers/show/

```php
$transaction = $sly->show_transaction('TRANSACTION_TOKEN');
```


### Show Transcript

This API call allows you to see the full conversation Spreedly had with the payment gateway for a given transaction. You can see exactly what was sent to the gateway and exactly how the gateway responded.

Unlike the other methods in the Spreedly API this returns a plain text log rather than XML.

https://docs.spreedly.com/reference/api/v1/transactions/transcript/

```php
$transcript = $sly->show_transcript('TRANSACTION_TOKEN');

echo $transcript;
```
