<?php
/**
 * $URL: http://code.marketacumen.com/zesk/trunk/modules/mail/classes/mail/object.inc $
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 * @package zesk
 * @subpackage mail
 */
namespace zesk;

/**
 * Pattern for matching a list of email addresses
 */
define("PREG_EMAIL_LIST", '/([^",]*"[^"]*"[^",]*|[^",]*),/'); // Matches "foo" <bar@dee.com>, foo <bar@dee.com>, dee@bar.com

/**
 * Database object representing a single email message
 *
 * @author kent
 * @see Class_Mail_Message
 * @property integer $id
 * @property string $hash
 * @property string $message_id
 * @property string $mail_from
 * @property string $mail_to
 * @property string $subject
 * @property integer $state
 * @property \zesk\Timestamp $date
 * @property string $content_type
 * @property string $content
 * @property integer $size
 * @property object $user
 */
class Mail_Message extends Object {
	function applyHeaders($headers, &$contents) {
		$items = array(
			"From" => "mail_from",
			"To" => "mail_to",
			"Subject" => "subject"
		);
		
		foreach ($items as $item => $var) {
			$item = strtolower($item);
			if (isset($headers[$item])) {
				$this->set_member($var, $headers[$item]->value());
			}
		}
		$dd = avalue($headers, 'date');
		if ($dd instanceof Mail_Header) {
			$dd = $dd->value();
			// TODO: Parse Time Zone for correct UTC Time
			$this->date = Timestamp::factory($dd);
		}
		$size = 0;
		if (is_array($contents)) {
			$n_contents = count($contents);
			if ($n_contents === 1) {
				$contents = $contents[0];
			} else if ($n_contents > 1) {
				/* @var $content Content_Data */
				foreach ($contents as $content) {
					$size += $content->size;
				}
			}
		}
		if ($contents instanceof Mail_Content) {
			$size = $contents->size();
			$this->set_member("content_type", $contents->ContentType);
			$this->set_member("content", $contents->Content);
			$this->set_member("hash", $contents->hash());
			$contents = null;
		}
		if (is_string($contents)) {
			$size = strlen($contents);
			$this->set_member("content_type", "text/plain");
			$this->set_member("content", $contents);
			$this->set_member("hash", md5($contents));
		}
		$this->set_member("size", $size);
		$this->set_member("state", 0);
	}
	function header($type, $value = null, $append = false) {
		$htype = Object::factory('Mail_Header_Type', array(
			"code" => $type
		))->register();
		$hTypeID = $htype->id();
		if ($value === null) {
			$result = $this->member_query("headers")
				->where("headers.type", $htype)
				->what(array(
				"id" => "headers.id",
				"value" => "headers.value"
			))
				->to_array("id", "value");
			if (count($result) === 1) {
				return avalue(array_values($result), 0);
			}
			return $result;
		}
		if (!$append) {
			foreach ($this->member_iterator("headers", array(
				"type" => $htype
			)) as $header) {
				$header->value($value);
				return $this;
			}
		}
		Object::factory("Mail_Header", array(
			"type" => $htype,
			"value" => $value,
			"mail" => $this
		))->store();
		return $this;
	}
	function from($value = null) {
		if ($value === null) {
			return $this->mail_from;
		}
		$this->mail_from = $value;
		$this->header("From", $value, false);
		return $this;
	}
	function content_type($value = null) {
		return $this->header("Content-Type", $value);
	}
	function to($value = null) {
		if ($value === null) {
			return $this->mail_to;
		}
		$value = $this->cleanEmailList($value);
		$this->mail_to = $value;
		return $this->header("To", $value);
	}
	function bcc($value = null) {
		if ($value !== null) {
			$value = $this->cleanEmailList($value);
		}
		return $this->header("BCC", $value);
	}
	function subject($value = null) {
		if ($value !== null) {
			$this->subject = $value;
		}
		return $this->header("Subject", $value);
	}
	function body($value = null) {
		if ($value === null) {
			return $this->content;
		}
		$this->content = Text::set_line_breaks($value, "\r\n");
		return $this;
	}
	function html_body($value = null) {
		$this->content = Text::set_line_breaks($value, "\r\n");
		return $this->content_type("text/html");
	}
	function cc($value = null) {
		if ($value !== null) {
			$value = $this->cleanEmailList($value);
			$this->mail_to = $value;
		}
		return $this->header("CC", $value);
	}
	
