<?php
namespace zesk;

/**
 * @require
 * @author kent
 *
 */
class DKIM_Test extends Test_Unit {
	protected $load_modules = array(
		"DKIM"
	);
	
	/**
	 *
	 * @todo Move to dkim module
	 * @see Test_Unit::initialize()
	 */
	function test__dump() {
		$data = null;
		DKIM::_dump($data);
	}
	function test_clean_newlines() {
		$email = null;
		DKIM::clean_newlines($email);
	}
	function test_domainkeys_nofws_canonicalize() {
		$headers = null;
		$body = null;
		DKIM::domainkeys_nofws_canonicalize($headers, $body);
	}
	function test_domainkeys_simple_canonicalize() {
		$headers = null;
		$body = null;
		DKIM::domainkeys_simple_canonicalize($headers, $body);
	}
	function test_dump() {
		$what = null;
		$data = null;
		DKIM::dump($what, $data);
	}
	function test_enabled() {
		DKIM::enabled();
	}
	function test_DKIM() {
		$domain = null;
		$public_key_path = null;
		$private_key_path = null;
		$selector = false;
		$success = false;
		try {
			$testx = new DKIM($domain, $public_key_path, $private_key_path, $selector);
		} catch (Exception_DKIM $e) {
			$success = true;
		}
		$this->assert($success === true);
		
		$this->assert(DKIM::enabled());
		//
		//$headers_lines = null;
		//$body = null;
		//$testx->sign($headers_lines, $body);
		//
		//$testx->dns_txt_record();
		//
		//$what = null;
		//$data = null;
		//DKIM::dump($what, $data);
		
		$path = ZESK_ROOT . 'system/test/DomainKeys-canonicalize/';
		
		for ($i = 1; $i <= 25; $i++) {
			$suffix = "." . str::zero_pad($i);
			
			$email = file::contents($path . "email" . $suffix);
			if ($email === null) {
				continue;
			}
			
			$nofws_expected = file_get_contents($path . "nofws.expected" . $suffix);
			$simple_expected = file_get_contents($path . "simple.expected" . $suffix);
			$purpose = file_get_contents($path . "purpose" . $suffix);
			
			echo "$i: Purpose: $purpose\n";
			
			DKIM::dump("email", $email);
			$email = DKIM::clean_newlines($email);
			list($headers, $body) = pair($email, "\r\n\r\n", $email, '');
			
			DKIM::dump("headers", $headers);
			DKIM::dump("body", $body);
			
			$nofws_generated = DKIM::domainkeys_nofws_canonicalize($headers, $body);
			$simple_generated = DKIM::domainkeys_simple_canonicalize($headers, $body);
			
			$this->assert($simple_generated === $simple_expected, "$i: SIMPLE failed:\ngenerated !== expected\n" . DKIM::_dump($simple_generated) . "\n" . DKIM::_dump($simple_expected));
			$this->assert($nofws_generated === $nofws_expected, "$i: NOFWS failed:\ngenerated !== expected\n" . DKIM::_dump($nofws_generated) . "\n" . DKIM::_dump($nofws_expected));
		}
	}
	function test_DKIM_Exception() {
		$message = null;
		$code = null;
		$testx = new Exception_DKIM($message, $code);
		
		$testx->getMessage();
		
		$testx->getCode();
		
		$testx->getFile();
		
		$testx->getLine();
		
		$testx->getTrace();
		
		$testx->getTraceAsString();
		
		$testx->__toString();
		
		echo basename(__FILE__) . ": success\n";
	}
}
