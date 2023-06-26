<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Session;

use zesk;
use zesk\Interface\SessionInterface;
use zesk\Request;
use zesk\UnitTest;

/**
 * @todo inherit from Session_Test
 *
 * @author kent
 *
 */
class SessionPHPTest extends UnitTest {
	protected array $load_modules = [
		'session',
	];

	public function assertSessionInterface(SessionInterface $session): void {
		$id = $session->id();
		$this->assertIsString($id, 'Session ID is string');
		$this->assertFalse($session->isAuthenticated(), 'Session authenticated');
	}

	public const TEST_CLASS = SessionPHP::class;

	public static function data_basic_session(): array {
		return [
			[function () {
				$app = self::app();
				$app->setOption('session_class', self::TEST_CLASS);

				$request = new Request($app);
				$request->initializeFromSettings([
					'url' => 'http://localhost/path',
				]);

				return $app->session($request);
			}],
		];
	}

	/**
	 * @dataProvider data_basic_session
	 * @param SessionInterface $session
	 * @return void
	 */
	public function test_main($mixed): void {
		$session = $this->applyClosures($mixed);
		$this->assertInstanceOf(self::TEST_CLASS, $session);
		$this->assertSessionInterface($session);
	}

	/**
	 * @param SessionInterface $session
	 * @return void
	 * @expectedException use zesk\Exception\NotFoundException
	 * @dataProvider data_basic_session
	 */
	public function test_user_id_throws(SessionInterface $session): void {
		$this->assertNull($session->userId(), 'Session user ID did not throw');
	}

	/**
	 * @param SessionInterface $session
	 * @return void
	 * @expectedException use zesk\Exception\NotFoundException
	 * @dataProvider data_basic_session
	 */
	public function test_user_throws(SessionInterface $session): void {
		$this->assertNull($session->user(), 'Session user did not throw');
	}
}
