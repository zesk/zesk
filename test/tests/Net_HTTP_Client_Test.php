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
    const TEST_URL = "https://marketacumen.com/";

    public function test_all() {
        $url = self::TEST_URL;

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

    public function test_main() {
        $url = self::TEST_URL;

        $result = Net_HTTP_Client::simpleGet($url);
        $this->assertIsString($result);
        $this->log($result);
        $this->assert(strpos($result, "Market Acumen") !== false, $result);
    }

    public function test_url_content_length() {
        $url = self::TEST_URL . "images/marketacumen-logo.png";
        $n = Net_HTTP_Client::url_content_length($this->application, $url);
        $this->assert($n > 0);
    }

    public function test_url_headers() {
        $url = self::TEST_URL;
        $headers = Net_HTTP_Client::url_headers($this->application, $url);
        $this->log($headers);
        $this->assert(begins($headers['Content-Type'], 'text/html'));
    }

    public function test_default_user_agent() {
        $client = new Net_HTTP_Client($this->application);
        $this->assert(strpos($client->default_user_agent(), "zesk") === 0);
        echo basename(__FILE__) . ": success\n";
    }
}
