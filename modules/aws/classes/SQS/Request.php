<?php

namespace zesk\AWS\SQS;

final class Request {

	/**
	 *
	 * @var unknown $sqs
	 * @var unknown $queue
	 * @var unknown $verb
	 * @var unknown $expires
	 * @var array $parameters
	 */
	private $sqs, $queue, $verb, $expires, $parameters = array();

	/**
	 *
	 * @var unknown
	 */
	public $response;

	/**
	 * Constructor
	 *
	 * @param string $sqs
	 *        	The SQS class object making the request
	 * @param string $queue
	 *        	Queue name, without leading slash
	 * @param string $action
	 *        	SimpleDB action
	 * @param string $verb
	 *        	HTTP verb
	 * @param string $accesskey
	 *        	AWS Access Key
	 * @param boolean $expires
	 *        	If true, uses Expires instead of Timestamp
	 * @return mixed
	 */
	function __construct($sqs, $queue, $action, $verb, $expires = false) {
		$this->parameters['Action'] = $action;
		$this->parameters['Version'] = '2009-02-01';
		$this->parameters['SignatureVersion'] = '2';
		$this->parameters['SignatureMethod'] = 'HmacSHA256';
		$this->parameters['AWSAccessKeyId'] = $sqs->getAccessKey();

		$this->sqs = $sqs;
		$this->queue = $queue;
		$this->verb = $verb;
		$this->expires = $expires;
		$this->response = new \stdClass();
		$this->response->error = false;
	}

	/**
	 * Set request parameter
	 *
	 * @param string $key
	 *        	Key
	 * @param string $value
	 *        	Value
	 * @return void
	 */
	public function setParameter($key, $value) {
		$this->parameters[$key] = $value;
	}

	/**
	 * Get the response
	 *
	 * @return object | false
	 */
	public function getResponse() {
		if ($this->expires) {
			$this->parameters['Expires'] = gmdate('Y-m-d\TH:i:s\Z');
		} else {
			$this->parameters['Timestamp'] = gmdate('Y-m-d\TH:i:s\Z');
		}

		$params = array();
		foreach ($this->parameters as $var => $value) {
			$params[] = $var . '=' . rawurlencode($value);
		}

		sort($params, SORT_STRING);

		$query = implode('&', $params);

		$queue_minus_http = substr($this->queue, strpos($this->queue, '/') + 2);
		$host = substr($queue_minus_http, 0, strpos($queue_minus_http, '/'));
		$uri = substr($queue_minus_http, strpos($queue_minus_http, '/'));

		$headers = array();
		$headers[] = 'Host: ' . $host;

		$strtosign = $this->verb . "\n" . $host . "\n" . $uri . "\n" . $query;

		$query .= '&Signature=' . rawurlencode($this->__getSignature($strtosign));

		// Basic setup
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_USERAGENT, 'SQS/php');

		if (substr($this->queue, 0, 5) == "https") {
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, ($this->sqs->verifyHost() ? 1 : 0));
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, ($this->sqs->verifyPeer() ? 1 : 0));
		}

		// Request types
		switch ($this->verb) {
			case 'GET':
				break;
			case 'POST':
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->verb);
				$headers[] = 'Content-Type: application/x-www-form-urlencoded';
				break;
			default :
				break;
		}

		curl_setopt($curl, CURLOPT_URL, $this->queue . '?' . $query);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
		curl_setopt($curl, CURLOPT_WRITEFUNCTION, array(
			&$this,
			'__responseWriteCallback'
		));
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

		// Execute, grab errors
		if (curl_exec($curl))
			$this->response->code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		else
			$this->response->error = array(
				'curl' => true,
				'code' => curl_errno($curl),
				'message' => curl_error($curl)
			);

		@curl_close($curl);

		// Parse body into XML
		if ($this->response->error === false && isset($this->response->body)) {
			$this->response->body = simplexml_load_string($this->response->body);

			// Grab SQS errors
			if (!in_array($this->response->code, array(
				200,
				204
			)) && isset($this->response->body->Error)) {
				$this->response->error = array(
					'curl' => false,
					'Type' => (string) $this->response->body->Error->Type,
					'Code' => (string) $this->response->body->Error->Code,
					'Message' => (string) $this->response->body->Error->Message,
					'Detail' => (string) $this->response->body->Error->Detail
				);
				unset($this->response->body);
			}
		}

		return $this->response;
	}

	/**
	 * CURL write callback
	 *
	 * @param
	 *        	resource &$curl CURL resource
	 * @param
	 *        	string &$data Data
	 * @return integer
	 */
	private function __responseWriteCallback(&$curl, &$data) {
		$this->response->body .= $data;
		return strlen($data);
	}

	/**
	 * Generate the auth string using Hmac-SHA256
	 *
	 * @param string $string
	 *        	String to sign
	 * @return string
	 */
	private function __getSignature($string) {
		return base64_encode(hash_hmac('sha256', $string, $this->sqs->getSecretKey(), true));
	}
}

