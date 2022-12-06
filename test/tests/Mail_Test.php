<?php declare(strict_types=1);

/**
 *
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Mail_Test extends UnitTest {
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
		} catch (Exception_Syntax) {
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
		$this->assertEquals($result, ['File-Format' => 'both', 'File-Format-Separator' => '--DOG--', 'Subject' => 'This is my dog', 'From' => 'support@conversionruler.com', 'Reply-To' => 'support@conversionruler.com', 'body_text' => 'This is a text email', 'body_html' => 'This is an <strong>HTML</strong> message', ]);
	}

	public function test_parse_address(): void {
		$email = 'John Doe <john@doe.com>';
		$part = null;
		$result = Mail::parse_address($email, $part);
		$this->assertEquals($result, ['length' => 23, 'text' => 'John Doe <john@doe.com>', 'name' => 'John Doe', 'email' => 'john@doe.com', 'user' => 'john', 'host' => 'doe.com', ], _dump($result));
	}

	public function test_header_charsets(): void {
		$header = '=?ISO-8859-1?q?Hello?= =?ISO-8859-2?q?This?= =?ISO-8859-3?q?is?= =?ISO-8859-4?q?a?= =?ISO-8859-5?q?test?= =?ISO-8859-4?X?but_ignore_this_part?= ';
		$result = Mail::header_charsets($header);

		$this->assertEquals($result, ['ISO-8859-1', 'ISO-8859-2', 'ISO-8859-3', 'ISO-8859-4', 'ISO-8859-5', ]);
	}

	public function test_decode_header(): void {
		$headers = [['=?US-ASCII?Q?Keith_Moore?= <moore@cs.utk.edu>', 'Keith Moore <moore@cs.utk.edu>', ['US-ASCII', ], ], ['=?ISO-8859-1?Q?Keld_J=F8rn_Simonsen?= <keld@dkuug.dk>', 'Keld J' . UTF8::from_charset(chr(hexdec('F8')), 'ISO-8859-1') . 'rn Simonsen <keld@dkuug.dk>', ['ISO-8859-1', ], ], ['=?ISO-8859-1?Q?Andr=E9?= Pirard <PIRARD@vm1.ulg.ac.be>', 'Andr' . chr(hexdec('C3')) . chr(hexdec('A9')) . ' Pirard <PIRARD@vm1.ulg.ac.be>', ['ISO-8859-1', ], ], ['=?ISO-8859-1?B?SWYgeW91IGNhbiByZWFkIHRoaXMgeW8=?=
 =?ISO-8859-2?B?dSB1bmRlcnN0YW5kIHRoZSBleGFtcGxlLg==?=', 'If you can read this you understand the example.', ['ISO-8859-1', 'ISO-8859-2', ], ], ['Nathaniel Borenstein <nsb@thumper.bellcore.com>
 (=?iso-8859-8?b?7eXs+SDv4SDp7Oj08A==?=)', 'Nathaniel Borenstein <nsb@thumper.bellcore.com>
 (םולש ןב ילטפנ)', ['ISO-8859-8', ], ], ['(=?ISO-8859-1?Q?a?=
       =?ISO-8859-1?Q?b?=)', '(ab)', ['ISO-8859-1', 'ISO-8859-1', ], ], ['?ISO-8859-1?Q?a?=
       =?ISO-8859-1?Q?b?)', '?ISO-8859-1?Q?a?=
       =?ISO-8859-1?Q?b?)', [], ], ['(=?ISO-8859-1?Q?a_b?=)', '(a b)', ['ISO-8859-1', ], ], ['(=?ISO-8859-1?Q?a?= =?iso-8859-2?q?_b?=)', '(a b)', ['ISO-8859-1', 'ISO-8859-2', ], ], ['(=?ISO-8859-1?Q?a?=













	=?iso-8859-2?q?_b?=)', '(a b)', ['ISO-8859-1', 'ISO-8859-2', ], ], ];

		foreach ($headers as $header) {
			[$test, $expect, $expected_charsets] = $header;
			$result = Mail::decode_header($test);
			$this->assertEquals($expect, $result);
			$charsets = Mail::header_charsets($test);
			$this->assertEquals($expected_charsets, $charsets);
		}
		$charset = null;
	}

	public function test_debug(): void {
		$set = null;
		Mail::debug($set);
	}

	public function test_is_encoded_header(): void {
		$headers = [['=?US-ASCII?Q?Keith_Moore?= <moore@cs.utk.edu>', true, ], ['=?ISO-8859-1?Q?Keld_J=F8rn_Simonsen?= <keld@dkuug.dk>', true, ], ['=?ISO-8859-1?Q?Andr=E9?= Pirard <PIRARD@vm1.ulg.ac.be>', true, ], ['=?ISO-8859-1?B?SWYgeW91IGNhbiByZWFkIHRoaXMgeW8=?=
 =?ISO-8859-2?B?dSB1bmRlcnN0YW5kIHRoZSBleGFtcGxlLg==?=', true, ], ['Nathaniel Borenstein <nsb@thumper.bellcore.com>
 (=?iso-8859-8?b?7eXs+SDv4SDp7Oj08A==?=)', true, ], ['(=?ISO-8859-1?Q?a?=
       =?ISO-8859-1?Q?b?=)', true, ], ['?ISO-8859-1?Q?a?=
       =?ISO-8859-1?Q?b?)', false, ], ['(=??Q?a_b?=)', false, ], ['(=?a_b?Q??=)', false, ], ['(=?bad-charset?Q?data?=)', true, ], // No charset validation, OK
			['(=?ISO-8859-1?X?a?=)', false, ], ['(=?ISO-8859-1?Y?a?=)', false, ], ['(=?ISO-8859-1?q?a?=)', true, ], ['(=?ISO-8859-1?Q?a?=)', true, ], ['(=?ISO-8859-1?B?a?=)', true, ], ['(=?ISO-8859-1?b?a?=)', true, ], ];

		foreach ($headers as $i => $header) {
			[$test, $expect] = $header;
			// echo "Test $i: $test => " . ($expect ? 'true' : 'false') . "\n";
			$result = Mail::is_encoded_header($test);
			$this->assertEquals($result, $expect);
			if ($result) {
				$this->assertNotEquals(0, count(Mail::header_charsets($test)));
			} else {
				$this->assertEquals(0, count(Mail::header_charsets($test)));
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

		$result = Mail::load_file($filename);

		$this->assertEquals($result['File-Format'], 'both');
		$this->assertEquals($result['Subject'], 'Test Email');
		$this->assertEquals($result['From'], 'support@zesk.com');
		$this->assertEquals($result['To'], 'support@example.com');
		$this->assertEquals($result['body_text'], 'This is the text email.

Thanks,

http://www.example.com/unsubscribe?email={email}', 'Text failed: ' . $result['body_text']);
		$this->assertEquals($result['body_html'], 'This is the <strong>HTML</strong> email.

Thanks,

<a href="http://www.example.com/unsubscribe?email={email}">Unsubscribe</a>', 'HTML Failed: ' . $result['body_html']);
	}

	public function test_load_theme(): void {
		$application = $this->application;
		$hash = $this->randomHex();
		$file = $this->sandbox('The-Template.tpl');
		file_put_contents($file, "File-Format: html\nSubject: Yo\n\nHello <strong>$hash</strong> you are so {verb}");
		$this->application->addThemePath($this->sandbox());

		$options = ['verb' => 'fun'];
		$this->assertEquals(['Subject' => 'Yo', 'File-Format' => 'html', 'body_html' => "Hello <strong>$hash</strong> you are so fun"], Mail::load_theme($application, 'The-Template', $options));
	}

	public function test_mail_array(): void {
		Mail::setDebug(true);

		$to = 'noone@zesk.com';
		$from = 'no-reply@' . System::uname();
		$subject = 'This is a subject';
		$array = ['Hello' => 'Name', 'Boo' => 'FEDF', ];
		$prefix = '';
		$suffix = '';
		Mail::mail_array($this->application, $to, $from, $subject, $array, $prefix, $suffix);
	}

	public function test_mailer(): void {
		Mail::setDebug(true);
		$headers = ['From' => 'no-reply@zesk.com', 'To' => 'noone@example.com', 'Subject' => basename(__FILE__), ];
		$body = "This is the body\n\n--\nnoone@example.com";
		Mail::mailer($this->application, $headers, $body);
	}

	public function test_map(): void {
		$filename = $this->sandbox('testfile.txt');
		file_put_contents($filename, 'fucking');
		$to = 'dude';
		$from = 'from';
		$subject = 'subject';
		$fields = [];
		$cc = null;
		$bcc = null;
		$result = Mail::map($this->application, $to, $from, $subject, $filename, $fields, $cc, $bcc);
		$this->assertInstanceOf(Mail::class, $result);
	}

	public function test_parse_headers(): void {
		$this->assertEquals(['Header' => 'Value', 'Another' => 'Thing'], Mail::parse_headers("Header: Value\r\nAnother: Thing"));
	}

	public function test_multipart_send(): void {
		Mail::setDebug(true);

		$mail_options = ['From' => 'noone@zesk.com', 'To' => 'no-reply@zesk.com', ];
		$attachments = null;
		Mail::multipart_send($this->application, $mail_options, $attachments);
	}

	/**
	 * @depends test_outgoing_requirements
	 */
	public function test_send_sms(): void {
		Mail::setDebug(true);

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
		$cc = false;
		$bcc = false;
		$headers = false;
		Mail::send_sms($this->application, $to, $from, $subject, $body, $cc, $bcc, $headers);
	}

	/**
	 * @depends test_outgoing_requirements
	 */
	public function test_sendmail(): void {
		Mail::debug(false);
		$pop_url = $this->url;
		if (!URL::valid($pop_url)) {
			$this->markTestSkipped('No POP URL specified in ' . __CLASS__ . '::email_url');
		}
		$to = $this->email;
		$from = 'Don\'t bother replying <no-reply@zesk.com>';
		$subject = 'Test email: AUTO_DELETE';
		$body = 'Test body';
		$cc = null;
		$bcc = null;
		$headers[] = 'X-Bogus-Header: This is a bogus header';

		$this->log("Sending mail to $to");
		$this->assertInstanceOf(Mail::class, Mail::sendmail(
			$this->application,
			$to,
			$from,
			$subject,
			$body,
			$cc,
			$bcc,
			$headers
		));

		$test_mailbox = $to;
		$n_seconds = 1;
		$success = false;
		$timer = new Timer();
		do {
			$pop = new Net_POP_Client($this->application, $pop_url, ['echo_log' => false, ]);
			$iterator = $pop->iterator();
			foreach ($iterator as $headers) {
				$remote_subject = avalue($headers, 'subject');
				$this->application->logger->debug("checking subject: $remote_subject");
				if ($remote_subject === $subject) {
					$iterator->current_delete();
					$bogus_header = avalue($headers, 'x-bogus-header');
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
