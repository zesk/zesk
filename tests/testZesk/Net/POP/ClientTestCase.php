<?php
declare(strict_types=1);
/**
 *
 * @test_sandbox true
 *
 * @package      zesk
 * @subpackage   test
 * @author       kent
 * @copyright    Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk\Net\POP;

use zesk\ArrayTools;
use zesk\Exception\AuthenticationException;
use zesk\Mail;
use zesk\Mail\Mail;
use zesk\Net\POP\Client;
use zesk\PHPUnit\TestCase;
use zesk\Timer;
use zesk\Types;
use zesk\URL;

/**
 * @author kent
 *
 */
class ClientTestCase extends TestCase {
	
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
	public function test_outgoing_requirements(): void
	{
		$this->url = $this->option('url', $this->configuration->getString('SMTP_URL'));
		$this->email = $this->option('email', $this->configuration->getString('MAIL_FROM'));
		if (!$this->url) {
			$this->markTestSkipped('SMTP_URL not set');
		}
		if (!$this->email) {
			$this->markTestSkipped('MAIL_FROM not set');
		}
		if (!URL::valid($this->url)) {
			$this->markTestSkipped(get_class($this) . '::url not valid (' . $this->url . ')');
		}
		if (!Types::isEmail($this->email)) {
			$this->markTestSkipped(get_class($this) . '::email not valid (' . $this->email . ')');
		}
		$this->parts = URL::parse($this->url);
		$this->assertArrayHasKeys([
			'schene',
			'user',
			'pass',
			'host',
			'port',
		], $this->parts);
	}
	
	/**
	 * @return void
	 * @depends test_outgoing_requirements
	 */
	public function test_mail_delivery(): void
	{
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
			$testObject = new Client($this->application, $test_url, $options);
			
			$iterator = $testObject->iterator();
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
			$testObject->disconnect();
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
	public function test_bad_password(): void
	{
		$this->expectException(AuthenticationException::class);
		$parts = ArrayTools::filter($this->parts, 'scheme;host;user');
		$parts['pass'] = 'bad-password';
		$test_url = URL::stringify($parts);
		
		$options = [
			'echo_log'   => true,
			'read_debug' => true,
			'debug'      => true,
			'debug_apop' => true,
		];
		$testObject = new Client($this->application, $test_url, $options);
		
		$testObject->authenticate();
	}
}
