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

# Spreedly Core v1 API
# Version: WIP
# Author: Paul Slugocki

class Spreedly {

	// General settings
	protected $_base_url = 'https://core.spreedly.com/v1';
	protected $_api_access_secret = '';
	protected $_environment_key = '';
	protected $_signing_secret = '';
	protected $_response_format = '';

	/**
	 * __construct
	 *
	 * @author Paul Slugocki
	 * @param $api_access_secret
	 * @param $environment_key
	 */
	public function __construct($environment_key, $api_access_secret, $signing_secret = '', $response_format = 'xml') {
		$this->_api_access_secret = $api_access_secret;
		$this->_environment_key = $environment_key;
		$this->_signing_secret = $signing_secret;
		$this->_response_format = $response_format;
	}

	/**
	 * request
	 *
	 * @author Paul Slugocki
	 * @param $path API path to call
	 * @param $data XML data to post
	 * @param $verb HTTP Verb, defaults to POST
	 */
	public function request($path, $data = null, $verb = null, $raw = false) {
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


		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

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

		// convert to SimpleXML/JSON decode
		if(!$raw) {
			if($this->_response_format == "json") {
				$result = json_decode($result);
			} else {
				$result = simplexml_load_string($result);
			}
		}

		return($result);
	}

	/**
	 * get_base_url
	 *
	 * @author Paul Slugocki
	 * @return String
	 */
	public function get_base_url() {
		return $this->_base_url;
	}
	/**
	 * set_base_url
	 *
	 * @author Paul Slugocki
	 * @param $url New base url
	 * @return Boolean
	 */
	public function set_base_url($url) {
		$this->_base_url = $url;
		return true;
	}

	/**
	 * get_response_format
	 *
	 * @author Paul Slugocki
	 * @return String
	 */
	public function get_response_format() {
		return $this->_response_format;
	}
	/**
	 * set_response_format
	 *
	 * @author Paul Slugocki
	 * @param $url New response format (xml/json)
	 * @return Boolean
	 */
	public function set_response_format($response_format) {
		$this->_response_format = $response_format;
		return true;
	}

// Gateways - Options

	public function list_supported_gateways() {

    $path = '/gateways_options.'.$this->_response_format;

    return $this->request($path, null, 'GET');

	}

	public function show_supported_gateway($type) {

		$gateway_list =  simplexml_load_file(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'gateway_types' . DIRECTORY_SEPARATOR . 'list.'.$this->_response_format);

		$xpath = '//gateway[gateway_type="' . $type . '"]';

		$result = $gateway_list->xpath($xpath);

		if(count($result))
			return $result[0];
		else
			return null;

	}

// Gateways - API Methods

	/**
	 * list_gateways
	 *
	 * @author Paul Slugocki
	 * @param $since_token Get next page (of 20) by passing the token from the last gateway on previous list
	 */
	public function list_gateways($since_token = null) {

		$path = '/gateways.'.$this->_response_format;

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

		return $this->request('/gateways/' . $token . '.'.$this->_response_format);

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

            $this->array_to_xml($gateway_settings, $xml);
		}

		return $this->request('/gateways.'.$this->_response_format, $xml);

	}

	/**
	 * retain_gateway
	 *
	 * @author Paul Slugocki
	 * @param $token The token for the gateway to retain
	 */
	public function retain_gateway($token) {

		return $this->request('/gateways/' . $token . '/retain.'.$this->_response_format, null, 'PUT');

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

        $this->array_to_xml($gateway_settings, $xml);

		return $this->request('/gateways/' . $token . '.'.$this->_response_format, $xml, 'PUT');

	}

	/**
	 * redact_gateway
	 *
	 * @author Paul Slugocki
	 * @param $token The token for the gateway to update
	 * @param $gateway_settings Associative array containing the settings
	 */
	public function redact_gateway($token) {

		return $this->request('/gateways/' . $token . '/redact.'.$this->_response_format, null, 'PUT');

	}

	// Transactions

		/**
		 * list_transactions
		 *
		 * @author Paul Slugocki
		 * @param $gateway_token The token for the gateway to list
		 * @param $order  Optionally order, desc
		 * @param $since_token Return transactions since this token, e.g. pagination
		 * @param $count Number of transactions to return, DEFAULT 20
		 */
		public function list_transactions($gateway_token, $order = "", $since_token = "", $count = "") {

			$url = '/gateways/' . $gateway_token . '/transactions.' . $this->_response_format . '?';

			$params = array();

			if($order != "")
				$params["order"] = $order;

			if($since_token != "")
				$params["since_token"] = $since_token;

			if($count != "")
				$params["count"] = $count;

			$url .= http_build_query($params);

			return $this->request($url);

		}

		/**
		 * show_transaction
		 *
		 * @author Paul Slugocki
		 * @param $transaction_token The token for the transaction to show
		 */
		public function show_transaction($transaction_token) {

			return $this->request('/transactions/' . $transaction_token . '.' . $this->_response_format . '?');

		}

		/**
		 * show_transcript
		 *
		 * @author Paul Slugocki
		 * @param $transaction_token The token for the transaction to credit
		 * @param $amount  Optionally either an array of transaction details or a string/float amount
		 */
		public function show_transcript($transaction_token) {

			return $this->request('/transactions/' . $transaction_token . '/transcript', null, null, true);

		}

		/**
		* Complete (3DS 2)
		*
		* @author Paul Slugocki
		* @param $transaction_token The token for the transaction to complete
		*/
		public function complete_transaction($transaction_token) {

		return $this->request('/transactions/' . $transaction_token . '/complete.' . $this->_response_format, null, 'POST');

		}

