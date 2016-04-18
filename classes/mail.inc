<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/mail.inc $
 * @package zesk
 * @subpackage email
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2014, Market Acumen, Inc.
 */

/**
 * mail tools
 *
 * @author kent
 */
class Mail extends Hookable {
	const header_content_type = 'Content-Type';
	const header_message_id = "Message-ID";
	const header_to = "To";
	const header_from = "From";
	const header_subject = "Subject";
	
	/**
	 *
	 * @var array
	 */
	public $headers = array();
	
	/**
	 *
	 * @var string
	 */
	public $body = "";
	
	/**
	 *
	 * @var integer
	 */
	public $sent = null;
	/**
	 *
	 * @var string
	 */
	public $method = null;
	private static $debug = false;
	private static $log = null;
	private static $fp = null;
	private static $disabled = null;
	
	/**
	 * Create a Mail object
	 * 
	 * @param array $headers
	 * @param string $body
	 * @param array $options
	 */
	public function __construct(array $headers, $body, array $options = array()) {
		parent::__construct($options);
		$this->headers = $headers;
		$this->body = $body;
		$this->sent = null;
	}
	
	/**
	 * Create a Mail object
	 *
	 * @param array $headers
	 * @param string $body
	 * @param array $options
	 * @return Mail
	 */
	public static function factory(array $headers, $body, array $options = array()) {
		return new Mail($headers, $body, $options);
	}
	
	/**
	 * Get/Set a header
	 *
	 * @param string $name
	 * @param string $set
	 *        	Value to set
	 *
	 * @return Mail mixed
	 */
	public function header($name, $set = null) {
		if ($set === null) {
			return avalue($this->headers, $name, null);
		}
		$this->headers[$name] = $set;
		return $this;
	}
	
	/**
	 * Sign email with DKIM
	 *
	 * @todo Move to DKIM module
	 */
	private function _dkim_sign() {
		$smtp_send = zesk::get('SMTP_URL');
		if (zesk::get('DKIM::public_key') && $smtp_send) {
			if (DKIM::enabled()) {
				$dkim = new DKIM(zesk::get('DKIM::domain'), zesk::get('DKIM::public_key'), zesk::get('DKIM::private_key'), zesk::get('DKIM::selector'));
				$this->headers = $dkim->sign(self::render_headers($this->headers), $this->body);
			}
		}
	}
	
	/**
	 * Send a Mail object
	 *
	 * @return boolean|Mail
	 */
	public function send() {
		$smtp_send = zesk::get('SMTP_URL');
		
		self::_log($this->headers, $this->body);
		
		if (!$this->hook_array("send", array(), true)) {
			return false;
		}
		if ($this->sent !== null) {
			return true;
		}
		if (self::$debug) {
			return $this->_send_echo();
		}
		if (self::$disabled) {
			$this->sent = time();
			$this->method = "disabled";
			return false;
		}
		if ($smtp_send) {
			return $this->_send_smtp();
		}
		return $this->_send_mail();
	}
	
	/**
	 * Internal function to send by echo
	 *
	 * @return boolean
	 */
	private function _send_echo() {
		$eol = self::mail_eol();
		echo "<pre class=\"mail-debug\">" . htmlspecialchars(self::render_headers($this->headers) . $eol . $eol . $this->body) . "</pre>";
		$this->sent = time();
		$this->method = "echo";
		return true;
	}
	
	/**
	 * Send using SMTP_URL and SMTP_OPTIONS which is, oddly, formatted as HTML attributes optionally
	 *
	 * @return Mail|null
	 */
	private function _send_smtp() {
		$smtp_send = zesk::get('SMTP_URL');
		$to = avalue($this->headers, 'To', null);
		$from = avalue($this->headers, 'From', null);
		$body = str_replace("\r\n", "\n", $this->body);
		$body = str_replace("\n", "\r\n", $body);
		$smtp = new Net_SMTP_Client($smtp_send, html::parse_attributes(zesk::get('SMTP_OPTIONS')));
		$this->method = "smtp";
		if ($smtp->send($from, $to, self::render_headers($this->headers), $body)) {
			$this->sent = time();
			return $this;
		}
		return null;
	}
	
