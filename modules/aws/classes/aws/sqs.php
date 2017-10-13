<?php
/**
* Copyright (c) 2011, Dan Myers.
* Parts copyright (c) 2008, Donovan Schönknecht.
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*
* - Redistributions of source code must retain the above copyright notice,
*   this list of conditions and the following disclaimer.
* - Redistributions in binary form must reproduce the above copyright
*   notice, this list of conditions and the following disclaimer in the
*   documentation and/or other materials provided with the distribution.
*
* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
* AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
* IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
* ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
* LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
* CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
* SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
* INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
* CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
* ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
* POSSIBILITY OF SUCH DAMAGE.
*
* This is a modified BSD license (the third clause has been removed).
* The BSD license may be found here:
* http://www.opensource.org/licenses/bsd-license.php
*
* Amazon SQS is a trademark of Amazon.com, Inc. or its affiliates.
*
* SQS is based on Donovan Schönknecht's Amazon S3 PHP class, found here:
* http://undesigned.org.za/2007/10/22/amazon-s3-php-class
*/

/**
* Amazon SQS PHP class
*
* @link http://sourceforge.net/projects/php-sqs/
* @version 1.0.0
*/
class SQS
{
	const ENDPOINT_US_EAST = 'https://sqs.us-east-1.amazonaws.com/';
	const ENDPOINT_US_WEST = 'https://sqs.us-west-1.amazonaws.com/';
	const ENDPOINT_EU_WEST = 'https://sqs.eu-west-1.amazonaws.com/';
	const ENDPOINT_AP_SOUTHEAST = 'https://sqs.ap-southeast-1.amazonaws.com/';

	const INSECURE_ENDPOINT_US_EAST = 'http://sqs.us-east-1.amazonaws.com/';
	const INSECURE_ENDPOINT_US_WEST = 'http://sqs.us-west-1.amazonaws.com/';
	const INSECURE_ENDPOINT_EU_WEST = 'http://sqs.eu-west-1.amazonaws.com/';
	const INSECURE_ENDPOINT_AP_SOUTHEAST = 'http://sqs.ap-southeast-1.amazonaws.com/';
	
	protected $__accessKey; // AWS Access key
	protected $__secretKey; // AWS Secret key
	protected $__host;

	public function getAccessKey() { return $this->__accessKey; }
	public function getSecretKey() { return $this->__secretKey; }
	public function getHost() { return $this->__host; }

	protected $__verifyHost = true;
	protected $__verifyPeer = true;

	// verifyHost and verifyPeer determine whether curl verifies ssl certificates.
	// It may be necessary to disable these checks on certain systems.
	public function verifyHost() { return $this->__verifyHost; }
	public function enableVerifyHost($enable = true) { $this->__verifyHost = $enable; }

	public function verifyPeer() { return $this->__verifyPeer; }
	public function enableVerifyPeer($enable = true) { $this->__verifyPeer = $enable; }

	/**
	* Constructor - this class cannot be used statically
	*
	* @param string $accessKey Access key
	* @param string $secretKey Secret key
	* @param boolean $useSSL Enable SSL
	* @return void
	*/
	public function __construct($accessKey = null, $secretKey = null, $host = SQS::ENDPOINT_US_EAST) {
		if ($accessKey !== null && $secretKey !== null) {
			$this->setAuth($accessKey, $secretKey);
		}
		$this->__host = $host;
	}

	/**
	* Set AWS access key and secret key
	*
	* @param string $accessKey Access key
	* @param string $secretKey Secret key
	* @return void
	*/
	public function setAuth($accessKey, $secretKey) {
		$this->__accessKey = $accessKey;
		$this->__secretKey = $secretKey;
	}

