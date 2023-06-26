<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

use zesk\Exception\SyntaxException;
use zesk\Mail\Mail;
use zesk\PHPUnit\TestCase;

/**
 *
 * @author kent
 *
 */
class MailTest extends TestCase {
	/**
	 *
	 */
	private ?string $url = null;

	/**
	 *
	 */
	private ?string $email = null;

	/**
	 */
	public function test_outgoing_requirements(): void {
		$this->url = $this->option('email_url');

		if (empty($this->url)) {
			$this->markTestSkipped(__CLASS__ . '::email_url not set');
		}

		try {
			$parts = URL::parse($this->url);
		} catch (SyntaxException) {
			$this->markTestSkipped(__CLASS__ . "::email not a valid URL: $this->url");
		}
		$this->email = $this->option('email', $parts['user'] ?? null);
		if (empty($this->email)) {
			$this->markTestSkipped(__CLASS__ . '::email set');
		}
		if ($parts['user'] !== $this->email) {
			$this->markTestSkipped('User ' . $parts['user'] . " !== $this->email\n");
		}
	}

	public function test_load(): void {
		$result = Mail::load(file_get_contents($this->application->zeskHome('test/test-data/mail_load.0.txt')));
		$this->assertEquals([
			'File-Format' => 'both', 'File-Format-Separator' => '--DOG--', 'Subject' => 'This is my dog',
			'From' => 'support@conversionruler.com', 'Reply-To' => 'support@conversionruler.com',
			'body_text' => 'This is a text email', 'body_html' => 'This is an <strong>HTML</strong> message',
		], $result);
	}

	public function test_parseAddress(): void {
		$email = 'John Doe <john@doe.com>';
		$result = Mail::parseAddress($email);
		$expected = [
			'length' => 23, 'text' => 'John Doe <john@doe.com>', 'name' => 'John Doe', 'email' => 'john@doe.com',
			'user' => 'john', 'host' => 'doe.com',
		];
		$this->assertEquals($expected, $result);
	}

	public function test_header_charsets(): void {
		$header = '=?ISO-8859-1?q?Hello?= =?ISO-8859-2?q?This?= =?ISO-8859-3?q?is?= =?ISO-8859-4?q?a?= =?ISO-8859-5?q?test?= =?ISO-8859-4?X?but_ignore_this_part?= ';
		$result = Mail::headerCharsets($header);

		$this->assertEquals(['ISO-8859-1', 'ISO-8859-2', 'ISO-8859-3', 'ISO-8859-4', 'ISO-8859-5',], $result);
	}

