<?php
declare(strict_types=1);

/**
 * @package zesk
 * @subpackage email
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Mail;

use zesk\Application;
use zesk\ArrayTools;
use zesk\Exception_Connect;
use zesk\Exception_Convert;
use zesk\Exception_Deprecated;
use zesk\Exception_File_Format;
use zesk\Exception_File_NotFound;
use zesk\Exception_File_Permission;
use zesk\Exception_Key;
use zesk\Exception_Parameter;
use zesk\Exception_Redirect;
use zesk\Exception_Syntax;
use zesk\File;
use zesk\Hookable;
use zesk\Hooks;
use zesk\MIME;
use zesk\Net\SMTP\Client;
use zesk\Text;
use zesk\UTF8;
use zesk\Version;
use function is_windows;

/**
 *
 * @author kent
 *
 */
class Mail extends Hookable {
	/**
	 * Set to enable debugging behavior
	 */
	public const OPTION_DEBUG = 'debug';

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
	 */
	private static string $log = '';

	/**
	 *
	 */
	private static mixed $fp = null;

	/**
	 *
	 */
	private static bool $disabled = false;

	/**
	 * Create a Mail object
	 *
	 * @param Application $application
	 * @param array $headers
	 * @param string $body
	 * @param array $options
	 */
	public function __construct(Application $application, array $headers, string $body, array $options = []) {
		parent::__construct($application, $options);
		$this->inheritConfiguration();
		$this->headers = $headers;
		$this->body = $body;
		$this->sent = 0;
	}

	/**
	 * Create a Mail object
	 *
	 * @param Application $application
	 * @param array $headers
	 * @param string $body
	 * @param array $options
	 * @return Mail
	 */
	public static function factory(Application $application, array $headers, string $body, array $options = []): self {
		return new self($application, $headers, $body, $options);
	}

