<?php declare(strict_types=1);
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
	protected array $load_modules = [
		"session",
	];

	public function test_main(): void {
		$class = __NAMESPACE__ . "\\" . "Session_PHP";

		$this->application->setOption("session_class", $class);

		$request = new Request($this->application);
		$request->initialize_from_settings([
			"url" => "http://localhost/path",
		]);

		$session = $this->application->session($request);

		$this->assert_instanceof($session, $class);

		$this->session_tests($session);
	}

	public function session_tests(Interface_Session $session): void {
		$id = $session->id();
		$this->assert_is_string($id, "Session ID is string");

		$request = new Request($this->application);
		$request->initialize_from_settings([
			"url" => "http://localhost/",
		]);
		$this->assert_false($session->authenticated($request), "Session authenticated");
		$this->assert_null($session->user_id(), "Session user ID is null");
		$this->assert_null($session->user(), "Session user is null");
	}
}
