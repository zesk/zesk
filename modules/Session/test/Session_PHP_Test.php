<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Session\Session\test;

use zesk;
use zesk\Interface_Session;
use zesk\Request;
use zesk\UnitTest;

/**
 * @todo inherit from Session_Test
 *
 * @author kent
 *
 */
class Session_PHP_Test extends UnitTest {
	protected array $load_modules = [
		'session',
	];

	public const TEST_CLASS = __NAMESPACE__ . '\\' . 'Session_PHP';

	public static function data_basic_session(): array {
		$this->application->setOption('session_class', self::TEST_CLASS);

		$request = new Request($this->application);
		$request->initializeFromSettings([
			'url' => 'http://localhost/path',
		]);

		$session = $this->application->session($request);

		return [
			[$session],
		];
	}

	/**
	 * @dataProvider data_basic_session
	 * @param Interface_Session $session
	 * @return void
	 */
	public function test_main(Interface_Session $session): void {
		$this->assert_instanceof($session, self::TEST_CLASS);

		$this->session_tests($session);
	}

	public function session_tests(Interface_Session $session): void {
		$id = $session->id();
		$this->assertIsString($id, 'Session ID is string');

		$request = new Request($this->application);
		$request->initializeFromSettings([
			'url' => 'http://localhost/',
		]);
		$this->assertFalse($session->isAuthenticated(), 'Session authenticated');
	}

	/**
	 * @param Interface_Session $session
	 * @return void
	 * @expectedException zesk\Exception_NotFound
	 * @dataProvider data_basic_session
	 */
	public function test_user_id_throws(Interface_Session $session): void {
		$this->assertNull($session->userId(), 'Session user ID did not throw');
	}

	/**
	 * @param Interface_Session $session
	 * @return void
	 * @expectedException zesk\Exception_NotFound
	 * @dataProvider data_basic_session
	 */
	public function test_user_throws(Interface_Session $session): void {
		$this->assertNull($session->user(), 'Session user did not throw');
	}
}