	/**
	 * @return Mail
	 */
	private function _send_mail() {
		$to = avalue($this->headers, 'To', null);
		$from = avalue($this->headers, 'From', null);
		$headers = $this->headers;
		$subject = avalue($this->headers, 'Subject', '');
		unset($headers['To']);
		unset($headers['Subject']);
		$body = str_replace("\r", "", $this->body);
		if ($from) {
			$from_email = self::parse_address($from, 'email');
			$mailopts = "-t -f$from_email";
			ini_set('sendmail_from', $from);
		} else {
			$mailopts = null;
		}
		$result = mail($to, $subject, $body, self::render_headers($headers), $mailopts);
		if ($from) {
			ini_restore('sendmail_from');
		}
		$this->method = "mail";
		if ($result) {
			$this->sent = time();
		}
		return $this;
	}
	private static function mail_eol() {
		return zesk::$is_windows ? "\r\n" : "\n";
	}
	private static function caesar_encrypt($x, $y) {
		$p = "";
		for ($i = 0; $i < strlen($x); $i++) {
			$p = $p . chr((ord($x[$i]) - 32) % 240 + $y + 32);
		}
		return urlencode($p);
	}
	public static function encrypt($e, $hr = false, $noscript = true) {
		if (empty($e)) {
			return "";
		}
		$offset = mt_rand(1, 10);
		$doff = 32 + $offset;
		if (empty($hr)) {
			$hr = $e;
		}
		$link = "<a href=\"mailto:" . $e . "\">$hr</a>";
		$j = "";
		$scr = "";
		$scr = $scr . "var e = unescape(\"" . self::caesar_encrypt($link, $offset) . "\");\n";
		$scr = $scr . "var i,p='';for(i=0;i<e.length;i++){p+=String.fromCharCode(((e.charCodeAt(i)-" . $doff . ")%240)+32);}\n";
		$code = zesk::get('encrypt-email-id', 0);
		$scr .= "\$('#enc_email_$code').html(p);";
		Response::instance()->jquery("(function () { " . $scr . " })();");
		$j = $j . "<span id=\"enc_email_$code\"></span>";
		zesk::set('encrypt-email-id', $code + 1);
		
		$e = explode("@", $e);
		if ($noscript) {
			$j = $j . "<noscript>" . $e[0] . " <em>-a<!-- " . mt_rand() . " -->t-</em> ";
			$f = explode(".", $e[1]);
			$j = $j . implode(" <em>d<!-- " . mt_rand() . " -->ot</em> ", $f);
			$j = $j . "</noscript>";
		}
		
		return $j;
	}
	public static function hooks() {
		zesk::add_hook('Application::configured', __CLASS__ . '::configured');
	}
	public static function configured() {
		self::$debug = zesk::getb('mail::debug');
		self::$log = zesk::get('mail::log');
		self::$fp = null;
		self::$disabled = zesk::get('mail::disabled');
	}
	public static function debug($set = null) {
		if ($set !== null) {
			self::$debug = $set;
		}
		return self::$debug;
	}
	private static function trim_mail_line($line) {
		return trim(str_replace(array(
			"\r",
			"\n"
		), array(
			'',
			''
		), $line));
	}
	public static function parse_address($email, $part = null) {
		$matches = array();
		$result = array();
		$atom = '[- A-Za-z0-9!#$%&\'*+\/=?^_`{|}~]';
		$atext = "$atom+";
		$domain = '[-A-Za-z0-9.]+';
		$white = '\s+';
		if (preg_match('/(' . $atext . '|"[^\"]")' . $white . '<(' . $atext . ')@(' . $domain . ')>/', $email, $matches)) {
			$result['length'] = strlen($matches[0]);
			$result['text'] = $matches[0];
			$result['name'] = unquote($matches[1]);
			$result['email'] = $matches[2] . '@' . strtolower($matches[3]);
			$result['user'] = $matches[2];
			$result['host'] = strtolower($matches[3]);
		} else if (preg_match('/<(' . $atext . ')@(' . $domain . ')>/', $email, $matches)) {
			$result['length'] = strlen($matches[0]);
			$result['text'] = $matches[0];
			$result['name'] = '';
			$result['email'] = $matches[1] . '@' . strtolower($matches[2]);
			$result['user'] = $matches[1];
			$result['host'] = strtolower($matches[2]);
		} else if (preg_match('/(' . $atext . ')@(' . $domain . ')/', $email, $matches)) {
			$result['length'] = strlen($matches[0]);
			$result['text'] = $matches[0];
			$result['name'] = '';
			$result['email'] = $matches[1] . '@' . strtolower($matches[2]);
			$result['user'] = $matches[1];
			$result['host'] = strtolower($matches[2]);
		} else {
			return false;
		}
		if ($part) {
			return avalue($result, $part, "Invalid Part: $part");
		}
		return $result;
	}
	