	/**
	* Create a queue
	*
	* @param string  $queue The queue to create
	* @param integer $visibility_timeout The visibility timeout for the new queue
	* @return An array containing the queue's URL and a request Id
	*/
	public function createQueue($queue, $visibility_timeout = null) {
		$rest = new SQSRequest($this, $this->__host, 'CreateQueue', 'POST');

		$rest->setParameter('QueueName', $queue);

		if($visibility_timeout !== null)
		{
			$rest->setParameter('DefaultVisibilityTimeout', $visibility_timeout);
		}

		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 200)
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false) {
			$this->__triggerError(__FUNCTION__, $rest->error);
			return null;
		}

		$result = array();
		$result['QueueUrl'] = (string)$rest->body->CreateQueueResult->QueueUrl;
		$result['RequestId'] = (string)$rest->body->ResponseMetadata->RequestId;
		return $result;
	}

	/**
	* Delete a queue
	*
	* @param string $queue The queue to delete
	* @return An array containing the request id for this request
	*/
	public function deleteQueue($queue) {
		$rest = new SQSRequest($this, $queue, 'DeleteQueue', 'POST');
		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 200)
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false) {
			$this->__triggerError(__FUNCTION__, $rest->error);
			return null;
		}

		$result = array();
		$result['RequestId'] = (string)$rest->body->ResponseMetadata->RequestId;
		return $result;
	}

	/**
	* Get a list of queues
	*
	* @param string $prefix Only return queues starting with this string (optional)
	* @return An array containing a list of queue URLs and a request Id
	*/
	public function listQueues($prefix = null) {
		$rest = new SQSRequest($this, $this->__host, 'ListQueues', 'GET');

		if($prefix !== null)
		{
			$rest->setParameter('QueueNamePrefix', $prefix);
		}

		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 200)
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false) {
			$this->__triggerError(__FUNCTION__, $rest->error);
			return null;
		}

		$results = array();
		$results['RequestId'] = (string)$rest->body->ResponseMetadata->RequestId;
		$queues = array();
		if(isset($rest->body->ListQueuesResult)) {
			foreach($rest->body->ListQueuesResult->QueueUrl as $q) {
				$queues[] = (string)$q;
			}
		}
		$results['Queues'] = $queues;
		return $results;
	}

	/**
	* Get a queue's attributes
	*
	* @param string $queue The queue for which to retrieve attributes
	* @param string $attribute Which attribute to retrieve (default is 'All')
	* @return An array containing the list of attributes and a request id
	*/
	public function getQueueAttributes($queue, $attribute = 'All') {
		$rest = new SQSRequest($this, $queue, 'GetQueueAttributes', 'GET');

		$rest->setParameter('AttributeName', $attribute);

		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 200)
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false) {
			$this->__triggerError(__FUNCTION__, $rest->error);
			return null;
		}

		$results = array();
		$results['RequestId'] = (string)$rest->body->ResponseMetadata->RequestId;
		$attributes = array();
		if(isset($rest->body->GetQueueAttributesResult)) {
			foreach($rest->body->GetQueueAttributesResult->Attribute as $a) {
				$attributes[(string)$a->Name] = (string)$a->Value;
			}
		}
		$results['Attributes'] = $attributes;
		return $results;
	}

	/**
	* Set attributes on a queue
	*
	* @param string $queue The queue for which to set attributes
	* @param string $attributes An array of name=>value attribute pairs
	* @return An array containing a request id
	*/
	public function setQueueAttributes($queue, $attributes) {
		$rest = new SQSRequest($this, $queue, 'SetQueueAttributes', 'POST');

		$i = 1;
		foreach($attributes as $attribute => $value) {
			$rest->setParameter('Attribute.'.$i.'.Name', $attribute);
			$rest->setParameter('Attribute.'.$i.'.Value', $value);
			$i++;
		}

		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 200)
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false) {
			$this->__triggerError(__FUNCTION__, $rest->error);
			return null;
		}

		$results = array();
		$results['RequestId'] = (string)$rest->body->ResponseMetadata->RequestId;
		return $results;
	}

	/**
	* Send a message to a queue
	*
	* @param string $queue The queue which will receive the message
	* @param string $message The body of the message to send
	* @return An array containing the md5 sum of the message received by SQS, a message id, and a request id
	*/
	public function sendMessage($queue, $message) {
		$rest = new SQSRequest($this, $queue, 'SendMessage', 'POST');

		$rest->setParameter('MessageBody', $message);

		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 200)
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false) {
			$this->__triggerError(__FUNCTION__, $rest->error);
			return null;
		}

		$results = array();
		$results['RequestId'] = (string)$rest->body->ResponseMetadata->RequestId;
		if(isset($rest->body->SendMessageResult)) {
			$results['MD5OfMessageBody'] = (string)$rest->body->SendMessageResult->MD5OfMessageBody;
			$results['MessageId'] = (string)$rest->body->SendMessageResult->MessageId;
		}
		return $results;
	}

	/**
	* Receive a message from a queue
	*
	* @param string  $queue The queue for which to retrieve messages
	* @param integer $num_messages The maximum number of messages to retrieve (optional)
	* @param integer $visibility_timeout The visibility timeout of the retrieved message (optional)
	* @param array   $attributes An array of attributes for each message that you want to retrieve (optional)
	* @return An array containing a list of messages and a request id
	*/
	public function receiveMessage($queue, $num_messages = null, $visibility_timeout = null, $attributes = array()) {
		$rest = new SQSRequest($this, $queue, 'ReceiveMessage', 'GET');

		if($num_messages !== null)
		{
			$rest->setParameter('MaxNumberOfMessages', $num_messages);
		}
		if($visibility_timeout !== null)
		{
			$rest->setParameter('VisibilityTimeout', $visibility_timeout);
		}

		$i = 1;
		foreach($attributes as $attribute) {
			$rest->setParameter('AttributeName.'.$i, $attribute);
			$i++;
		}

		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 200)
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false) {
			$this->__triggerError(__FUNCTION__, $rest->error);
			return null;
		}

		$results = array();
		$results['RequestId'] = (string)$rest->body->ResponseMetadata->RequestId;
		$messages = array();
		if(isset($rest->body->ReceiveMessageResult)) {
			foreach($rest->body->ReceiveMessageResult->Message as $m) {
				$message = array();
				$message['MessageId'] = (string)($m->MessageId);
				$message['ReceiptHandle'] = (string)($m->ReceiptHandle);
				$message['MD5OfBody'] = (string)($m->MD5OfBody);
				$message['Body'] = (string)($m->Body);

				if(isset($m->Attribute)) {
					$attributes = array();
					foreach($m->Attribute as $a) {
						$attributes[(string)$a->Name] = (string)$a->Value;
					}
					$message['Attributes'] = $attributes;
				}

				$messages[] = $message;
			}
		}
		$results['Messages'] = $messages;
		return $results;
	}

	/**
	* Change the visibility timeout setting for a specific message
	*
	* @param string  $queue The queue containing the message to modify
	* @param string  $receipt_handle The receipt handle of the message to modify
	* @param integer $visibility_timeout The new visibility timeout to set on the message, in seconds
	* @return An array containing the request id
	*/
	public function changeMessageVisibility($queue, $receipt_handle, $visibility_timeout) {
		$rest = new SQSRequest($this, $queue, 'ChangeMessageVisibility', 'POST');

		$rest->setParameter('ReceiptHandle', $receipt_handle);
		$rest->setParameter('VisibilityTimeout', $visibility_timeout);

		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 200)
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false) {
			$this->__triggerError(__FUNCTION__, $rest->error);
			return null;
		}

		$results = array();
		$results['RequestId'] = (string)$rest->body->ResponseMetadata->RequestId;
		return $results;
	}

	/**
	* Delete a message from a queue
	*
	* @param string $queue The queue containing the message to delete
	* @param string $receipt_handle The request id of the message to delete
	* @return An array containing the request id
	*/
	public function deleteMessage($queue, $receipt_handle) {
		$rest = new SQSRequest($this, $queue, 'DeleteMessage', 'POST');

		$rest->setParameter('ReceiptHandle', $receipt_handle);

		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 200)
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false) {
			$this->__triggerError(__FUNCTION__, $rest->error);
			return null;
		}

		$results = array();
		$results['RequestId'] = (string)$rest->body->ResponseMetadata->RequestId;
		return $results;
	}

	/**
	* Add access permissions to a queue, for sharing access to queues with other users
	*
	* @param string $queue The queue to which the permission will be added
	* @param string $label A unique identifier for the new permission
	* @param array  $permissions An array of account id => action name
	* @return An array containing the request id
	*/
	public function addPermission($queue, $label, $permissions) {
		$rest = new SQSRequest($this, $queue, 'AddPermission', 'POST');

		$rest->setParameter('Label', $label);
		$i = 1;
		foreach($permissions as $account => $action) {
			$rest->setParameter('AWSAccountId.'.$i, $account);
			$rest->setParameter('ActionName.'.$i, $action);
			$i++;
		}

		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 200)
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false) {
			$this->__triggerError(__FUNCTION__, $rest->error);
			return null;
		}

		$results = array();
		$results['RequestId'] = (string)$rest->body->ResponseMetadata->RequestId;
		return $results;
	}

	/**
	* Remove a permission from a queue
	*
	* @param string $queue The queue to which the permission will be added
	* @param string $label A unique identifier for the new permission
	* @return An array containing the request id
	*/
	public function removePermission($queue, $label) {
		$rest = new SQSRequest($this, $queue, 'RemvoePermission', 'POST');

		$rest->setParameter('Label', $label);

		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 200)
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false) {
			$this->__triggerError(__FUNCTION__, $rest->error);
			return null;
		}

		$results = array();
		$results['RequestId'] = (string)$rest->body->ResponseMetadata->RequestId;
		return $results;
	}

	/**
	* Trigger an error message
	*
	* @internal Used by member functions to output errors
	* @param array $error Array containing error information
	* @return string
	*/
	private function __triggerError($functionname, $error) {
		if($error['curl'])
		{
			trigger_error(sprintf("SQS::%s(): %s", $functionname, $error['code']), E_USER_WARNING);
		}
		else
		{
			$message = sprintf("SQS::%s(): Error %s caused by %s.", $functionname,
								$error['Code'], $error['Type']);
			$message .= sprintf("\nMessage: %s\n", $error['Message']);
			if(strlen($error['Detail']) > 0)
			{
				$message .= sprintf("Detail: %s\n", $error['Detail']);
			}
			trigger_error($message, E_USER_WARNING);
		}
	}
}

