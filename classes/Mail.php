<?php declare(strict_types=1);

/**
 * @package zesk
 * @subpackage email
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Mail extends Hookable {
	/**
	 * If you change one of these, please check the other for fixes as well
	 *
	 * @const Pattern to match RFC 2047 charset encodings in mail headers
	 */
	public const RFC2047HEADER = '/=\?([^ ?]+)\?([BQbq])\?([^ ?]+)\?=/';

	/**
	 *
	 * @const Pattern to match RFC 2047 charset encodings in mail headers with whitespace
	 */
	public const RFC2047HEADER_SPACES = '/(=\?[^ ?]+\?[BQbq]\?[^ ?]+\?=)\s+(=\?[^ ?]+\?[BQbq]\?[^ ?]+\?=)/';

	/**
	 *
	 * @var string
	 */
	public const HEADER_CONTENT_TYPE = 'Content-Type';

	/**
	 *
	 * @var string
	 */
	public const HEADER_MESSAGE_ID = 'Message-ID';

	/**
	 *
	 * @var string
	 */
	public const HEADER_TO = 'To';

	/**
	 *
	 * @var string
	 */
	public const HEADER_FROM = 'From';

	/**
	 *
	 * @var string
	 */
	public const HEADER_SUBJECT = 'Subject';

	/**
	 *
	 */
	public array $headers = [];

	/**
	 *
	 */
	public string $body = '';

	/**
	 *
	 */
	public int $sent = 0;

	/**
	 *
	 */
	public string $method = '';

	/**
	 *
	 *
	 */
	private static bool $debug = false;

	/**
	 *
	 */
	private static string $log = '';

	/**
	 *
	 */
	private static ?resource $fp = null;

	/**
	 *
	 */
	private static bool $disabled = false;

	/**
	 * Create a Mail object
	 *
	 * @param array $headers
	 * @param string $body
	 * @param array $options
	 */
	public function __construct(Application $application, array $headers, $body, array $options = []) {
		parent::__construct($application, $options);
		$this->inheritConfiguration();
		$this->headers = $headers;
		$this->body = $body;
		$this->sent = 0;
	}

	/**
	 * Create a Mail object
	 *
	 * @param array $headers
	 * @param string $body
	 * @param array $options
	 * @return Mail
	 */
	public static function factory(Application $application, array $headers, $body, array $options = []) {
		return new self($application, $headers, $body, $options);
	}

	/**
	 * Get/Set a header
	 *
	 * @param string $name
	 * @param string $set
	 *            Value to set
	 *
	 * @return self
	 */
	public function header(string $name): array|string {
		return $this->headers[$name] ?? '';
	}

	/**
	 * @param string $name
	 * @param array|string $set
	 * @return $this
	 */
	public function setHeader(string $name, array|string $set): self {
		$this->headers[$name] = $set;
		return $this;
	}

	/**
	 *
	 * @param Application $application
	 */
	public static function hooks(Application $application): void {
		$application->hooks->add(Hooks::HOOK_CONFIGURED, __CLASS__ . '::configured');
	}

	/**
	 *
	 * @param Application $application
	 */
	public static function configured(Application $application): void {
		$config = $application->configuration;

		/*
		 * Load globals
		 */
		self::$debug = toBool($config->path_get([__CLASS__, 'debug', ]));
		self::$log = $application->paths->expand($config->path_get([__CLASS__, 'log', ]));
		self::$fp = null;
		self::$disabled = toBool($config->path_get([__CLASS__, 'disabled', ]));
	}

	/**
	 * Send a Mail object
	 *
	 * @return self
	 */
	public function send(): self {
		$this->_log($this->headers, $this->body);

		if (!$this->call_hook_arguments('send', [], true)) {
			$this->method = 'send-hook-false';
			return $this;
		}
		if ($this->sent !== 0) {
			return $this;
		}
		if (self::$debug) {
			return $this->_send_echo();
		}
		if (self::$disabled) {
			$this->sent = time();
			$this->method = 'disabled';
			return $this;
		}
		$smtp_send = $this->option('SMTP_URL');
		if ($smtp_send) {
			return $this->_send_smtp();
		}
		return $this->_send_mail();
	}

	/**
	 * Internal function to send by echo
	 *
	 * @return self
	 */
	private function _send_echo(): self {
		$eol = self::mailEOL();
		$lines = [];
		$lines[] = '<pre class="mail-debug">';
		$lines[] = htmlspecialchars(self::renderHeaders($this->headers));
		$lines[] = $eol . $eol;
		$lines[] = $this->body;
		$lines[] = '</pre>';
		print(implode('', $lines));

		$this->sent = time();
		$this->method = 'echo';
		return $this;
	}

	/**
	 * Send using SMTP_URL and SMTP_OPTIONS which is, oddly, formatted as HTML attributes optionally
	 *
	 * @return Mail
	 * @throws Exception_Connect|Exception_Syntax
	 */
	private function _send_smtp(): self {
		$url = $this->option('SMTP_URL');
		$to = $this->headers['To'] ?? null;
		$from = $this->headers['From'] ?? null;
		$body = str_replace("\r\n", "\n", $this->body);
		$body = str_replace("\n", "\r\n", $body);
		$smtp = new Net_SMTP_Client($this->application, $url, $this->optionArray('SMTP_OPTIONS'));
		$this->method = 'smtp';
		if ($smtp->send($from, $to, self::renderHeaders($this->headers), $body)) {
			$this->sent = time();
			return $this;
		}

		throw new Exception_Connect('SMTP failed');
	}

	/**
	 *
	 * @return self
	 */
	private function _send_mail(): self {
		$to = $this->headers['To'] ?? null;
		$from = $this->headers ['From'] ?? null;
		$headers = $this->headers;
		$subject = $this->headers['Subject'] ?? '';
		unset($headers['To']);
		unset($headers['Subject']);
		$body = str_replace("\r", '', $this->body);
		if ($from) {
			$address = self::parse_address($from);
			$from_email = $address['email'];
			$options = "-t -f$from_email";
			ini_set('sendmail_from', $from);
		} else {
			$options = null;
		}
		$result = mail($to, $subject, $body, self::renderHeaders($headers), $options);
		if ($from) {
			ini_restore('sendmail_from');
		}
		$this->method = 'mail';
		if ($result) {
			$this->sent = time();
		}
		return $this;
	}

	private static function mailEOL(): string {
		return \is_windows() ? "\r\n" : "\n";
	}

	/**
	 * Get mail debugging status
	 *
	 * @return bool
	 */
	public static function debug(): bool {
		return self::$debug;
	}

	/**
	 * Set mail debugging
	 *
	 * @param bool $set
	 * @return void
	 */
	public static function setDebug(bool $set): void {
		self::$debug = $set;
	}

	private static function trim_mail_line(string $line): string {
		return trim(str_replace(["\r", "\n", ], ['', '', ], $line));
	}

	/**
	 * @param string $email
	 * @return array
	 */
	public static function parse_address(string $email): array {
		return self::parseAddress($email);
	}

	/**
	 * Parse an email address in various form
	 *
	 * @param string $email
	 * @return array
	 */
	public static function parseAddress(string $email): array {
		$matches = [];
		$result = [];
		$atom = '[- A-Za-z0-9!#$%&\'*+\/=?^_`{|}~]';
		$atext = "$atom+";
		$domain = '[-A-Za-z0-9.]+';
		$white = '\s+';
		$patterns = ['/(' . $atext . '|"[^\"]")' . $white . '<(' . $atext . ')@(' . $domain . ')>/' => [1, 2, 3], '/<(' . $atext . ')@(' . $domain . ')>/' => [null, 1, 2], '/(' . $atext . ')@(' . $domain . ')/' => [null, 1, 2], ];
		foreach ($patterns as $pattern => $mappings) {
			if (preg_match($pattern, $email, $matches)) {
				[$name_index, $user_index, $domain_index] = $mappings;
				$result['length'] = strlen($matches[0]);
				$result['text'] = $matches[0];
				$result['name'] = $name_index ? unquote($matches[$name_index]) : '';
				$result['user'] = $matches[$user_index];
				$result['host'] = strtolower($matches[$domain_index]);
				$result['email'] = $result['user'] . '@' . $result['host'];
				return $result;
			}
		}
		return [];
	}

	/**
	 * Identical to sendmail, but truncates the entire message to be 140 characters
	 * Determined length based on iPhone/AT&T.
	 *
	 * @param string $to
	 *            Email address to send to
	 * @param string $from
	 *            Email address from (may be "Hello" <email@example.com> etc.)
	 * @param string $subject
	 *            Optional. Subject of message.
	 * @param string $body
	 *            Message to send.
	 * @param string $cc
	 *            Optional. CC email addresses.
	 * @param string $bcc
	 *            Optional. BCC email addresses.
	 * @param array $headers
	 *            Optional extra headers in the form: array("Header-Type: Header Value", "...")
	 * @return boolean True if email sent, False if not.
	 * @todo Test with alternate providers.
	 */
	public static function send_sms(Application $application, $to, $from, $subject, $body, $cc = false, $bcc = false, $headers = false) {
		$email_parts = self::parse_address($from);
		$from_part = avalue($email_parts, 'name', avalue($email_parts, 'email', ''));

		// FRM:name\n
		// SUBJ:$subject\n
		// MSG:...

		$len = 0;
		if ($from_part) {
			$len = strlen("FRM:$from_part\n");
		}
		if ($subject) {
			$len += strlen("SUBJ:$subject\n");
		}
		$len += strlen('MSG:');

		$remain = to_integer($application->configuration->path_get([__CLASS__, 'sms_max_characters', ]), 140) - $len;

		return self::sendmail($application, $to, $from, $subject, substr($body, 0, $remain), $cc, $bcc, $headers);
	}

	/**
	 * Send an email to someone.
	 *
	 * @param string $to
	 *            Email address to send to
	 * @param string $from
	 *            Email address from (may be "Hello" <email@example.com> etc.)
	 * @param string $subject
	 *            Optional. Subject of message.
	 * @param string $body
	 *            Message to send.
	 * @param string $cc
	 *            Optional. CC email addresses.
	 * @param string $bcc
	 *            Optional. BCC email addresses.
	 * @param array $headers
	 *            Optional extra headers in the form: array("Header-Type: Header Value", "...")
	 * @return self
	 */
	public static function sendmail(Application $application, string $to, string $from, string $subject, string $body, string $cc = null, string $bcc = null, array $headers = [], array $options = []) {
		$new_headers = [];
		if (!is_array($headers)) {
			$headers = [];
		}
		if (!empty($from)) {
			$from = self::trim_mail_line($from);
			$new_headers['From'] = rtrim($from);
		}
		if (is_string($cc) && is_email($cc)) {
			$new_headers['Cc'] = ltrim($cc);
		}
		if (is_string($bcc) && is_email($bcc)) {
			$new_headers['Bcc'] = ltrim($bcc);
		}

		$new_headers['To'] = $to;
		$new_headers['Subject'] = self::trim_mail_line($subject);
		$new_headers['Date'] = gmdate('D, d M Y H:i:s \G\M\T', time());

		//	$headers[] = "Content-Type: text/plain";

		foreach ($headers as $header) {
			[$name, $value] = pair($header, ':', '', '');
			if ($name) {
				$new_headers[$name] = ltrim($value);
			}
		}
		return self::mailer($application, $new_headers, $body, $options);
	}

	private function _log($headers, $body): void {
		if (!self::$log) {
			return;
		}
		if (!self::$fp) {
			self::$fp = fopen(self::$log, 'ab');
			if (!self::$fp) {
				$this->application->logger->error('Unable to open mail log {log} - mail logging disabled', ['log' => self::$log, ]);
				self::$log = null;
				return;
			}
		}
		fwrite(self::$fp, Text::format_pairs($headers) . "\n" . $body . "\n\n");
	}

	/**
	 * @param array $headers
	 * @return string
	 */
	private static function renderHeaders(array $headers): string {
		$mail_eol = "\r\n";
		$raw_headers = '';
		foreach ($headers as $name => $value) {
			$raw_headers .= $name . ': ' . rtrim($value) . $mail_eol;
		}
		return $raw_headers;
	}

	/**
	 * @param Application $application
	 * @param array $headers
	 * @param string $body
	 * @param array $options
	 * @return Mail
	 */
	public static function mailer(Application $application, array $headers, string $body, array $options = []): self {
		$mail = new Mail($application, $headers, $body, $options);
		return $mail->send();
	}

	public static function mail_array(Application $application, string $to, string $from, string $subject, array $array, string $prefix = '', string $suffix = '') {
		return self::mailArray($application, $to, $from, $subject, $array, $prefix, $suffix);
	}

	/**
	 * @param Application $application
	 * @param string $to
	 * @param string $from
	 * @param string $subject
	 * @param array $array
	 * @param string $prefix
	 * @param string $suffix
	 * @return bool
	 */
	public static function mailArray(Application $application, string $to, string $from, string $subject, array $array, string $prefix = '', string $suffix = '') {
		$content = Text::format_pairs($array);
		return self::sendmail($application, $to, $from, $subject, $prefix . $content . $suffix);
	}

	public static function map(Application $application, string $to, string $from, string $subject, string $filename, array $fields, string $cc = null, string $bcc = null): self {
		if (!file_exists($filename)) {
			return false;
		}
		$from = preg_replace("/[\r\n]/", '', $from);
		$fields['to'] = $to;
		$fields['from'] = $from;
		$fields['subject'] = $subject;
		$fields['cc'] = $cc;
		$fields['when'] = date('Y-m-d H-i-s');
		$fields['*'] = Text::format_array($fields);
		$contents = map(file_get_contents($filename), $fields);
		$subject = trim(map($subject, $fields));
		$contents = str_replace("\r\n", "\n", $contents);
		$contents = str_replace("\r", '', $contents);
		return self::sendmail($application, $to, $from, $subject, $contents, $cc, $bcc);
	}

	/*
	 * How to detect a bounce email
	 *
	 * 1. make sure the email you send out have the header
	 * "Return-Path: detect-bounce@yourdomain.com\r\n",
	 * &
	 * "Return-Receipt-To: bounce@yourdomain.com\r\n"
	 *
	 * 2. setup this detect-bounce mail account at your mail server
	 *
	 * 3. http::redirect the incoming mail from this email account to your php script (check your mail server doc on how do this)
	 *
	 * 4. your php script will then be able to process the incoming email in whatever way you like, including to detect bounce mail message (use regexp search).
	 *
	 * Note that the mail will be not be store after the mail server has http::redirect to your script.  If you want to store it, you need additional code in your script
	 */

	/**
	 * Send a text or HTML email, with optional attachments
	 *
	 * @param array $mail_options
	 *            Options for the mail, required: From, To
	 * @param array $attachments
	 *            Array of arrays containing keys
	 *            - "file" The file name to attach (required)
	 *            - "name" The name to use in the email for the attachment (uses basename otherwise)
	 *            - "content_type" - The content type to use for this attachment (uses MIME
	 *            detection otherwise)
	 * @return Mail
	 */
	public static function multipart_send(Application $application, array $mail_options, $attachments = null) {
		$eol = self::mailEOL();
		$mime_boundary = md5(microtime());

		$charset = avalue($mail_options, 'charset', 'UTF-8');
		unset($mail_options['charset']);

		// Common Headers
		$headers = ArrayTools::filter($mail_options, 'From;To;Reply-To;Return-Path;Cc;Bcc;Return-Receipt-To;Subject');
		if (!array_key_exists('From', $headers)) {
			throw new Exception_Semantics('Need to have a From header: {keys} {debug}', ['keys' => array_keys($headers), 'debug' => _dump($mail_options), ]);
		}
		if (!array_key_exists('To', $headers)) {
			throw new Exception_Semantics('Need to have a To header: {keys} <pre>{debug}</pre>', ['keys' => array_keys($headers), 'debug' => _dump($mail_options), ]);
		}
		// KMD: 2015-11-05 Removed
		//	 "Return-Receipt-To"
		// From below as it should be handled enough by Return-Path for bounces
		foreach (['Reply-To', 'Return-Path', ] as $k) {
			if (!array_key_exists($k, $headers)) {
				$headers[$k] = $headers['From'];
			}
		}
		foreach ($mail_options as $k => $v) {
			if (begins($k, 'X-')) {
				$headers[$k] = $v;
			}
		}

		$headers[self::HEADER_MESSAGE_ID] = '<' . $mime_boundary . ' mailer@' . avalue($mail_options, 'System-ID', avalue($_SERVER, 'SERVER_NAME', '')) . '>';
		$headers['X-Mailer'] = 'zesk v' . Version::release() . '/PHP v' . phpversion();
		$headers['MIME-Version'] = '1.0';
		$headers[self::HEADER_CONTENT_TYPE] = 'multipart/related; boundary="' . $mime_boundary . '"';

		$m = '';

		// Setup for text OR html -
		$m .= '--' . $mime_boundary . $eol;
		// A different MIME boundary for this section for the alternative
		$htmlalt_mime_boundary = md5($mime_boundary . '_htmlalt');
		$m .= 'Content-Type: multipart/alternative; boundary="' . $htmlalt_mime_boundary . '"' . $eol . $eol;

		if (array_key_exists('body_text', $mail_options)) {
			// Text Version
			$m .= '--' . $htmlalt_mime_boundary . $eol;
			$m .= "Content-Type: text/plain; charset=$charset" . $eol;
			$m .= 'Content-Transfer-Encoding: quoted-printable' . $eol . $eol;
			$m .= quoted_printable_encode($mail_options['body_text']) . $eol . $eol;
		}

		if (array_key_exists('body_html', $mail_options)) {
			// HTML Version
			$m .= '--' . $htmlalt_mime_boundary . $eol;
			$m .= "Content-Type: text/html; charset=$charset" . $eol;
			$m .= 'Content-Transfer-Encoding: quoted-printable' . $eol . $eol;
			$m .= quoted_printable_encode($mail_options['body_html']) . $eol . $eol;
		}

		//close the html/plain text alternate portion
		$m .= '--' . $htmlalt_mime_boundary . '--' . $eol . $eol;

		if (is_array($attachments)) {
			// Attachments
			foreach ($attachments as $attachment) {
				$file = $attachment['file'];
				if (is_file($file)) {
					$file_name = avalue($attachment, 'name', basename($file));
					$f_contents = file_get_contents($file);
					$f_contents = chunk_split(base64_encode($f_contents)); //Encode The Data For Transition using base64_encode();
					$content_type = avalue($attachment, 'content_type');
					if (!$content_type) {
						$content_type = MIME::from_filename($file_name);
					}

					// Attachment
					$m .= '--' . $mime_boundary . $eol;
					$m .= "Content-Type: $content_type; name=\"" . $file_name . '"' . $eol;
					$m .= 'Content-Transfer-Encoding: base64' . $eol;
					$m .= 'Content-Disposition: attachment; filename="' . $file_name . '"' . $eol . $eol; // !! This line needs TWO end of lines !! IMPORTANT !!
					$m .= $f_contents . $eol . $eol;
				}
			}
		}

		// Finished
		$m .= '--' . $mime_boundary . '--' . $eol . $eol;

		// SEND THE EMAIL
		$result = self::mailer($application, $headers, $m);

		return $result;
	}

	public static function load_file($filename) {
		$contents = file_get_contents($filename);
		if (empty($contents)) {
			return false;
		}
		return self::load($contents);
	}

	/**
	 * Render an email using a theme
	 *
	 * @param Application $application
	 * @param string|array $theme
	 * @param array $variables
	 * @throws Exception_Semantics
	 */
	public static function load_theme(Application $application, string|array $theme, array $variables = []) {
		return self::loadTheme($application, $theme, $variables);
	}

	/**
	 * Render an email using a theme
	 *
	 * @param Application $application
	 * @param string|array $theme
	 * @param array $variables
	 * @return array
	 * @throws Exception_Semantics
	 */
	public static function loadTheme(Application $application, string|array $theme, array $variables = []) {
		$variables = toArray($variables);
		$variables['application'] = $application;
		return self::load(map($application->theme($theme, $variables), $variables));
	}

	/**
	 * Load and parse mail from a string
	 *
	 * @param string $contents
	 * @return array
	 */
	public static function load(string $contents): array {
		$result = [];
		$lines = explode("\n", $contents);
		while (($line = array_shift($lines)) !== false) {
			$line = trim($line);
			if (empty($line)) {
				$content_type = strtolower($result['File-Format'] ?? '');
				$content = implode("\n", $lines);
				switch ($content_type) {
					case 'html':
						$result['body_html'] = $content;

						break;
					case 'both':
						$ff_sep = $result['File-Format-Separator'] ?? '--HTML--';
						[$text, $html] = explode($ff_sep, $content, 2);
						$result['body_text'] = rtrim($text);
						$result['body_html'] = trim($html);

						break;
					case 'text':
					default:
						$result['body_text'] = $content;

						break;
				}

				break;
			} else {
				[$header_type, $header_value] = pair($line, ':', $line, '');
				$result[$header_type] = ltrim($header_value);
			}
		}
		return $result;
	}

	/**
	 * http://www.rfc-archive.org/getrfc.php?rfc=2047
	 *
	 * =?<charset>?<encoding>?<data>?=
	 *
	 * @param string $subject
	 */
	public static function is_encoded_header($header) {
		// e.g. =?utf-8?q?Re=3a=20ConversionRuler=20Support=3a=204D09EE9A=20=2d=20Re=3a=20ConversionRuler=20Support=3a=204D078032=20=2d=20Wordpress=20Plugin?=
		// e.g. =?utf-8?q?Wordpress=20Plugin?=
		return preg_match(self::RFC2047HEADER, $header) !== 0;
	}

	/**
	 *
	 * @param string $header
	 * @return array
	 */
	public static function header_charsets($header) {
		$matches = null;
		if (!preg_match_all(self::RFC2047HEADER, $header, $matches, PREG_PATTERN_ORDER)) {
			return [];
		}
		return array_map('strtoupper', $matches[1]);
	}

	/**
	 * Given a header with RFC2047 encoding of binary/UTF-8 data, convert it into UTF8 string
	 *
	 * @param string $header
	 * @return string
	 * @throws Exception_Semantics - only if PHP preg_match_all somehow fails to extract an encoding of B or Q
	 */
	public static function decode_header($header) {
		$matches = null;

		/* Repair instances where two encodings are together and separated by a space (strip the spaces) */
		$header = preg_replace(self::RFC2047HEADER_SPACES, '$1$2', $header);

		/* Now see if any encodings exist and match them */
		if (!preg_match_all(self::RFC2047HEADER, $header, $matches, PREG_SET_ORDER)) {
			return $header;
		}
		foreach ($matches as $header_match) {
			[$match, $charset, $encoding, $data] = $header_match;
			$encoding = strtoupper($encoding);
			switch ($encoding) {
				case 'B':
					$data = base64_decode($data);

					break;
				case 'Q':
					$data = quoted_printable_decode(str_replace('_', ' ', $data));

					break;
				default:
					throw new Exception_Semantics("preg_match_all is busted: didn't find B or Q in encoding $header");
			}
			$data = UTF8::from_charset($data, $charset);
			$header = str_replace($match, $data, $header);
		}
		return $header;
	}

	/**
	 * Simple utility to skip mail headers
	 *
	 * @param string $content
	 * @param array $options
	 * @return Ambigous <mixed, array>
	 */
	public static function skip_headers($content, array $options = []) {
		$newline = avalue($options, 'newline', "\r\n");
		return avalue(explode($newline . $newline, $content, 2), 1, avalue($options, 'default', null));
	}

	/**
	 * Given an email, parse out the headers from the top.
	 * A blank line indicates end of headers.
	 *
	 * Specify:
	 *
	 * newline - alternate newline character (defaults to \r\n)
	 * whitespace - alternate first character "continue" whitespaces (defaults to space/tab)
	 * line_trim - trim each new line before checking against blank lines - this allows parsing of
	 * forwarded email which may have whitespaces inserted
	 *
	 * @param string $content
	 *            raw email message
	 * @param array $options
	 *            Optional options for parsing
	 * @return array
	 */
	public static function parse_headers(string $content, array $options = []): array {
		$newline = $options['newline'] ?? "\r\n";
		$whitespace = $options['whitespace'] ?? " \t";
		$line_trim = $options['line_trim'] ?? false;
		$lines = explode($newline, $content);
		$headers = [];
		$curHeader = null;
		$curValue = '';
		foreach ($lines as $line) {
			if (($line_trim && rtrim($line) === '') || $line === '') {
				break;
			}
			if ($curHeader !== null) {
				if (str_contains($whitespace, substr($line, 0, 1))) {
					$curValue .= $newline . trim($line);
				} else {
					ArrayTools::append($headers, $curHeader, $curValue);
					$curHeader = $curValue = null;
				}
			}
			if ($curHeader === null) {
				[$n, $v] = pair($line, ':', $line, '');
				$curHeader = $n;
				$curValue = trim($v);
			}
		}
		if ($curHeader !== null) {
			ArrayTools::append($headers, $curHeader, $curValue);
		}
		return $headers;
	}

	/**
	 * Dump a Mail object
	 */
	public function dump() {
		return Text::format_pairs($this->headers) . "\n\n" . $this->body;
	}
}
