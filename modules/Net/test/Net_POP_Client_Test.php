<?php
declare(strict_types=1);
/**
 *
 * @test_sandbox true
 *
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

use zesk\PHPUnit\TestCase;

/**
 * @author kent
 *
 */
class Net_POP_Client_Test extends TestCase {
	/**
	 *
	 * @var string
	 */
	private string $url;

	/**
	 *
	 * @var string
	 */
	private string $email;

	/**
	 *
	 * @var array
	 */
	private array $parts = [];

	/**
	 */
	public function test_outgoing_requirements(): void {
		$mail = new Mail($this->application, [], '');
		if (!$mail->option('SMTP_URL')) {
			$this->markTestSkipped($mail::class . '::SMTP_URL not set');
		}
		$this->url = $this->option('url', '');
		if (!URL::valid($this->url)) {
			$this->markTestSkipped(get_class($this) . '::url not valid (' . $this->url . ')');
		}
		$this->email = $this->option('email');
		if (!is_email($this->email)) {
			$this->markTestSkipped(get_class($this) . '::email not valid (' . $this->email . ')');
		}
		$this->parts = URL::parse($this->url);
		$this->assertArrayHasKeys(['schene', 'user', 'pass', 'host', 'port'], $this->parts);
	}

	/**
	 * @return void
	 * @depends test_outgoing_requirements
	 */
	public function test_mail_delivery(): void {
		$test_email = $this->email;
		$test_url = $this->url;

		$options = [
			'echo_log' => true,
		];

		$test_prefix = __CLASS__;

		$test_key = $test_prefix . '-' . md5(microtime());

		$mail = Mail::sendmail($this->application, $test_email, 'no-reply@zesk.com', "Test Subject: $test_key", "This is not a comment. I hold a mild disdain for writing comments, but find them useful when others write them.\n$test_key");

		$this->assertInstanceOf(Mail::class, $mail);

		$n_seconds = 1;
		$success = false;
		$timer = new Timer();
		echo "Searching for subject containing: $test_key\n";
		do {
			$testx = new Net_POP_Client($this->application, $test_url, $options);

			$iterator = $testx->iterator();
			foreach ($iterator as $message_id => $headers) {
				$subject = $headers['subject'] ?? '-no-subject-';
				echo "Examining message id $message_id ... Subject: " . $subject . "\n";
				if (str_contains($subject, $test_prefix) || str_contains($subject, __CLASS__)) {
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

		$this->assertTrue($success, "Ran out of time trying to find $test_prefix");

		echo basename(__FILE__) . ": success\n";
	}

	/**
	 * @depends test_outgoing_requirements
	 */
	public function test_bad_password(): void {
		$this->expectException(Authentication::class);
		$parts = ArrayTools::filter($this->parts, 'scheme;host;user');
		$parts['pass'] = 'bad-password';
		$test_url = URL::stringify($parts);

		$options = [
			'echo_log' => true, 'read_debug' => true, 'debug' => true, 'debug_apop' => true,
		];
		$testx = new Net_POP_Client($this->application, $test_url, $options);

		$testx->authenticate();
	}
}
