<?php
/**
 * 
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Net_HTTP_Client_Test extends Test_Unit {
	function test_all() {
		$url = "http://www.marketacumen.com/";
		$x = new Net_HTTP_Client($this->application, $url);
		
		$default = false;
		$x->user_agent($default);
		
		$value = __CLASS__;
		$x->user_agent($value);
		
		$this->assert_equal($x->user_agent(), $value);
		
		$this->assert($x->method_post() === false);
		
		$x->method(Net_HTTP::METHOD_POST);
		
		$this->assert($x->method_post() === true);
		
		$x->url();
		
		$x->request_header(Net_HTTP::REQUEST_CONTENT_TYPE);
		
		$x->response_code();
		
		$x->response_code_type();
		
		$x->response_message();
		
		$x->response_protocol();
		
		$x->response_header(Net_HTTP::RESPONSE_CONTENT_TYPE);
		
		$name = null;
		$default = false;
		$x->response_header($name, $default);
		
		$x->go();
		
		$url = null;
		Net_HTTP_Client::simpleGet($url);
		
		$x->domain();
	}
	function test_main() {
		$url = "http://www.marketacumen.com";
		$result = Net_HTTP_Client::simpleGet($url);
		$this->assert(strpos($result, "Market Acumen") !== false, $result);
	}
	function test_url_content_length() {
		$url = "http://www.marketacumen.com/images/marketacumen-logo.png";
		$n = Net_HTTP_Client::url_content_length($this->application, $url);
		$this->assert($n > 0);
	}
	function test_url_headers() {
		$url = "http://www.marketacumen.com/";
		$headers = Net_HTTP_Client::url_headers($this->application, $url);
		$this->log($headers);
		$this->assert($headers['Content-Type'] === 'text/html');
	}
	function test_default_user_agent() {
		$client = new Net_HTTP_Client($this->application);
		$this->assert(strpos($client->default_user_agent(), "zesk") === 0);
		echo basename(__FILE__) . ": success\n";
	}
}
