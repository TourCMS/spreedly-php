<?php
/*
Copyright (c) 2014 Travel UCD

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

# Spreedly v1 API
# Version: WIP
# Author: Paul Slugocki

class Spreedly {

	// General settings
	protected $_base_url = 'https://core.spreedly.com/v1';
	protected $_api_access_secret = '';
	protected $_environment_key = '';
	protected $_signing_secret = '';
	
	/**
	 * __construct
	 *
	 * @author Paul Slugocki
	 * @param $api_access_secret
	 * @param $environment_key
	 */
	public function __construct($environment_key, $api_access_secret, $signing_secret = '') {
		$this->_api_access_secret = $api_access_secret;
		$this->_environment_key = $environment_key;
		$this->_signing_secret = $signing_secret;
	}
	
	/**
	 * request
	 *
	 * @author Paul Slugocki
	 * @param $path API path to call
	 * @param $data XML data to post
	 * @param $verb HTTP Verb, defaults to POST
	 */
	public function request($path, $data = null, $verb = null) {
		// Prepare the URL we are sending to
		$url = $this->_base_url.$path;

		// Build headers
		//$headers = array("Content-type: text/xml;charset=\"utf-8\"");
		$headers = array("Content-type: application/xml;");
		
				
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 0 );
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_USERPWD, $this->_environment_key . ":" . $this->_api_access_secret);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		
		// If we have a verb set it
		if(!empty($verb)) {
			curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $verb );
			
			if(empty($data)) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, '');
			}
		}
		
		// If we have some XML data to post add it
		if(!empty($data)) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data->asXml());
		}
		
		$response = curl_exec($ch);
		
		// Strip out the result
		$header_size = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
		$result = substr( $response, $header_size );
		// convert to SimpleXML
		$result = simplexml_load_string($result);
		return($result);
	}
	
// Gateways - Non API methods

	public function refresh_supported_gateways() {
	
		$gateway_dir =  dirname(__FILE__) . DIRECTORY_SEPARATOR . 'gateway_types';
		
		$output_xml = new SimpleXMLElement('<gateways />');
		
		if ($handle = opendir($gateway_dir . DIRECTORY_SEPARATOR . 'specs')) {
		
		    while (false !== ($file = readdir($handle))) {
		        if ('.' === $file) continue;
		        if ('..' === $file) continue;
				if ('xml' != substr($file, -3)) continue;
		        
		        // do something with the file
		        $gateway_xml = simplexml_load_file($gateway_dir . DIRECTORY_SEPARATOR . 'specs' . DIRECTORY_SEPARATOR . $file);
		        
		        $gateway = $output_xml->addChild('gateway');
		        $gateway->addChild('gateway_type', $gateway_xml->gateway_type);
		        $gateway->addChild('name', $gateway_xml->name);
		        
		    }
		    
		    closedir($handle);
		    
		    $output_file = fopen($gateway_dir . DIRECTORY_SEPARATOR . 'list.xml', 'w') or die('Cannot open file:'); //
		 
		    fwrite($output_file, $output_xml->asXml());
		    
		    fclose($output_file);
		    
		}
	}
	
	public function list_supported_gateways() {
		
		$gateway_list =  dirname(__FILE__) . DIRECTORY_SEPARATOR . 'gateway_types' . DIRECTORY_SEPARATOR . 'list.xml';
		
		return simplexml_load_file($gateway_list);
		
	}
	
	public function show_supported_gateway($type) {
		
		$gateway_list =  dirname(__FILE__) . DIRECTORY_SEPARATOR . 'gateway_types' . DIRECTORY_SEPARATOR . 'specs' . DIRECTORY_SEPARATOR . $type . '.xml';
		
		return simplexml_load_file($gateway_list);
		
	}
	
// Gateways - API Methods
	
	/**
	 * list_gateways
	 *
	 * @author Paul Slugocki
	 * @param $since_token Get next page (of 20) by passing the token from the last gateway on previous list
	 */
	public function list_gateways($since_token = null) {
		
		$path = '/gateways.xml';
		
		if(!empty($since_token))
			$path .= '?since_token=' . $since_token;
			
		return $this->request($path);
		
	}
	
	/**
	 * show_gateway
	 *
	 * @author Paul Slugocki
	 * @param $token Token for the gateway to show
	 */
	public function show_gateway($token) {
	
		return $this->request('/gateways/' . $token . '.xml');
		
	}
	
	/**
	 * create_gateway
	 *
	 * @author Paul Slugocki
	 * @param $gateway_type E.g. The Spreedly gateway type to create "authorize_net"
	 * @param $gateway_settings Associative array containing the settings
	 */
	public function create_gateway($gateway_type, $gateway_settings = null) {
		
		$xml = null;
		
		if(!empty($gateway_settings)) {
		
			$xml = new SimpleXMLElement('<gateway />');
			
			$xml->addChild('gateway_type', $gateway_type);
			
			foreach($gateway_settings as $key => $setting) {
				$xml->addChild($key, $setting);
			}
			
		}
		
		return $this->request('/gateways.xml', $xml);
	
	}
	
	/**
	 * retain_gateway
	 *
	 * @author Paul Slugocki
	 * @param $token The token for the gateway to retain
	 */
	public function retain_gateway($token) {

		return $this->request('/gateways/' . $token . '/retain.xml', null, 'PUT');
	
	}
	
	/**
	 * update_gateway
	 *
	 * @author Paul Slugocki
	 * @param $token The token for the gateway to update
	 * @param $gateway_settings Associative array containing the settings
	 */
	public function update_gateway($token, $gateway_settings) {
		
		$xml = new SimpleXMLElement('<gateway />');
		
		foreach($gateway_settings as $key => $setting) {
			$xml->addChild($key, $setting);
		}
		
		return $this->request('/gateways/' . $token . '.xml', $xml, 'PUT');
	
	}
	
	/**
	 * redact_gateway
	 *
	 * @author Paul Slugocki
	 * @param $token The token for the gateway to update
	 * @param $gateway_settings Associative array containing the settings
	 */
	public function redact_gateway($token) {
	
		return $this->request('/gateways/' . $token . '/redact.xml', null, 'PUT');
	
	}