final class SQSRequest
{
	private $sqs, $queue, $verb, $expires, $parameters = array();
	public $response;

	/**
	* Constructor
	*
	* @param string $sqs The SQS class object making the request
	* @param string $queue Queue name, without leading slash
	* @param string $action SimpleDB action
	* @param string $verb HTTP verb
	* @param string $accesskey AWS Access Key
	* @param boolean $expires If true, uses Expires instead of Timestamp
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
		$this->response = new STDClass;
		$this->response->error = false;
	}

	/**
	* Set request parameter
	*
	* @param string $key Key
	* @param string $value Value
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
		if($this->expires)
		{
			$this->parameters['Expires'] = gmdate('Y-m-d\TH:i:s\Z');
		}
		else
		{
			$this->parameters['Timestamp'] = gmdate('Y-m-d\TH:i:s\Z');
		}

		$params = array();
		foreach ($this->parameters as $var => $value)
		{
			$params[] = $var.'='.rawurlencode($value);
		}

		sort($params, SORT_STRING);

		$query = implode('&', $params);

		$queue_minus_http = substr($this->queue, strpos($this->queue, '/') + 2);
		$host = substr($queue_minus_http, 0, strpos($queue_minus_http, '/'));
		$uri = substr($queue_minus_http, strpos($queue_minus_http, '/'));

		$headers = array();
		$headers[] = 'Host: '.$host;

		$strtosign = $this->verb."\n".$host."\n".$uri."\n".$query;

		$query .= '&Signature='.rawurlencode($this->__getSignature($strtosign));

		// Basic setup
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_USERAGENT, 'SQS/php');

		if(substr($this->queue, 0, 5) == "https") {
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, ($this->sqs->verifyHost() ? 1 : 0));
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, ($this->sqs->verifyPeer() ? 1 : 0));
		}

		// Request types
		switch ($this->verb) {
			case 'GET': break;
			case 'POST':
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->verb);
				$headers[] = 'Content-Type: application/x-www-form-urlencoded';
			break;
			default: break;
		}

		curl_setopt($curl, CURLOPT_URL, $this->queue.'?'.$query);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
		curl_setopt($curl, CURLOPT_WRITEFUNCTION, array(&$this, '__responseWriteCallback'));
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
			if (!in_array($this->response->code, array(200, 204))
				&& isset($this->response->body->Error)) {
				$this->response->error = array(
					'curl' => false,
					'Type' => (string)$this->response->body->Error->Type,
					'Code' => (string)$this->response->body->Error->Code,
					'Message' => (string)$this->response->body->Error->Message,
					'Detail' => (string)$this->response->body->Error->Detail
				);
				unset($this->response->body);
			}
		}

		return $this->response;
	}

	/**
	* CURL write callback
	*
	* @param resource &$curl CURL resource
	* @param string &$data Data
	* @return integer
	*/
	private function __responseWriteCallback(&$curl, &$data) {
		$this->response->body .= $data;
		return strlen($data);
	}

	/**
	* Generate the auth string using Hmac-SHA256
	*
	* @param string $string String to sign
	* @return string
	*/
	private function __getSignature($string) {
		return base64_encode(hash_hmac('sha256', $string, $this->sqs->getSecretKey(), true));
	}
}

