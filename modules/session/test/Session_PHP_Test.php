<?php
/**
 * 
 */
namespace zesk;

/**
 * @todo inherit from Session_Test
 * 
 * @author kent
 *
 */
class Session_PHP_Test extends Test_Unit {
	protected $load_modules = array(
		"session"
	);
	function test_main() {
		$class = __NAMESPACE__ . "\\" . "Session_PHP";
		
		$this->application->set_option("session_class", $class);
		
		$session = $this->application->session(true);
		
		$this->assert_instanceof($session, $class);
		
		$this->session_tests($session);
	}
	function session_tests(Interface_Session $session) {
		$id = $session->id();
		$this->assert_is_string($id, "Session ID is string");
		
		$this->assert_false($session->authenticated(), "Session authenticated");
		$this->assert_null($session->user_id(), "Session user ID is null");
		$this->assert_null($session->user(), "Session user is null");
	}
}
