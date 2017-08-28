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
class Mail_Test extends Test_Unit {
	/**
	 *
	 * @var unknown
	 */
	private $url = null;
	/**
	 *
	 * @var unknown
	 */
	private $email = null;
	/**
	 *
	 * @var array
	 */
	private $parts = array();
	function test_load() {
		$filename = null;
		$result = Mail::load(file_get_contents(dirname(__FILE__) . '/test-data/mail_load.0.txt'));
		$this->assert_arrays_equal($result, array(
			'File-Format' => 'both',
			'File-Format-Separator' => '--DOG--',
			'Subject' => 'This is my dog',
			'From' => 'support@conversionruler.com',
			'Reply-To' => 'support@conversionruler.com',
			'body_text' => 'This is a text email',
			'body_html' => 'This is an <strong>HTML</strong> message'
		));
	}
	function test_parse_address() {
		$email = "John Doe <john@doe.com>";
		$part = null;
		$result = Mail::parse_address($email, $part);
		$this->assert_arrays_equal($result, array(
			'length' => 23,
			'text' => "John Doe <john@doe.com>",
			'name' => "John Doe",
			'email' => "john@doe.com",
			'user' => "john",
			'host' => "doe.com"
		), _dump($result));
	}
	function test_header_charsets() {
		$header = "=?ISO-8859-1?q?Hello?= =?ISO-8859-2?q?This?= =?ISO-8859-3?q?is?= =?ISO-8859-4?q?a?= =?ISO-8859-5?q?test?= =?ISO-8859-4?X?but_ignore_this_part?= ";
		$result = Mail::header_charsets($header);
		
		$this->assert_arrays_equal($result, array(
			"ISO-8859-1",
			"ISO-8859-2",
			"ISO-8859-3",
			"ISO-8859-4",
			"ISO-8859-5"
		));
	}
	function test_decode_header() {
		$headers = array(
			array(
				'=?US-ASCII?Q?Keith_Moore?= <moore@cs.utk.edu>',
				'Keith Moore <moore@cs.utk.edu>',
				array(
					'US-ASCII'
				)
			),
			array(
				'=?ISO-8859-1?Q?Keld_J=F8rn_Simonsen?= <keld@dkuug.dk>',
				'Keld J' . UTF8::from_charset(chr(hexdec('F8')), 'ISO-8859-1') . 'rn Simonsen <keld@dkuug.dk>',
				array(
					'ISO-8859-1'
				)
			),
			array(
				'=?ISO-8859-1?Q?Andr=E9?= Pirard <PIRARD@vm1.ulg.ac.be>',
				'Andr' . chr(hexdec('C3')) . chr(hexdec('A9')) . ' Pirard <PIRARD@vm1.ulg.ac.be>',
				array(
					'ISO-8859-1'
				)
			),
			array(
				'=?ISO-8859-1?B?SWYgeW91IGNhbiByZWFkIHRoaXMgeW8=?=
 =?ISO-8859-2?B?dSB1bmRlcnN0YW5kIHRoZSBleGFtcGxlLg==?=',
				'If you can read this you understand the example.',
				array(
					'ISO-8859-1',
					'ISO-8859-2'
				)
			),
			array(
				'Nathaniel Borenstein <nsb@thumper.bellcore.com>
 (=?iso-8859-8?b?7eXs+SDv4SDp7Oj08A==?=)',
				'Nathaniel Borenstein <nsb@thumper.bellcore.com>
 (םולש ןב ילטפנ)',
				array(
					'ISO-8859-8'
				)
			),
			array(
				'(=?ISO-8859-1?Q?a?=
       =?ISO-8859-1?Q?b?=)',
				'(ab)',
				array(
					'ISO-8859-1',
					'ISO-8859-1'
				)
			),
			array(
				'?ISO-8859-1?Q?a?=
       =?ISO-8859-1?Q?b?)',
				'?ISO-8859-1?Q?a?=
       =?ISO-8859-1?Q?b?)',
				array()
			),
			array(
				'(=?ISO-8859-1?Q?a_b?=)',
				'(a b)',
				array(
					"ISO-8859-1"
				)
			),
			array(
				'(=?ISO-8859-1?Q?a?= =?iso-8859-2?q?_b?=)',
				'(a b)',
				array(
					"ISO-8859-1",
					"ISO-8859-2"
				)
			),
			array(
				'(=?ISO-8859-1?Q?a?=













	=?iso-8859-2?q?_b?=)',
				'(a b)',
				array(
					"ISO-8859-1",
					"ISO-8859-2"
				)
			)
		);
		
		foreach ($headers as $header) {
			list($test, $expect, $expected_charsets) = $header;
			$result = Mail::decode_header($test);
			$this->assert($result === $expect, "$result === $expect");
			echo "$test => $result (" . implode(",", $expected_charsets) . "<br />\n";
			$charsets = Mail::header_charsets($test);
			$this->assert_arrays_equal($charsets, $expected_charsets);
		}
		$charset = null;
	}
	function test_debug() {
		$set = null;
		Mail::debug($set);
	}
	function test_encrypt() {
		$e = null;
		$hr = false;
		$noscript = true;
		Mail::encrypt($e, $hr, $noscript);
	}
	function test_is_encoded_header() {
		$headers = array(
			array(
				'=?US-ASCII?Q?Keith_Moore?= <moore@cs.utk.edu>',
				true
			),
			array(
				'=?ISO-8859-1?Q?Keld_J=F8rn_Simonsen?= <keld@dkuug.dk>',
				true
			),
			array(
				'=?ISO-8859-1?Q?Andr=E9?= Pirard <PIRARD@vm1.ulg.ac.be>',
				true
			),
			array(
				'=?ISO-8859-1?B?SWYgeW91IGNhbiByZWFkIHRoaXMgeW8=?=
 =?ISO-8859-2?B?dSB1bmRlcnN0YW5kIHRoZSBleGFtcGxlLg==?=',
				true
			),
			array(
				'Nathaniel Borenstein <nsb@thumper.bellcore.com>
 (=?iso-8859-8?b?7eXs+SDv4SDp7Oj08A==?=)',
				true
			),
			array(
				'(=?ISO-8859-1?Q?a?=
       =?ISO-8859-1?Q?b?=)',
				true
			),
			array(
				'?ISO-8859-1?Q?a?=
       =?ISO-8859-1?Q?b?)',
				false
			),
			array(
				'(=??Q?a_b?=)',
				false
			),
			array(
				'(=?a_b?Q??=)',
				false
			),
			array(
				'(=?bad-charset?Q?data?=)',
				true
			), // No charset validation, OK
			array(
				'(=?ISO-8859-1?X?a?=)',
				false
			),
			array(
				'(=?ISO-8859-1?Y?a?=)',
				false
			),
			array(
				'(=?ISO-8859-1?q?a?=)',
				true
			),
			array(
				'(=?ISO-8859-1?Q?a?=)',
				true
			),
			array(
				'(=?ISO-8859-1?B?a?=)',
				true
			),
			array(
				'(=?ISO-8859-1?b?a?=)',
				true
			)
		);
		
		foreach ($headers as $i => $header) {
			list($test, $expect) = $header;
			echo "Test $i: $test => " . ($expect ? 'true' : 'false') . "\n";
			$result = Mail::is_encoded_header($test);
			$this->assert($result === $expect);
			if ($result) {
				$this->assert(count(Mail::header_charsets($test)) !== 0);
			} else {
				$this->assert(count(Mail::header_charsets($test)) === 0);
			}
		}
	}
	function test_load_file() {
		$filename = $this->test_sandbox("mail.txt");
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
		
		$this->assert($result['File-Format'] === 'both');
		$this->assert($result['Subject'] === 'Test Email');
		$this->assert($result['From'] === 'support@zesk.com');
		$this->assert($result['To'] === 'support@example.com');
		$this->assert($result['body_text'] === 'This is the text email.

Thanks,

http://www.example.com/unsubscribe?email={email}', "Text failed: " . $result['body_text']);
		$this->assert($result['body_html'] === 'This is the <strong>HTML</strong> email.

Thanks,

<a href="http://www.example.com/unsubscribe?email={email}">Unsubscribe</a>', "HTML Failed: " . $result['body_html']);
	}
	function test_load_theme() {
		$application = $this->application;
		$template = null;
		$options = null;
		Mail::load_theme($application, $template, $options);
	}
	function test_mail_array() {
		Mail::debug(true);
		
		$to = "kent@marketacumen.com";
		$from = "no-reply@" . zesk\System::uname();
		$subject = null;
		$array = array(
			"Hello" => "Name",
			"Boo" => "FEDF"
		);
		$prefix = "";
		$suffix = "";
		Mail::mail_array($to, $from, $subject, $array, $prefix, $suffix);
	}
	function test_mailer() {
		Mail::debug(true);
		$headers = array(
			"From" => "no-reply@zesk.com",
			"To" => "noone@example.com",
			"Subject" => basename(__FILE__)
		);
		$body = "This is the body\n\n--\nnoone@example.com";
		Mail::mailer($headers, $body);
	}
	function test_map() {
		$to = null;
		$from = null;
		$subject = null;
		$filename = null;
		$fields = null;
		$cc = false;
		$bcc = false;
		Mail::map($to, $from, $subject, $filename, $fields, $cc, $bcc);
	}
	function test_parse_headers() {
		$content = null;
		Mail::parse_headers($content);
	}
	function test_mulitpart_send() {
		Mail::debug(true);
		
		$mail_options = array(
			"From" => "kent@zesk.com",
			"To" => "no-reply@zesk.com"
		);
		$attachments = null;
		Mail::mulitpart_send($mail_options, $attachments);
	}
	function test_send_sms() {
		Mail::debug(true);
		
		$to = "John@dude.com";
		$from = "kent@example.com";
		$subject = "You are the man!";
		$body = "All work and no play makes Kent a dull boy.
All work and no play makes Kent a dull boy.
All work and no play makes Kent a dull boy.
All work and no play makes Kent a dull boy.
All work and no play makes Kent a dull boy.
All work and no play makes Kent a dull boy.
All work and no play makes Kent a dull boy.
All work and no play makes Kent a dull boy.
All work and no play makes Kent a dull boy.
";
		$cc = false;
		$bcc = false;
		$headers = false;
		Mail::send_sms($to, $from, $subject, $body, $cc, $bcc, $headers);
	}
	function test_send_template() {
		$template = $this->test_sandbox("mail.tpl");
		
		Mail::debug(true);
		
		file_put_contents($template, "From: no-reply@zesk.com\nTo:noone@example.com\n<?php\necho \$this->thing;");
		$options = array(
			"thing" => "hello"
		);
		$attachments = null;
		$map = null;
		ob_start();
		Mail::send_template($template, $options, $attachments, $map);
		$contents = ob_get_clean();
		echo $contents;
		
		// TODO: Fix mailer
	}
	
	/**
	 * Taken from Net_Pop_Client_Test::_init
	 */
	private function _init() {
		$this->url = $this->option('email_url');
		
		if (empty($this->url)) {
			$this->fail(__CLASS__ . "::email_url not set");
		}
		$this->parts = URL::parse($this->url);
		if (empty($this->parts)) {
			$this->fail(__CLASS__ . "::email not a valid URL: $this->url");
		}
		$this->email = $this->option('email', avalue($this->parts, 'user'));
		if (empty($this->email)) {
			$this->fail(__CLASS__ . "::email set");
		}
		if ($this->parts['user'] !== $this->email) {
			$this->fail("User " . $this->parts['user'] . " !== $this->email\n");
		}
	}
	function test_sendmail() {
		$this->_init();
		Mail::debug(false);
		$pop_url = $this->url;
		
		$to = $this->email;
		$from = 'Don\'t bother replying <no-reply@zesk.com>';
		$subject = "Test email: AUTO_DELETE";
		$body = "Test body";
		$cc = false;
		$bcc = false;
		$headers[] = 'X-Bogus-Header: This is a bogus header';
		
		$this->log("Sending mail to $to");
		$this->assert_true(Mail::sendmail($to, $from, $subject, $body, $cc, $bcc, $headers));
		
		$test_mailbox = $to;
		$n_seconds = 1;
		$success = false;
		$timer = new Timer();
		do {
			$pop = new Net_POP_Client($pop_url, array(
				"echo_log" => false
			));
			$iterator = $pop->iterator();
			foreach ($iterator as $headers) {
				$remote_subject = avalue($headers, "subject");
				$this->application->logger->debug("checking subject: $remote_subject");
				if ($remote_subject === $subject) {
					$iterator->current_delete();
					$bogus_header = avalue($headers, "x-bogus-header");
					if ($bogus_header === "This is a bogus header") {
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
		
		$this->assert($success, "Unable to find message with subject $subject in destination folder");
	}
}