	/**
	 * Get a header
	 *
	 * @param string $name
	 * @return array|string
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
		self::$log = $application->paths->expand($config->getPath([__CLASS__, 'log', ]));
		self::$fp = null;
		self::$disabled = toBool($config->getPath([__CLASS__, 'disabled', ]));
	}

	/**
	 * Send a Mail object
	 *
	 * @return self
	 * @throws Exception_Connect|Exception_Syntax
	 */
	public function send(): self {
		$this->_log($this->headers, $this->body);

		if (!$this->callHookArguments('send', [], true)) {
			$this->method = 'send-hook-false';
			return $this;
		}
		if ($this->sent !== 0) {
			return $this;
		}
		if ($this->debug()) {
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
		return $this->_sendPHPMail();
	}

	/**
	 * Internal function to send by echo
	 *
	 * @return self
	 */
	private function _send_echo(): self {
		print(self::toDebugHTML());
		$this->sent = time();
		$this->method = 'echo';
		return $this;
	}

	/**
	 * Returns a debugging Mail for HTML
	 *
	 * @return string
	 */
	public function toDebugHTML(): string {
		$eol = self::mailEOL();
		$lines = [];
		$lines[] = '<pre class="mail-debug">';
		$lines[] = htmlspecialchars(self::renderHeaders($this->headers));
		$lines[] = $eol . $eol;
		$lines[] = $this->body;
		$lines[] = '</pre>';
		return implode('', $lines);
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
		$smtp = new Client($this->application, $url, $this->optionArray('SMTP_OPTIONS'));
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
	private function _sendPHPMail(): self {
		$to = $this->headers['To'] ?? null;
		$from = $this->headers['From'] ?? null;
		$headers = $this->headers;
		$subject = $this->headers['Subject'] ?? '';
		unset($headers['To']);
		unset($headers['Subject']);
		$body = str_replace("\r", '', $this->body);
		$ini_was_set = false;
		if ($from) {
			try {
				$address = self::parseAddress($from);
				$from_email = $address['email'];
				$options = "-t -f$from_email";
				ini_set('sendmail_from', $from);
				$ini_was_set = true;
			} catch (Exception_Syntax) {
				$options = null;
			}
		} else {
			$options = null;
		}
		$result = mail($to, $subject, $body, self::renderHeaders($headers), $options);
		if ($ini_was_set) {
			ini_restore('sendmail_from');
		}
		$this->method = 'mail';
		if ($result) {
			$this->sent = time();
		}
		return $this;
	}

	/**
	 * @return string
	 */
	private static function mailEOL(): string {
		return is_windows() ? "\r\n" : "\n";
	}

	/**
	 * Get mail debugging status
	 *
	 * @return bool
	 */
	public function debug(): bool {
		return $this->optionBool('debug');
	}

	/**
	 * Set mail debugging
	 *
	 * @param bool $set
	 * @return self
	 */
	public function setDebug(bool $set): self {
		return $this->setOption('debug', $set);
	}

	/**
	 * @param string $line
	 * @return string
	 */
	private static function trimMailLine(string $line): string {
		return trim(str_replace(["\r", "\n", ], ['', '', ], $line));
	}

	/**
	 * Parse an email address in various form
	 *
	 * @param string $email
	 * @return array
	 * @throws Exception_Syntax
	 */
	public static function parseAddress(string $email): array {
		$matches = [];
		$result = [];
		$atom = '[- A-Za-z0-9!#$%&\'*+\/=?^_`{|}~]';
		$aText = "$atom+";
		$domain = '[-A-Za-z0-9.]+';
		$white = '\s+';
		$patterns = [
			'/(' . $aText . '|"[^\"]")' . $white . '<(' . $aText . ')@(' . $domain . ')>/' => [1, 2, 3],
			'/<(' . $aText . ')@(' . $domain . ')>/' => [null, 1, 2],
			'/(' . $aText . ')@(' . $domain . ')/' => [null, 1, 2],
		];
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

		throw new Exception_Syntax('Invalid {method} email: {email}', ['email' => $email, 'method' => __METHOD__]);
	}

	/**
	 * Identical to sendmail, but truncates the entire message to be 140 characters
	 * Determined length based on iPhone/AT&T.
	 *
	 * @param string $to Email address to send to
	 * @param string $from  Email address from (maybe "Hello" <email@example.com> etc.)
	 * @param string $subject Optional. Subject of message.
	 * @param string $body Message to send.
	 * @param string $cc Optional. CC email addresses.
	 * @param string $bcc Optional. BCC email addresses.
	 * @param array $headers Optional extra headers in the form: array("Header-Type: Header Value", "...")
	 * @return Mail unsent email
	 * @throws Exception_Syntax
	 */
	public static function sms(
		Application $application,
		string $to,
		string $from,
		string $subject,
		string $body,
		string $cc = '',
		string $bcc = '',
		array $headers = []
	): self {
		$email_parts = self::parseAddress($from);
		$from_part = $email_parts['name'] ?? $email_parts['email'] ?? '';
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

		$remain = toInteger($application->configuration->getPath([__CLASS__, 'sms_max_characters', ]), 140) - $len;

		return self::sendmail($application, $to, $from, $subject, substr($body, 0, $remain), $cc, $bcc, $headers);
	}

	/**
	 * Send email to someone.
	 *
	 * @param Application $application
	 * @param string $to Email address to send to
	 * @param string $from Email address from (e.g. "Hello" <email@example.com> etc.)
	 * @param string $subject Optional. Subject of message.
	 * @param string $body Message to send.
	 * @param string $cc Optional. CC email addresses.
	 * @param string $bcc Optional. BCC email addresses.
	 * @param array $headers Optional extra headers in the form: array("Header-Type: Header Value", "...")
	 * @param array $options
	 * @return self
	 * @throws Exception_Syntax
	 */
	public static function sendmail(Application $application, string $to, string $from, string $subject, string $body, string $cc = '', string $bcc = '', array $headers = [], array $options = []): self {
		$new_headers = [];
		if (!empty($from)) {
			$from = self::trimMailLine($from);
			if (!is_simple_email($from)) {
				throw new Exception_Syntax('Not an email From: {from}', ['from' => $from]);
			}
			$new_headers['From'] = $from;
		}
		if (is_string($cc) && $cc) {
			if (!is_email($cc)) {
				throw new Exception_Syntax('Not an email CC: {cc}', ['cc' => $cc]);
			}
			$new_headers['Cc'] = $cc;
		}
		if (is_string($bcc) && $bcc) {
			if (!is_email($bcc)) {
				throw new Exception_Syntax('Not an email BCC: {bcc}', ['bcc' => $bcc]);
			}
			$new_headers['Bcc'] = $bcc;
		}
		$toParts = self::parseAddress($to);
		$new_headers['To'] = $toParts['text'];
		$new_headers['Subject'] = self::trimMailLine($subject);
		$new_headers['Date'] = gmdate('D, d M Y H:i:s \G\M\T', time());

		foreach ($headers as $header) {
			[$name, $value] = pair($header, ':');
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
				self::$log = '';
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
		return new Mail($application, $headers, $body, $options);
	}

	/**
	 * @param Application $application
	 * @param string $to
	 * @param string $from
	 * @param string $subject
	 * @param array $array
	 * @param string $prefix
	 * @param string $suffix
	 * @return static
	 * @throws Exception_Syntax
	 * @throws Exception_Syntax
	 * @throws Exception_Deprecated
	 */
	public static function mail_array(Application $application, string $to, string $from, string $subject, array $array, string $prefix = '', string $suffix = ''): self {
		$application->deprecated(__METHOD__);
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
	 * @return Mail
	 * @throws Exception_Syntax
	 */
	public static function mailArray(Application $application, string $to, string $from, string $subject, array $array, string $prefix = '', string $suffix = ''): self {
		$content = Text::format_pairs($array);
		return self::sendmail($application, $to, $from, $subject, $prefix . $content . $suffix);
	}

	/**
	 * @param Application $application
	 * @param string $to
	 * @param string $from
	 * @param string $subject
	 * @param string $filename
	 * @param array $fields
	 * @param string $cc
	 * @param string $bcc
	 * @return static
	 * @throws Exception_File_NotFound
	 * @throws Exception_Syntax
	 */
	public static function map(Application $application, string $to, string $from, string $subject, string $filename, array $fields, string $cc = '', string $bcc = ''): self {
		if (!file_exists($filename)) {
			throw new Exception_File_NotFound($filename);
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
	 * @param Application $application
	 * @param array $mail_options
	 * @param array $attachments
	 * @return self
	 * @throws Exception_Parameter
	 * @throws Exception_File_NotFound
	 * @throws Exception_File_Permission|Exception_Key
	 */
	public static function multipartFactory(Application $application, array $mail_options, array $attachments = []): self {
		$eol = self::mailEOL();
		$mime_boundary = md5(microtime());

		$charset = $mail_options['charset'] ?? 'UTF-8';
		unset($mail_options['charset']);

		// Common Headers
		$headers = ArrayTools::filter($mail_options, [
			'From', 'To', 'Reply-To', 'Return-Path', 'Cc', 'Bcc', 'Return-Receipt-To', 'Subject',
		]);
		if (!array_key_exists('From', $headers)) {
			throw new Exception_Parameter('Need to have a From header: {keys} {debug}', [
				'keys' => array_keys($headers), 'debug' => _dump($mail_options),
			]);
		}
		if (!array_key_exists('To', $headers)) {
			throw new Exception_Parameter('Need to have a \"To\" header: {keys} <pre>{debug}</pre>', [
				'keys' => array_keys($headers), 'debug' => _dump($mail_options),
			]);
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
			if (str_starts_with($k, 'X-')) {
				$headers[$k] = $v;
			}
		}
		$mailHost = $mail_options['System-ID'] ?? $_SERVER['SERVER_NAME'] ?? php_uname('n');
		$headers[self::HEADER_MESSAGE_ID] = '<' . $mime_boundary . ' mailer@' . $mailHost . '>';
		$headers['X-Mailer'] = 'zesk v' . Version::release() . '/PHP v' . phpversion();
		$headers['MIME-Version'] = '1.0';
		$headers[self::HEADER_CONTENT_TYPE] = 'multipart/related; boundary="' . $mime_boundary . '"';

		$m = '';

		// Setup for text OR html -
		$m .= '--' . $mime_boundary . $eol;
		// A different MIME boundary for this section for the alternative
		$htmlMIMEBoundary = md5($mime_boundary . '_alt_html');
		$m .= 'Content-Type: multipart/alternative; boundary="' . $htmlMIMEBoundary . '"' . $eol . $eol;

		if (array_key_exists('body_text', $mail_options)) {
			// Text Version
			$m .= '--' . $htmlMIMEBoundary . $eol;
			$m .= "Content-Type: text/plain; charset=$charset" . $eol;
			$m .= 'Content-Transfer-Encoding: quoted-printable' . $eol . $eol;
			$m .= quoted_printable_encode($mail_options['body_text']) . $eol . $eol;
		}

		if (array_key_exists('body_html', $mail_options)) {
			// HTML Version
			$m .= '--' . $htmlMIMEBoundary . $eol;
			$m .= "Content-Type: text/html; charset=$charset" . $eol;
			$m .= 'Content-Transfer-Encoding: quoted-printable' . $eol . $eol;
			$m .= quoted_printable_encode($mail_options['body_html']) . $eol . $eol;
		}

		//close the html/plain text alternate portion
		$m .= '--' . $htmlMIMEBoundary . '--' . $eol . $eol;


		// Attachments
		foreach ($attachments as $attachment) {
			if (is_string($attachment)) {
				$attachment = ['file' => $attachment];
			}
			/* Keys: file, name, type */
			if (!array_key_exists('file', $attachment) && !array_key_exists('content', $attachment)) {
				throw new Exception_Parameter('Attachment must have key "file" or "attachment" which is path to file to send');
			}
			if (array_key_exists('file', $attachment)) {
				$file = $attachment['file'];
				$file_name = $attachment['name'] ?? basename($file);
				$f_contents = File::contents($file);
				$content_type = $attachment['type'] ?? null;
				if (!$content_type) {
					$content_type = MIME::fromExtension($file_name);
				}
			} else {
				if (!array_key_exists('name', $attachment)) {
					throw new Exception_Parameter('Attachment must have key "name" and "content" (or just "file")');
				}
				$file_name = $attachment['name'];
				$f_contents = strval($attachment['content']);
				$content_type = $attachment['type'] ?? 'application/octet-stream';
			}

			// Attachment
			$m .= '--' . $mime_boundary . $eol;
			$m .= "Content-Type: $content_type; name=\"" . $file_name . '"' . $eol;
			$m .= 'Content-Transfer-Encoding: base64' . $eol;
			$m .= 'Content-Disposition: attachment; filename="' . $file_name . '"' . $eol . $eol; // !! This line needs TWO end of lines !! IMPORTANT !!
			$f_contents = chunk_split(base64_encode($f_contents)); //Encode The Data For Transition using base64_encode();
			$m .= $f_contents . $eol . $eol;
		}


		// Finished
		$m .= '--' . $mime_boundary . '--' . $eol . $eol;

		return self::mailer($application, $headers, $m);
	}

	/**
	 * @param string $filename
	 * @return array
	 * @throws Exception_File_NotFound
	 * @throws Exception_File_Permission
	 */
	public static function loadFile(string $filename): array {
		return self::load(File::contents($filename));
	}

	/**
	 * Render an email using a theme
	 *
	 * @param Application $application
	 * @param string|array $theme
	 * @param array $variables
	 * @return array
	 * @throws Exception_Deprecated
	 * @throws Exception_Redirect
	 */
	public static function load_theme(Application $application, string|array $theme, array $variables = []): array {
		zesk()->deprecated(__METHOD__);
		return self::loadTheme($application, $theme, $variables);
	}

	/**
	 * Render an email using a theme
	 *
	 * @param Application $application
	 * @param string|array $theme
	 * @param array $variables
	 * @return array
	 * @throws Exception_Redirect
	 */
	public static function loadTheme(Application $application, string|array $theme, array $variables = []): array {
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
				[$header_type, $header_value] = pair($line, ':', $line);
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
	 * @param string $header
	 * @return bool
	 */
	public static function isEncodedHeader(string $header): bool {
		// e.g. =?utf-8?q?Re=3a=20ConversionRuler=20Support=3a=204D09EE9A=20=2d=20Re=3a=20ConversionRuler=20Support=3a=204D078032=20=2d=20Wordpress=20Plugin?=
		// e.g. =?utf-8?q?Wordpress=20Plugin?=
		return preg_match(self::RFC2047HEADER, $header) !== 0;
	}

	/**
	 *
	 * @param string $header
	 * @return array
	 */
	public static function headerCharsets(string $header): array {
		$matches = null;
		if (!preg_match_all(self::RFC2047HEADER, $header, $matches, PREG_PATTERN_ORDER)) {
			return [];
		}
		return array_map(strtoupper(...), $matches[1]);
	}

	/**
	 * Given a header with RFC2047 encoding of binary/UTF-8 data, convert it into UTF8 string
	 *
	 * @param string $header
	 * @return string
	 * @throws Exception_Syntax - decoding failed
	 */
	public static function decodeHeader(string $header): string {
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
			$data = match ($encoding) {
				'B' => base64_decode($data),
				'Q' => quoted_printable_decode(str_replace('_', ' ', $data)),
				default => throw new Exception_Syntax("preg_match_all is busted: didn't find B or Q in encoding $header"),
			};

			try {
				$data = UTF8::from_charset($data, $charset);
			} catch (Exception_Convert|Exception_File_Format $e) {
				throw new Exception_Syntax('Unable to convert from charset {charset}', ['charset' => $charset], 0, $e);
			}
			$header = str_replace($match, $data, $header);
		}
		return $header;
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
	public static function parseHeaders(string $content, array $options = []): array {
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
				[$n, $v] = pair($line, ':', $line);
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
	public function dump(): string {
		return Text::format_pairs($this->headers) . "\n\n" . $this->body;
	}
}