// Payment methods (cards, banks etc)

	/**
	 * show_payment_method
	 *
	 * @author Paul Slugocki
	 * @param $token The token for the payment method to show
	 */
	public function show_payment_method($token) {

		return $this->request('/payment_methods/' . $token . '.'.$this->_response_format);

	}

	/**
		 * list_payment_method_transactions
		 *
		 * @author Paul Slugocki
		 * @param $token The token for the payment method to show transactions for
		 */
		public function list_payment_method_transactions($token) {

			return $this->request('/payment_methods/' . $token . '/transactions.'.$this->_response_format);

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

        $this->array_to_xml($transaction_details, $xml);

		return $this->request('/gateways/' . $gateway_token . '/purchase.'.$this->_response_format, $xml);

	}

	/**
	 * finalize_purchase (offsite purchases only)
	 *
	 * @author Paul Slugocki
	 * @param $transaction_token The token for the transaction to finalize
	 */
	public function finalize_purchase($transaction_token) {

		return $this->request('/transactions/' . $transaction_token . '.'.$this->_response_format, null, 'PUT');

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

        $this->array_to_xml($transaction_details, $xml);

		return $this->request('/gateways/' . $gateway_token . '/authorize.'.$this->_response_format, $xml);

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

                $this->array_to_xml($transaction_details, $xml);

			} else {

				$xml->addChild('amount', $transaction_details);
			}
		}

		return $this->request('/transactions/' . $transaction_token . '/capture.'.$this->_response_format, $xml);

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

                $this->array_to_xml($transaction_details, $xml);

			} else {

				$xml->addChild('amount', $transaction_details);
			}
		}

		return $this->request('/transactions/' . $transaction_token . '/void.'.$this->_response_format, $xml);

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

		return $this->request('/transactions/' . $transaction_token . '/credit.'.$this->_response_format, $xml);

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
	                $this->array_to_xml($value, $subnode);
	            } else {
	                $this->array_to_xml($value, $xml);
	            }
	        } else {
	            $xml->addChild("$key", htmlspecialchars($value));
	        }
	    }

	}

	/**
	 * ensure_simplexml
	 * If not already XML, assume string and convert
	 */
	private function ensure_simplexml($xml) {

		if(!($xml instanceof SimpleXMLElement)) {
			$xml = simplexml_load_string($xml);
		}

		return $xml;
	}

	/**
	 * verify_signature
	 * Accept a transaction XML string or SimpleXMLElement, return true or false
	 */
	public function verify_transaction_signature($transaction) {

		// Make sure we have SimpleXMLElement
		$transaction = $this->ensure_simplexml($transaction);

		// Make sure we are at the transaction level, not the transactions level
		if(isset($transaction->transaction))
			$transaction = $transaction->transaction[0];

		// Compare the signature in the XML with the one we generate
		return $transaction->signed->signature == $this->generate_transaction_signature($transaction);

	}

	/**
	 * generate_signature
	 * Return the signature for a given transaction XML
	 */
	private function generate_transaction_signature($transaction) {

		$algorithm = $transaction->signed->algorithm;

		$fields_string = $transaction->signed->fields;

		$fields = explode(" ", $fields_string);

		$values = array();

		foreach ($fields as $field) {
			$values[] = $transaction->$field;
		}

		$string_to_sign = implode("|", $values);

		$signature = hash_hmac($algorithm, $string_to_sign, $this->_signing_secret, FALSE );

		return $signature;

	}

}

?>