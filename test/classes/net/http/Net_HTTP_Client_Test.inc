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
		$options = false;
		$x = new Net_HTTP_Client($url, $options);
		
		Net_HTTP_Client::defaultUserAgent();
		
		$default = false;
		$x->userAgent($default);
		
		$value = null;
		$x->setUserAgent($value);
		
		$this->assert($x->isPost() === false);
		
		$x->method(Net_HTTP::Method_POST);
		
		$this->assert($x->isPost() === true);
		
		$x->url();
		
		$x->headers();
		
		$x->responseCode();
		
		$x->responseCodeType();
		
		$x->responseMessage();
		
		$x->responseProtocol();
		
		$x->hasHeaders();
		
		$name = null;
		$default = false;
		$x->header($name, $default);
		
		$x->go();
		
		$url = null;
		Net_HTTP_Client::simpleGet($url);
		
		$x->domain();
		
		$x->__sleep();
		
		$x->option();
		
		$remove = false;
		$x->options_exclude($remove);
		
		$selected = false;
		$x->options_include($selected);
		
		$x->option_keys();
		
		$name = null;
		$checkEmpty = false;
		$x->has_option($name, $checkEmpty);
		
		$mixed = 'temp';
		$value = false;
		$overwrite = true;
		$x->set_option($mixed, $value, $overwrite);
	}

	function test_main() {
		$url = "http://www.marketacumen.com";
		$result = Net_HTTP_Client::simpleGet($url);
		$this->assert(strpos($result, "Market Acumen") !== false, $result);
		
		echo basename(__FILE__) . ": success\n";
	}

	function test_url_content_length() {
		$url = "http://www.marketacumen.com/images/marketacumen-logo.png";
		$n = Net_HTTP_Client::urlContentLength($url);
		$this->assert($n > 0);
		
		echo basename(__FILE__) . ": success\n";
	}

	function test_url_headers() {
		$url = "http://www.marketacumen.com/";
		$headers = Net_HTTP_Client::urlHeaders($url);
		dump($headers);
		$this->assert($headers['Content-Type'] === 'text/html');
	}

	function test_default_user_agent() {
		$this->assert(strpos(Net_HTTP_Client::defaultUserAgent(), "zesk") === 0);
		echo basename(__FILE__) . ": success\n";
	}
}