	/**
	 * Identical to sendmail, but truncates the entire message to be 140 characters
	 * Determined length based on iPhone/AT&T.
	 *
	 * @todo Test with alternate providers.
	 * @param string $to
	 *        	Email address to send to
	 * @param string $from
	 *        	Email address from (may be "Hello" <email@example.com> etc.)
	 * @param string $subject
	 *        	Optional. Subject of message.
	 * @param string $body
	 *        	Message to send.
	 * @param string $cc
	 *        	Optional. CC email addresses.
	 * @param string $bcc
	 *        	Optional. BCC email addresses.
	 * @param array $headers
	 *        	Optional extra headers in the form: array("Header-Type: Header Value", "...")
	 * @return boolean True if email sent, False if not.
	 */
	public static function send_sms($to, $from, $subject, $body, $cc = false, $bcc = false, $headers = false) {
		$email_parts = self::parse_address($from);
		$from_part = avalue($email_parts, "name", avalue($email_parts, "email", ""));
		
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
		$len += strlen("MSG:");
		
		$remain = zesk::geti('sms_sendmail::max_characters', 140) - $len;
		
		return self::sendmail($to, $from, $subject, substr($body, 0, $remain), $cc, $bcc, $headers);
	}
	
	/**
	 * Send an email to someone.
	 *
	 * @param string $to
	 *        	Email address to send to
	 * @param string $from
	 *        	Email address from (may be "Hello" <email@example.com> etc.)
	 * @param string $subject
	 *        	Optional. Subject of message.
	 * @param string $body
	 *        	Message to send.
	 * @param string $cc
	 *        	Optional. CC email addresses.
	 * @param string $bcc
	 *        	Optional. BCC email addresses.
	 * @param array $headers
	 *        	Optional extra headers in the form: array("Header-Type: Header Value", "...")
	 * @return boolean True if email sent, False if not.
	 */
	public static function sendmail($to, $from, $subject, $body, $cc = false, $bcc = false, $headers = false, array $options = array()) {
		$new_headers = array();
		if (!is_array($headers)) {
			$headers = array();
		}
		if (!empty($from)) {
			$from = self::trim_mail_line($from);
			$new_headers['From'] = rtrim($from);
		}
		if (is_email($cc)) {
			$new_headers['Cc'] = ltrim($cc);
		}
		if (is_email($bcc)) {
			$new_headers['Bcc'] = ltrim($bcc);
		}
		
		$new_headers['To'] = $to;
		$new_headers['Subject'] = self::trim_mail_line($subject);
		$new_headers['Date'] = gmdate('D, d M Y H:i:s \G\M\T', time());
		
		//	$headers[] = "Content-Type: text/plain";
		

		foreach ($headers as $header) {
			list($name, $value) = pair($header, ":", null, null);
			if ($name) {
				$new_headers[$name] = ltrim($value);
			}
		}
		return self::mailer($new_headers, $body, $options);
	}
	private static function _log($headers, $body) {
		if (!self::$log) {
			return;
		}
		if (!self::$fp) {
			self::$fp = fopen(self::$log, "a");
			if (!self::$fp) {
				log::error("Unable to open mail log {log} - mail logging disabled", array(
					"log" => self::$log
				));
				self::$log = null;
				return;
			}
		}
		fwrite(self::$fp, text::format_pairs($headers) . "\n" . $body . "\n\n");
	}
	private static function render_headers(array $headers) {
		$mail_eol = "\r\n";
		$raw_headers = "";
		foreach ($headers as $name => $value) {
			$raw_headers .= $name . ": " . rtrim($value) . $mail_eol;
		}
		return $raw_headers;
	}
	public static function mailer(array $headers, $body, array $options = array()) {
		$mail = new Mail($headers, $body, $options);
		return $mail->send();
	}
	public static function mail_array($to, $from, $subject, $array, $prefix = "", $suffix = "") {
		$content = text::format_pairs($array);
		return self::sendmail($to, $from, $subject, $prefix . $content . $suffix);
	}
	public static function map($to, $from, $subject, $filename, $fields, $cc = false, $bcc = false) {
		if (!file_exists($filename)) {
			return false;
		}
		$from = preg_replace("/[\r\n]/", "", $from);
		$fields['to'] = $to;
		$fields['from'] = $from;
		$fields['subject'] = $subject;
		$fields['cc'] = $cc;
		$fields['when'] = date("Y-m-d H-i-s");
		$fields['*'] = text::format_array($fields);
		$contents = map(file_get_contents($filename), $fields);
		$subject = trim(map($subject, $fields));
		$contents = str_replace("\r\n", "\n", $contents);
		$contents = str_replace("\r", "", $contents);
		return self::sendmail($to, $from, $subject, $contents, $cc, $bcc);
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
	 *        	Options for the mail, required: From, To
	 * @return unknown
	 */
	public static function mulitpart_send($mail_options, $attachments = null) {
		$eol = mail::mail_eol();
		$mime_boundary = md5(microtime());
		
		$charset = avalue($mail_options, 'charset', 'UTF-8');
		unset($mail_options['charset']);
		
		# Common Headers
		$headers = arr::filter($mail_options, "From;To;Reply-To;Return-Path;Cc;Bcc;Return-Receipt-To;Subject");
		if (!array_key_exists("From", $headers)) {
			throw new Zesk_Exception("Need to have a From header: {keys} {debug}", array(
				"keys" => array_keys($headers),
				"debug" => _dump($mail_options)
			));
		}
		if (!array_key_exists("To", $headers)) {
			throw new Zesk_Exception("Need to have a To header: {keys} <pre>{debug}</pre>", array(
				"keys" => array_keys($headers),
				"debug" => _dump($mail_options)
					));
		}
		// KMD: 2015-11-05 Removed
		//	 "Return-Receipt-To"
		// From below as it should be handled enough by Return-Path for bounces
		foreach (array(
			"Reply-To",
			"Return-Path"
		) as $k) {
			if (!array_key_exists($k, $headers)) {
				$headers[$k] = $headers['From'];
			}
		}
		foreach ($mail_options as $k => $v) {
			if (begins($k, "X-")) {
				$headers[$k] = $v;
			}
		}
		
		$headers[self::header_message_id] = "<" . $mime_boundary . " mailer@" . avalue($mail_options, "System-ID", avalue($_SERVER, 'SERVER_NAME', '')) . ">";
		$headers['X-Mailer'] = "zesk v" . zesk::version() . "/PHP v" . phpversion();
		$headers['MIME-Version'] = "1.0";
		$headers[self::header_content_type] = "multipart/related; boundary=\"" . $mime_boundary . "\"";
		
		$m = "";
		
		// Setup for text OR html -
		$m .= "--" . $mime_boundary . $eol;
		// A different MIME boundary for this section for the alternative
		$htmlalt_mime_boundary = md5($mime_boundary . "_htmlalt");
		$m .= "Content-Type: multipart/alternative; boundary=\"" . $htmlalt_mime_boundary . "\"" . $eol . $eol;
		
		if (array_key_exists('body_text', $mail_options)) {
			// Text Version
			$m .= "--" . $htmlalt_mime_boundary . $eol;
			$m .= "Content-Type: text/plain; charset=$charset" . $eol;
			$m .= "Content-Transfer-Encoding: quoted-printable" . $eol . $eol;
			$m .= quoted_printable_encode($mail_options['body_text']) . $eol . $eol;
		}
		
		if (array_key_exists('body_html', $mail_options)) {
			// HTML Version
			$m .= "--" . $htmlalt_mime_boundary . $eol;
			$m .= "Content-Type: text/html; charset=$charset" . $eol;
			$m .= "Content-Transfer-Encoding: quoted-printable" . $eol . $eol;
			$m .= quoted_printable_encode($mail_options['body_html']) . $eol . $eol;
		}
		
		//close the html/plain text alternate portion
		$m .= "--" . $htmlalt_mime_boundary . "--" . $eol . $eol;
		
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
						$content_type = mime::from_filename($file_name);
					}
					
					// Attachment
					$m .= "--" . $mime_boundary . $eol;
					$m .= "Content-Type: $content_type; name=\"" . $file_name . "\"" . $eol;
					$m .= "Content-Transfer-Encoding: base64" . $eol;
					$m .= "Content-Disposition: attachment; filename=\"" . $file_name . "\"" . $eol . $eol; // !! This line needs TWO end of lines !! IMPORTANT !!
					$m .= $f_contents . $eol . $eol;
				}
			}
		}
		
		// Finished
		$m .= "--" . $mime_boundary . "--" . $eol . $eol;
		
		// SEND THE EMAIL
		$result = self::mailer($headers, $m);
		
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
	 * @todo Probably should split $theme_variables and $map_variables, eh? 2016-03-10
	 * 	 
	 * @param Application $application
	 * @param unknown $theme
	 * @param unknown $variables
	 */
	public static function load_theme(Application $application, $theme, $variables = null) {
		$variables = to_array($variables);
		$variables['application'] = $application;
		return self::load(map($application->theme($theme, $variables), $variables));
	}
	
	/**
	 * 
	 * @see Mail::load_theme
	 * @deprecated 2016-01 
	 * @param unknown $template
	 * @param unknown $variables
	 */
	public static function load_template($template, $variables = null) {
		$variables = to_array($variables);
		return self::load(map(Template::instance($template, $variables), $variables));
	}
	public static function load($contents) {
		$lines = explode("\n", $contents);
		$result = array();
		while (($line = array_shift($lines)) !== false) {
			$line = trim($line);
			if (empty($line)) {
				$content_type = strtolower(avalue($result, 'File-Format', ''));
				$content = implode("\n", $lines);
				switch ($content_type) {
					case "html":
						$result['body_html'] = $content;
						break;
					case "text":
						$result['body_text'] = $content;
						break;
					case "both":
						$ff_sep = avalue($result, 'File-Format-Separator', '--HTML--');
						list($text, $html) = explode($ff_sep, $content, 2);
						$result['body_text'] = chop($text);
						$result['body_html'] = trim($html);
						break;
					default :
						$result['body_text'] = $content;
						break;
				}
				break;
			} else {
				$header_type = $header_value = null;
				list($header_type, $header_value) = pair($line, ":", $line, null);
				$result[$header_type] = ltrim($header_value);
			}
		}
		return $result;
	}
	public static function send_template($template, $variables, $attachments = null) {
		$mail = self::load_template($template, $variables);
		return self::mulitpart_send($mail, $attachments);
	}
	
	/**
	 * If you change one of these, please check the other for fixes as well
	 *
	 * @const Pattern to match RFC 2047 charset encodings in mail headers
	 */
	const rfc2047header = '/=\?([^ ?]+)\?([BQbq])\?([^ ?]+)\?=/';
	const rfc2047header_spaces = '/(=\?[^ ?]+\?[BQbq]\?[^ ?]+\?=)\s+(=\?[^ ?]+\?[BQbq]\?[^ ?]+\?=)/';
	
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
		return preg_match(self::rfc2047header, $header) !== 0;
	}
	public static function header_charsets($header) {
		$matches = null;
		if (!preg_match_all(self::rfc2047header, $header, $matches, PREG_PATTERN_ORDER)) {
			return array();
		}
		return array_map('strtoupper', $matches[1]);
	}
	public static function decode_header($header) {
		$matches = null;
		
		/* Repair instances where two encodings are together and separated by a space (strip the spaces) */
		$header = preg_replace(self::rfc2047header_spaces, "$1$2", $header);
		
		/* Now see if any encodings exist and match them */
		if (!preg_match_all(self::rfc2047header, $header, $matches, PREG_SET_ORDER)) {
			return $header;
		}
		foreach ($matches as $header_match) {
			list($match, $charset, $encoding, $data) = $header_match;
			$encoding = strtoupper($encoding);
			switch ($encoding) {
				case 'B':
					$data = base64_decode($data);
					break;
				case 'Q':
					$data = quoted_printable_decode(str_replace("_", " ", $data));
					break;
				default :
					throw new Exception_Semantics("preg_match_all is busted: didn't find B or Q in encoding $header");
			}
			$data = utf8::from_charset($data, $charset);
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
	public static function skip_headers($content, array $options = array()) {
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
	 *        	raw email message
	 * @param array $options
	 *        	Optional options for parsing
	 * @return array
	 */
	public static function parse_headers($content, array $options = array()) {
		$newline = avalue($options, 'newline', "\r\n");
		$whitespace = avalue($options, 'whitespace', " \t");
		$line_trim = avalue($options, 'line_trim', false);
		$lines = explode($newline, $content);
		$headers = array();
		$curh = null;
		$curv = "";
		foreach ($lines as $line) {
			if (($line_trim && rtrim($line) === "") || $line === "") {
				break;
			}
			if ($curh !== null) {
				if (strpos($whitespace, substr($line, 0, 1)) !== false) {
					$curv .= $newline . trim($line);
				} else {
					arr::append($headers, $curh, $curv);
					$curh = $curv = null;
				}
			}
			if ($curh === null) {
				list($n, $v) = pair($line, ":", $line, null);
				$curh = $n;
				$curv = trim($v);
			}
		}
		if ($curh !== null) {
			arr::append($headers, $curh, $curv);
		}
		return $headers;
	}
	public function dump() {
		return text::format_pairs($this->headers) . "\n\n" . $this->body;
	}
}