	/*====================================================================================*\
	 * Sending
	 \*------------------------------------------------------------------------------------*/
	private static function _parseEmail($mixed) {
		$matches = false;
		if (preg_match('/([-A-Za-z_.\']+@[-a-z0-9A-Z.]+)/', $mixed, $matches)) {
			return trim($matches[1]);
		}
		return false;
	}
	private static function _parseEmailName($mixed, &$name) {
		$matches = false;
		if (preg_match('/([^<]+)<\s*([-A-Za-z0-9_.\']+@[-a-z0-9A-Z.]+)\s*>\s*/', $mixed, $matches)) {
			$name = trim(unquote(trim($matches[1])));
			return trim($matches[2]);
		}
		return false;
	}
	static function parseEmail($mixed, &$name) {
		$name = false;
		$result = self::_parseEmailName($mixed, $name);
		if ($result !== false) {
			return $result;
		}
		$result = self::_parseEmail($mixed);
		if ($result !== false) {
			$name = "";
			return $result;
		}
		return false;
	}
	private static function _cleanEmail($mixed, $justEmail = false) {
		$emails = array();
		$errors = array();
		if (is_array($mixed)) {
			foreach ($mixed as $email) {
				$result = self::_cleanEmail($email, $justEmail);
				$emails = array_merge($emails, $result);
			}
		} else if (is_string($mixed)) {
			$matches = false;
			if (preg_match_all(PREG_EMAIL_LIST, "$mixed,", $matches, PREG_SET_ORDER)) {
				foreach ($matches as $i => $match) {
					$emailMatch = $match[1];
					if (empty($emailMatch))
						continue;
					$name = false;
					$email = self::_parseEmailName($emailMatch, $name);
					if ($email) {
						if ($justEmail) {
							$emails[strtolower($email)] = $email;
						} else {
							$emails[strtolower($email)] = "\"$name\" <$email>";
						}
					} else {
						$email = self::_parseEmail($emailMatch);
						if ($email) {
							if ($justEmail) {
								$emails[strtolower($email)] = $email;
							} else {
								$emails[strtolower($email)] = "\"$email\" <$email>";
							}
						} else {
							$emails[] = new Exception_Mail_Format($emailMatch);
						}
					}
				}
			} else {
				$emails[] = new Exception_Mail_Format($mixed);
			}
		} else {
			throw new Exception_Mail_Format($mixed);
		}
		return $emails;
	}
	static function cleanEmailList($mixed, $justEmail = false) {
		$emails = self::_cleanEmail($mixed, $justEmail);
		return implode(", ", $emails);
	}
	function _mailTo() {
		$to = $this->headerGet("To", array());
		return $this->cleanEmailList($to, true);
	}
	function _mailExtras() {
		$extras = array();
		
		/* @var $head Mail_Header */
		foreach ($this->headers as $head) {
			$name = $head->name();
			if (!in_array(strtolower($name), array(
				"to",
				"subject"
			))) {
				$value = $head->value;
				$extras[$name] = $value;
			}
		}
		$extras["X-Mailer"] = "zesk/" . Version::release();
		
		if (!$this->member_is_empty("mail_from")) {
			$extras["From"] = $this->mail_from;
		}
		if ($this->member_is_empty("message_id")) {
			$this->message_id = md5(microtime());
		}
		$extras["Message-ID"] = $this->message_id;
		
		$result = array();
		foreach ($extras as $k => $v) {
			$result[] = "$k:$v";
		}
		return $result;
	}
	function send() {
		$to = $this->_mailTo();
		$subject = $this->headerGet("Subject");
		$content = $this->content;
		$extras = $this->_mailExtras();
		
		$mailBR = \is_windows() ? "\r\n" : "\n";
		
		$extras = implode($mailBR, $extras) . $mailBR;
		if (ZESK_DEBUG_MAIL) {
			$ff = fopen("/tmp/Mail.log", "a");
			fwrite($ff, "To: $to\nSubject: $subject\n" . Debug::dump($extras) . "\n\n" . Text::set_line_breaks($content, $mailBR) . "\n-- \n\n");
			fclose($ff);
			$result = true;
		} else {
			$result = @mail($to, trim($subject), Text::set_line_breaks($content, $mailBR) . $mailBR, $extras);
		}
		if ($result === false) {
			throw new Exception_Mail_Send("$to, \"$subject\"");
		}
		$this->set_member("date", Timestamp::now());
		return $this->store();
	}
	static function from_file(Application $application, $fd) {
		return Mail_Parser::from_file($application, $fd);
	}
	static function import_file(Application $application, $filename = "php://stdin") {
		$fd = fopen($filename, "r");
		if (strpos($filename, "mail.31589.txt") !== false) {
			$test = true;
		}
		$m = self::from_file($application, $fd);
		return $m;
	}
}

