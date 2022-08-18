<?php declare(strict_types=1);
/**
 *
 * @test_sandbox true
 *
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/**
 * @author kent
 *
 */
class Net_POP_Client_Test extends UnitTest {
	/**
	 *
	 * @var string
	 */
	private $url = null;

	/**
	 *
	 * @var string
	 */
	private $email = null;

	/**
	 *
	 * @var array
	 */
	private $parts = [];

	/**
	 */
	protected function initialize(): void {
		$this->url = $this->option('email_url');

		if (empty($this->url)) {
			$this->markTestSkipped(__CLASS__ . '::email_url not set');
		}
		$this->parts = URL::parse($this->url);
		if (empty($this->parts)) {
			$this->markTestSkipped(__CLASS__ . "::email not a valid URL: $this->url");
		}
		$this->email = $this->option('email', avalue($this->parts, 'user'));
		if (empty($this->email)) {
			$this->markTestSkipped(__CLASS__ . '::email set');
		}
		if ($this->parts['user'] !== $this->email) {
			$this->markTestSkipped('User ' . $this->parts['user'] . " !== $this->email\n");
		}
	}

	public function test_mail_delivery(): void {
		$test_email = $this->email;
		$test_url = $this->url;

		$options = [
			'echo_log' => true,
		];

		$test_prefix = __CLASS__;

		$test_key = $test_prefix . '-' . md5(microtime());

		$mail = Mail::sendmail($this->application, $test_email, 'no-reply@zesk.com', "Test Subject: $test_key", "This is not a comment. I hold a mild disdain for writing comments, but find them useful when others write them.\n$test_key");

		$this->assert_instanceof($mail, 'zesk\\Mail');

		$n_seconds = 1;
		$success = false;
		$timer = new Timer();
		echo "Searching for subject containing: $test_key\n";
		do {
			$testx = new Net_POP_Client($this->application, $test_url, $options);

			$iterator = $testx->iterator();
			foreach ($iterator as $message_id => $headers) {
				$subject = avalue($headers, 'subject', '-no-subject-');
				echo "Examining message id $message_id ... Subject: " . $subject . "\n";
				if (str_contains($subject, $test_prefix)   || str_contains($subject, __CLASS__)) {
					// Delete other tests
					echo "Deleting message id $message_id\n";
					$iterator->current_delete();
				}
				if (str_contains($subject, $test_key)) {
					$success = true;
				}
			}
			$testx->disconnect();
			if ($success) {
				break;
			}
			echo "Sleeping $n_seconds seconds waiting for delivery ...\n";
			sleep($n_seconds);
			$n_seconds *= 2;
		} while ($timer->elapsed() < 300);

		$this->assert($success, "Ran out of time trying to find $test_prefix");

		echo basename(__FILE__) . ": success\n";
	}

	/**
	 * @expectedException zesk\Exception_Authentication
	 */
	public function test_bad_password(): void {
		$parts = ArrayTools::filter($this->parts, 'scheme;host;user');
		$parts['pass'] = 'bad-password';
		$test_url = URL::unparse($parts);

		$options = [
			'echo_log' => true,
			'read_debug' => true,
			'debug' => true,
			'debug_apop' => true,
		];
		$testx = new Net_POP_Client($this->application, $test_url, $options);

		$testx->authenticate();
	}
}