	public function test_decode_header(): void {
		$headers = [
			['=?US-ASCII?Q?Keith_Moore?= <moore@cs.utk.edu>', 'Keith Moore <moore@cs.utk.edu>', ['US-ASCII',],], [
				'=?ISO-8859-1?Q?Keld_J=F8rn_Simonsen?= <keld@dkuug.dk>',
				'Keld J' . UTF8::fromCharacterSet(chr(hexdec('F8')), 'ISO-8859-1') . 'rn Simonsen <keld@dkuug.dk>',
				['ISO-8859-1',],
			], [
				'=?ISO-8859-1?Q?Andr=E9?= Pirard <PIRARD@vm1.ulg.ac.be>',
				'Andr' . chr(hexdec('C3')) . chr(hexdec('A9')) . ' Pirard <PIRARD@vm1.ulg.ac.be>', ['ISO-8859-1',],
			], [
				'=?ISO-8859-1?B?SWYgeW91IGNhbiByZWFkIHRoaXMgeW8=?=
 =?ISO-8859-2?B?dSB1bmRlcnN0YW5kIHRoZSBleGFtcGxlLg==?=', 'If you can read this you understand the example.',
				['ISO-8859-1', 'ISO-8859-2',],
			], [
				'Nathaniel Borenstein <nsb@thumper.bellcore.com>
 (=?iso-8859-8?b?7eXs+SDv4SDp7Oj08A==?=)', 'Nathaniel Borenstein <nsb@thumper.bellcore.com>
 (םולש ןב ילטפנ)', ['ISO-8859-8',],
			], [
				'(=?ISO-8859-1?Q?a?=
       =?ISO-8859-1?Q?b?=)', '(ab)', ['ISO-8859-1', 'ISO-8859-1',],
			], [
				'?ISO-8859-1?Q?a?=
       =?ISO-8859-1?Q?b?)', '?ISO-8859-1?Q?a?=
       =?ISO-8859-1?Q?b?)', [],
			], ['(=?ISO-8859-1?Q?a_b?=)', '(a b)', ['ISO-8859-1',],],
			['(=?ISO-8859-1?Q?a?= =?iso-8859-2?q?_b?=)', '(a b)', ['ISO-8859-1', 'ISO-8859-2',],], [
				'(=?ISO-8859-1?Q?a?=













	=?iso-8859-2?q?_b?=)', '(a b)', ['ISO-8859-1', 'ISO-8859-2',],
			],
		];

		foreach ($headers as $header) {
			[$test, $expect, $expected_charsets] = $header;
			$result = Mail::decodeHeader($test);
			$this->assertEquals($expect, $result);
			$charsets = Mail::headerCharsets($test);
			$this->assertEquals($expected_charsets, $charsets);
		}
		$charset = null;
	}

	public function test_is_encoded_header(): void {
		$headers = [
			['=?US-ASCII?Q?Keith_Moore?= <moore@cs.utk.edu>', true,],
			['=?ISO-8859-1?Q?Keld_J=F8rn_Simonsen?= <keld@dkuug.dk>', true,],
			['=?ISO-8859-1?Q?Andr=E9?= Pirard <PIRARD@vm1.ulg.ac.be>', true,], [
				'=?ISO-8859-1?B?SWYgeW91IGNhbiByZWFkIHRoaXMgeW8=?=
 =?ISO-8859-2?B?dSB1bmRlcnN0YW5kIHRoZSBleGFtcGxlLg==?=', true,
			], [
				'Nathaniel Borenstein <nsb@thumper.bellcore.com>
 (=?iso-8859-8?b?7eXs+SDv4SDp7Oj08A==?=)', true,
			], [
				'(=?ISO-8859-1?Q?a?=
       =?ISO-8859-1?Q?b?=)', true,
			], [
				'?ISO-8859-1?Q?a?=
       =?ISO-8859-1?Q?b?)', false,
			], ['(=??Q?a_b?=)', false,], ['(=?a_b?Q??=)', false,], ['(=?bad-charset?Q?data?=)', true,],
			// No charset validation, OK
			['(=?ISO-8859-1?X?a?=)', false,], ['(=?ISO-8859-1?Y?a?=)', false,], ['(=?ISO-8859-1?q?a?=)', true,],
			['(=?ISO-8859-1?Q?a?=)', true,], ['(=?ISO-8859-1?B?a?=)', true,], ['(=?ISO-8859-1?b?a?=)', true,],
		];

		foreach ($headers as $i => $header) {
			[$test, $expect] = $header;
			// echo "Test $i: $test => " . ($expect ? 'true' : 'false') . "\n";
			$result = Mail::isEncodedHeader($test);
			$this->assertEquals($result, $expect);
			if ($result) {
				$this->assertNotCount(0, Mail::headerCharsets($test));
			} else {
				$this->assertCount(0, Mail::headerCharsets($test));
			}
		}
	}

	public function test_load_file(): void {
		$filename = $this->test_sandbox('mail.txt');
		$contents = <<<EOF
File-Format: both
Subject: Test Email
From: support@zesk.com
To: support@example.com

This is the text email.

Thanks,

http://www.example.com/unsubscribe?email={email}
--HTML--
This is the <strong>HTML</strong> email.

Thanks,

<a href="http://www.example.com/unsubscribe?email={email}">Unsubscribe</a>
EOF;
		file_put_contents($filename, $contents);

		$result = Mail::loadFile($filename);

		$this->assertEquals('both', $result['File-Format']);
		$this->assertEquals('Test Email', $result['Subject']);
		$this->assertEquals('support@zesk.com', $result['From']);
		$this->assertEquals('support@example.com', $result['To']);
		$this->assertEquals('This is the text email.

Thanks,

http://www.example.com/unsubscribe?email={email}', $result['body_text'], 'Text failed: ' . $result['body_text']);
		$this->assertEquals('This is the <strong>HTML</strong> email.

Thanks,

<a href="http://www.example.com/unsubscribe?email={email}">Unsubscribe</a>', $result['body_html'], 'HTML Failed: ' . $result['body_html']);
	}

	public function test_loadTheme(): void {
		$application = $this->application;
		$hash = $this->randomHex();
		$file = $this->sandbox('The-Template.tpl');
		file_put_contents($file, "File-Format: html\nSubject: Yo\n\nHello <strong>$hash</strong> you are so {verb}");
		$this->application->addThemePath($this->sandbox());

		$options = ['verb' => 'fun'];
		$this->assertEquals([
			'Subject' => 'Yo', 'File-Format' => 'html', 'body_html' => "Hello <strong>$hash</strong> you are so fun",
		], Mail::loadTheme($application, 'The-Template', $options));
	}

	public function test_mailArray(): void {
		$to = 'noone@zesk.com';
		$from = 'no-reply@zesk' . System::uname();
		$subject = 'This is a subject';
		$array = ['Hello' => 'Name', 'Boo' => 'FEDF',];
		$prefix = '';
		$suffix = '';
		$mail = Mail::mailArray($this->application, $to, $from, $subject, $array, $prefix, $suffix);
		$content = $mail->toDebugHTML();
		$this->assertStringContainsString('mail-debug', $content);
		$this->assertStringContainsString("To: $to", $content);
		$this->assertStringContainsString("Subject: $subject", $content);
	}

	public function test_mailer(): void {
		$headers = [
			'From' => 'no-reply@zesk.com', 'To' => $to = 'noone@example.com',
			'Subject' => $subject = basename(__FILE__),
		];
		$body = "This is the body\n\n--\nnoone@example.com";
		$mail = Mail::mailer($this->application, $headers, $body);
		$content = $mail->toDebugHTML();
		$this->assertStringContainsString('mail-debug', $content);
		$this->assertStringContainsString("To: $to", $content);
		$this->assertStringContainsString("Subject: $subject", $content);
		$this->assertStringContainsString("$body", $content);
	}

	public static function data_is_email(): array {
		return [
			['test@test.com'], ['test@example.com'], ['test@[196.12.42.2]'], ['test@[IPv4::1]'],
		];
	}

	/**
	 * @param $email
	 * @return void
	 * @dataProvider data_is_email
	 */
	public function test_is_email($email): void {
		$this->assertTrue(Types::isEmail($email), "$email is not an email");
	}

	public function test_map(): void {
		$filename = $this->sandbox('testfile.txt');
		file_put_contents($filename, $saved_content = $this->randomHex(128));

		$to = 'dude@example.com';
		$from = 'from@example.com';
		$subject = 'subject';
		$fields = [];
		$cc = '';
		$bcc = '';
		$result = Mail::map($this->application, $to, $from, $subject, $filename, $fields, $cc, $bcc);
		$this->assertInstanceOf(Mail::class, $result);
		$content = $result->toDebugHTML();
		$this->assertStringContainsString('mail-debug', $content);
		$this->assertStringContainsString("To: $to", $content);
		$this->assertStringContainsString("Subject: $subject", $content);
		$this->assertStringContainsString($saved_content, $content);
	}

	public function test_parse_headers(): void {
		$this->assertEquals([
			'Header' => 'Value', 'Another' => 'Thing',
		], Mail::parseHeaders("Header: Value\r\nAnother: Thing"));
	}

	public function test_multipart_send(): void {
		$filename = $this->sandbox('testfile.gif');
		file_put_contents($filename, File::contents($this->application->zeskHome('share/image/zesk-logo.png')));


		$mail_options = [
			'From' => $from = 'noone@zesk.com', 'To' => $to = 'no-reply@zesk.com',
			'Subject' => $subject = $this->randomHex(12),
		];
		$mailName = $this->randomHex(12);
		$attachments = ['file' => $filename, 'name' => "$mailName.png"];
		$result = Mail::multipartFactory($this->application, $mail_options, [$attachments]);
		$this->assertInstanceOf(Mail::class, $result);
		$content = $result->dump();

		$this->assertStringContainsString("$to", $content);
		$this->assertStringContainsString('To:', $content);
		$this->assertStringContainsString($subject, $content);
		$this->assertStringContainsString('Subject:', $content);
		$this->assertStringContainsString("$mailName", $content);
		$this->assertStringContainsString('Content-Type: image/png', $content);
		$this->assertStringNotContainsString('testfile.gif', $content);
		$this->assertStringNotContainsString('zesk.png', $content);
	}

	/**
	 *
	 */
	public function test_send_sms(): void {
		$to = 'John@dude.com';
		$from = 'noone@example.com';
		$subject = 'You are the man!';
		$body = 'All work and no play makes Kent a dull boy.
All work and no play makes Kent a dull boy.
All work and no play makes Kent a dull boy.
All work and no play makes Kent a dull boy.
All work and no play makes Kent a dull boy.
All work and no play makes Kent a dull boy.
All work and no play makes Kent a dull boy.
All work and no play makes Kent a dull boy.
All work and no play makes Kent a dull boy.
';
		$cc = '';
		$bcc = '';
		$headers = [];
		$mail = Mail::sms($this->application, $to, $from, $subject, $body, $cc, $bcc, $headers);
		$this->assertInstanceOf(Mail::class, $mail);
		$mail->setDebug(true);

		$content = $mail->dump();

		$this->assertStringContainsString("$to", $content);
		$this->assertStringContainsString('To:', $content);
		$this->assertStringContainsString($subject, $content);
		$this->assertStringContainsString('Subject:', $content);
		$this->assertStringContainsString(substr($body, 0, 114), $content);
	}

	/**
	 * @depends test_outgoing_requirements
	 */
	public function test_sendmail(): void {
		$pop_url = $this->url;
		if (!URL::valid($pop_url)) {
			$this->markTestSkipped('No POP URL specified in ' . __CLASS__ . '::email_url');
		}
		$to = $this->email;
		$from = 'Don\'t bother replying <no-reply@zesk.com>';
		$subject = 'Test email: AUTO_DELETE';
		$body = 'Test body';
		$cc = '';
		$bcc = '';
		$headers[] = 'X-Bogus-Header: This is a bogus header';

		$this->log("Sending mail to $to");
		$this->assertInstanceOf(Mail::class, Mail::sendmail($this->application, $to, $from, $subject, $body, $cc, $bcc, $headers));

		$test_mailbox = $to;
		$n_seconds = 1;
		$success = false;
		$timer = new Timer();
		do {
			$pop = new Net\POP\Client\Client($this->application, $pop_url, ['echo_log' => false,]);
			$iterator = $pop->iterator();
			foreach ($iterator as $headers) {
				$remote_subject = $headers['subject'] ?? null;
				$this->application->debug("checking subject: $remote_subject");
				if ($remote_subject === $subject) {
					$iterator->current_delete();
					$bogus_header = $headers['x-bogus-header'] ?? null;
					if ($bogus_header === 'This is a bogus header') {
						$success = true;

						break 2;
					}
				}
			}
			echo "Sleeping $n_seconds seconds waiting for delivery ...\n";
			sleep($n_seconds);
			$n_seconds *= 2;
			$pop->disconnect();
		} while ($timer->elapsed() < 300);

		$this->assertTrue($success, "Unable to find message with subject $subject in destination folder");
	}
}