// Payment methods (cards, banks etc)

	/**
	 * show_payment_method
	 *
	 * @author Paul Slugocki
	 * @param $token The token for the payment method to show
	 */
	public function show_payment_method($token) {
	
		return $this->request('/payment_methods/' . $token . '.xml');
	
	}

// Transactions
	
	// Purchase

	/**
	 * purchase
	 *
	 * @author Paul Slugocki
	 * @param $gateway_token The token for the gateway to use for this purchase
	 * @param $transaction_details Associative array containing the transaction details
	 */
	public function purchase($gateway_token, $transaction_details) {
	
		$xml = new SimpleXMLElement('<transaction/>');
		
		foreach($transaction_details as $key => $detail) {
			$xml->addChild($key, $detail);
		}
	
		return $this->request('/gateways/' . $gateway_token . '/purchase.xml', $xml);
	
	}
	
	/**
	 * finalize_purchase (offsite purchases only)
	 *
	 * @author Paul Slugocki
	 * @param $transaction_token The token for the transaction to finalize
	 */
	public function finalize_purchase($transaction_token) {
	
		return $this->request('/transactions/' . $transaction_token . '.xml', null, 'PUT');
	
	}
	
	// Authorize
	
	/**
	 * authorize
	 *
	 * @author Paul Slugocki
	 * @param $gateway_token The token for the gateway to use for this purchase
	 * @param $transaction_details Associative array containing the transaction details
	 */
	public function authorize($gateway_token, $transaction_details) {
	
		$xml = new SimpleXMLElement('<transaction/>');
		
		foreach($transaction_details as $key => $detail) {
			$xml->addChild($key, $detail);
		}
	
		return $this->request('/gateways/' . $gateway_token . '/authorize.xml', $xml);
	
	}
	
	/**
	 * capture (take authorized funds)
	 *
	 * @author Paul Slugocki
	 * @param $transaction_token The token for the previous authorize transaction to capture
	 * @param $amount Optionally either an array of transaction details or a string/float amount
	 */
	public function capture($transaction_token, $transaction_details = null) {
	
		$xml = null;
		
		if(!empty($transaction_details)) {
			
			$xml = new SimpleXMLElement('<transaction/>');
			
			if(is_array($transaction_details)) {
			
				foreach($transaction_details as $key => $detail) {
					$xml->addChild($key, $detail);
				}
			
			} else {
			
				$xml->addChild('amount', $transaction_details);
			}
		}
		
		return $this->request('/transactions/' . $transaction_token . '/capture.xml', $xml);
	
	}
	
	// Void
	
	/**
	 * void (authorization or, for some gateways, recent actual payment)
	 *
	 * @author Paul Slugocki
	 * @param $transaction_token The token for the transaction to void
	 * @param $amount Optionally either an array of transaction details or a string/float amount
	 */
	public function void($transaction_token, $transaction_details = null) {
	
		$xml = null;
		
		if(!empty($transaction_details)) {
			
			$xml = new SimpleXMLElement('<transaction/>');
			
			if(is_array($transaction_details)) {
			
				foreach($transaction_details as $key => $detail) {
					$xml->addChild($key, $detail);
				}
			
			} else {
			
				$xml->addChild('amount', $transaction_details);
			}
		}
		
		return $this->request('/transactions/' . $transaction_token . '/void.xml', $xml);
	
	}
	
	// Credit
	
	/**
	 * credit Reverse a charge (refund)
	 *
	 * @author Paul Slugocki
	 * @param $transaction_token The token for the transaction to credit
	 * @param $amount  Optionally either an array of transaction details or a string/float amount
	 */
	public function credit($transaction_token, $transaction_details = null) {
	
		$xml = null;
		
		if(!empty($transaction_details)) {
			
			$xml = new SimpleXMLElement('<transaction/>');
			
			if(is_array($transaction_details)) {
			
				foreach($transaction_details as $key => $detail) {
					$xml->addChild($key, $detail);
				}
			
			} else {
			
				$xml->addChild('amount', $transaction_details);
			}
		}
		
		return $this->request('/transactions/' . $transaction_token . '/credit.xml', $xml);
	
	}
	
	// Transcript
	
	/**
	 * show_transcript
	 *
	 * @author Paul Slugocki
	 * @param $transaction_token The token for the transaction to credit
	 * @param $amount  Optionally either an array of transaction details or a string/float amount
	 */
	public function show_transcript($transaction_token) {

		return $this->request('/transactions/' . $transaction_token . '/transcript');
	
	}

// Helpers
	
	/**
	 * array_to_xml
	 *
	 * @author http://stackoverflow.com/questions/1397036/how-to-convert-array-to-simplexml/5965940#5965940
	 */
	private function array_to_xml($array, &$xml) {

	    foreach($array as $key => $value) {
	        if(is_array($value)) {
	            if(!is_numeric($key)){
	                $subnode = $xml->addChild("$key");
	                array_to_xml($value, $subnode);
	            } else {
	                array_to_xml($value, $xml);
	            }
	        } else {
	            $xml->addChild("$key","$value");
	        }
	    }

	}
	
}

?>