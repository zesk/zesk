<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/mail/classes/mail/parser.inc $
 * @package zesk
 * @subpackage mail
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
namespace zesk;

/**
 * Mail_Parser parses incoming mail and stores it as Mail_Message objects.
 *
 * @package zesk
 * @subpackage mail
 */
class Mail_Parser {
	
	/**
	 *
	 * @var Application
	 */
	private $application = null;
	/**
	 *
	 * @var Mail_Message
	 */
	private $mail = null;
	
	/**
	 *
	 * @var array
	 */
	private $headers = array();
	
	/**
	 *
	 * @var array
	 */
	private $contents = array();
	
	/**
	 * Message ID parsed from the message
	 *
	 * @var string
	 */
	private $message_id = false;
	
	/**
	 * File of email to parse
	 *
	 * @var resource
	 */
	private $mail_file = false;
	
	/**
	 * Boundary stack (push/pop)
	 *
	 * @var array
	 */
	private $bound_stack = array();
	
	/**
	 * Depth of stack when we're at the "top"
	 *
	 * @var integer
	 */
	private $bound_top = 0;
	private $is_bound_end = false;
	private $line_index = 0;
	private $forwarded = array();
	private $state;
	function dump() {
		$r = "";
		foreach ($this->headers as $k => $v) {
			if ($v instanceof Mail_Header) {
				$r .= $v->dump();
			} else if (is_array($v)) {
				foreach ($v as $vi) {
					$r .= $vi->dump();
				}
			}
		}
		foreach ($this->contents as $k => $v) {
			$r .= "-------------------- CONTENTS --------------------\n";
			if (is_string($v)) {
				$r .= "$k : $v\n";
			} else if ($v instanceof Mail_Content) {
				$r .= $v->dump();
			} else {
				$r .= "$k: " . gettype($v) . "\n";
			}
		}
		return $r;
	}
	public function __construct(Application $application) {
		$this->application = $application;
	}
	public static function from_file(Application $application, $mail_fd, $boundary = false) {
		$parser = new self($application);
		return $parser->_from_file($mail_fd, $boundary);
	}
	
	/**
	 *
	 * @param unknown $mail_fd
	 * @param string $boundary
	 * @return boolean|\zesk\Mail_Message
	 */
	private function _from_file($mail_fd, $boundary = false) {
		$application = $this->application;
		if ($boundary) {
			$this->pushBoundary($boundary);
		}
		$m = new Mail_Message($application);
		if (!$this->parse($mail_fd)) {
			return false;
		}
		if ($boundary) {
			$was_boundary_end = $this->isBoundaryEnd();
			$this->popBoundary();
		}
		$fields = array(
			"message_id" => $this->message_id(), //
			"date" => Timestamp::now(),
			"hash" => ""
		);
		
		$m->set_member($fields);
		$m->register();
		
		$headers = $this->headers();
		/* @var $content_type Mail_Header */
		$content_type = avalue($headers, "content-type");
		$contents = $this->contents();
		
		if ($contents instanceof Mail_Content) {
			/* @var $contents Mail_Content */
			if ($content_type && str::begins($content_type->value, 'text/')) {
				$contents = $contents->contents();
				$attributes = $content_type->parse_attributes();
				if (array_key_exists("charset", $attributes)) {
					$contents = charset::to_utf8($contents, $attributes['charset']);
				}
				$m->content = $contents;
				$m->content_type = $content_type->value;
				$contents = null;
			} else {
				$contents = array(
					$contents
				);
			}
		}
		$m->applyHeaders($headers, $contents);
		
		$m->store();
		
		foreach ($headers as $h) {
			if (is_array($h)) {
				foreach ($h as $hdup) {
					$hdup->mail = $m;
					$hdup->register();
				}
			} else {
				$h->mail = $m;
				$h->register();
			}
		}
		if (is_array($contents)) {
			/* @var $content Mail_Content */
			foreach ($contents as $content) {
				$content->set_member(array(
					"mail" => $m
				));
				$content->register();
				$m->attachments = $content;
			}
		}
		$m->store();
		if ($boundary && $was_boundary_end) {
			$m->set_option("found_boundary_end", true);
		}
		return $m;
	}
	
	/*
	 Public functions
	 */
	private function parse($mailFile) {
		$this->bound_top = count($this->bound_stack);
		$this->mail_file = $mailFile;
		/*
		 Parse headers
		 */
		$this->headers = self::parseHeaders($this->mail_file);
		if (mail::debug()) {
			echo "==== HEADERS DONE ===\n";
		}
		if ($this->headers === false) {
			return false;
		}
		if (mail::debug()) {
			echo "==== HEADERS DONE ===\n";
		}
		/*
		 Parse contents
		 */
		$this->contents = self::parseContent($this->headers);
		if (mail::debug()) {
			echo "==== CONTENTS DONE ===\n";
		}
		if ($this->contents === false) {
			return false;
		}
		
		if (mail::debug()) {
			echo "====  CONTENTS DONE ===\n";
		}
		/*
		 Verify we've got it all here
		 
		 TODO: Add additional checks here? Or just dump it in the db
		 and deal later?
		 */
		;
		if ($this->_requiremessage_id() && ($this->hasHeader("From") || $this->hasHeader("Return-Path")))
			return true;
		return false;
	}
	private function hasHeader($name) {
		return self::_hasHeader($this->headers, $name);
	}
	private function getHeader($name, $index = 0, $default = false) {
		return self::_getHeader($this->headers, $name, $index, $default);
	}
	private function message_id() {
		return $this->message_id;
	}
	private function headers() {
		return $this->headers;
	}
	
	/**
	 *
	 * @return Mail_Content[]
	 */
	public function contents() {
		return $this->contents;
	}
	public function forwardedMails() {
		return $this->forwarded;
	}
	/*
	 Private functions
	 */
	private function readLine() {
		$this->line_index++;
		if (feof($this->mail_file)) {
			return false;
		}
		$line = fgets($this->mail_file, 10240);
		if (mail::debug()) {
			echo "#### $line";
		}
		return $line;
	}
	private static function parseContentLine($text, &$attribs) {
		$items = explode(";", $text);
		if (count($items) == 0)
			return false;
		$type = trim(array_shift($items));
		$attribs = array();
		foreach ($items as $item) {
			$div = strpos($item, "=");
			if ($div === false) {
				$key = strtolower(trim($item));
				$value = false;
			} else {
				$key = trim(substr($item, 0, $div));
				$value = unquote(trim(substr($item, $div + 1)));
			}
			$attribs[$key] = $value;
		}
		return $type;
	}
	private function _hasHeader($headers, $name) {
		$name = strtolower($name);
		return isset($headers[$name]);
	}
	private function _getHeader($headers, $name, $index = 0, $default = false) {
		$name = strtolower($name);
		if (!array_key_exists($name, $headers)) {
			return $default;
		}
		$h = $headers[$name];
		if (!is_array($h)) {
			return $h->value;
		}
		if (count($h) >= $index) {
			return $default;
		}
		return $h[$index]->value;
	}
	private function _requiremessage_id() {
		$mid = $this->getHeader("Message-ID");
		if ($mid) {
			$this->message_id = $mid;
			return true;
		}
		$attribs = array(
			"From",
			"Return-Path",
			"To",
			"Subject",
			"Date"
		);
		$checksum = array();
		
		foreach ($attribs as $attrib) {
			$h = $this->getHeader($attrib);
			if ($h) {
				$checksum[] = $h;
			}
		}
		if (count($checksum) == 0) {
			return false;
		}
		$checksum = implode(";", $checksum);
		$this->message_id = "zesk:" . md5($checksum);
		return true;
	}
	private function _addHeader(&$headers, $hTypeName, $hValue) {
		if (strpos($hTypeName, " ") !== false) {
			zesk()->logger->notice("Skipping header line: $hTypeName");
			return false;
		}
		$hType = $this->application->object_factory(__NAMESPACE__ . '\\' . 'Mail_Header_Type', array(
			"code" => strtolower($hTypeName),
			'name' => $hTypeName
		))->register();
		
		$lowType = strtolower($hTypeName);
		$hParams = array(
			"type" => $hType,
			"value" => $hValue
		);
		if (isset($headers[$lowType])) {
			if (!is_array($headers[$lowType])) {
				$temp = $headers[$lowType];
				$headers[$lowType] = array();
				
				$headers[$lowType][] = $temp;
			}
			$headers[$lowType][] = $this->application->object_factory(__NAMESPACE__ . "\\" . "Mail_Header", $hParams);
		} else {
			$headers[$lowType] = $this->application->object_factory(__NAMESPACE__ . "\\" . "Mail_Header", $hParams);
		}
		return true;
	}
	private function parseHeaders() {
		$curType = "";
		$curValue = "";
		$headers = array();
		
		while (($line = $this->readLine()) != false) {
			$line = rtrim($line);
			if ($line === "") {
				break;
			}
			$firstChar = substr($line, 0, 1);
			if (($firstChar == " ") || ($firstChar == "\t")) {
				$hpos = false;
			} else {
				$hpos = strpos($line, ":");
			}
			if ($hpos === false) {
				if ($curType !== "") {
					$curValue .= $line . "\n";
				} else {
					echo ("Mail_Parser::parse: Encountered line \"$line\" before type found\n");
				}
			} else {
				if ($curType !== "") {
					$this->_addHeader($headers, $curType, rtrim($curValue));
				}
				$curType = substr($line, 0, $hpos);
				$curValue = ltrim(substr($line, $hpos + 1));
			}
		}
		if ($curType !== "") {
			$this->_addHeader($headers, $curType, rtrim($curValue));
		}
		return $headers;
	}
	private function parseContent($headers) {
		$encoding = false;
		$disposition = false;
		$filename = false;
		$contentID = false;
		$type = false;
		$boundary = false;
		if (!self::parseContentHeaders($headers, $encoding, $disposition, $filename, $contentID, $type, $boundary)) {
			return false;
		}
		if (mail::debug()) {
			foreach ($headers as $header) {
				if (is_array($header)) {
					/* @var $h Mail_Header */
					foreach ($header as $h) {
						echo $h->dump();
					}
				} else {
					/* @var $header Mail_Header */
					echo $header->dump();
				}
			}
			dump(array_keys($headers));
			echo "### CONTENT HEADERS PARSED: encoding:$encoding disposition:$disposition filename:\"$filename\" Content-ID:$contentID Type:$type\n";
		}
		list($major) = pair($type, "/", "application", "unknown");
		if ($major === "multipart") {
			if ($boundary) {
				$this->pushBoundary($boundary);
				$result = $this->parseMultiPart();
				$this->popBoundary();
				return $result;
			}
			zesk()->logger->warning("Mail_Parser::parseContent: No boundary in Content-Type: $type");
			return false;
		}
		if (empty($filename)) {
			return self::createDataContent($type, $encoding);
		} else {
			return self::createFileContent($type, $encoding, $disposition, $filename, $contentID);
		}
	}
	private function pushBoundary($boundary) {
		if (mail::debug())
			echo ">>>>>>>>>>>>>>> PUSH $boundary\n";
		array_unshift($this->bound_stack, $boundary);
	}
	private function boundary() {
		return avalue($this->bound_stack, 0);
	}
	private function popBoundary() {
		if (mail::debug())
			echo "<<<<<<<<<<<<<<< POP " . $this->boundary() . "\n";
		array_shift($this->bound_stack);
		$this->is_bound_end = false;
	}
	private function isBoundaryEnd() {
		return $this->is_bound_end;
	}
	private function checkBoundary($line) {
		$this->is_bound_end = false;
		if (count($this->bound_stack) == 0) {
			return false;
		}
		$line = trim($line);
		$bound = $this->bound_stack[0];
		if ($line === "--" . $bound) {
			if (mail::debug())
				echo "### Found BEGIN $bound\n";
			return true;
		}
		if ($line === "--" . $bound . "--") {
			if (mail::debug())
				echo "### Found END $bound\n";
			$this->is_bound_end = true;
			return true;
		}
		return false;
	}
	private function readEncodedLine($encoding = false) {
		$line = $this->readLine();
		if ($line === false) {
			return false;
		}
		if ($this->checkBoundary($line)) {
			return false;
		}
		if ($encoding == "base64") {
			return base64_decode($line);
		} else if ($encoding == "quoted-printable") {
			return quoted_printable_decode($line);
		}
		return $line;
	}
	private function decodeData($data, $encoding = false) {
		if ($encoding == "base64") {
			return base64_decode($data);
		} else if ($encoding == "quoted-printable") {
			return quoted_printable_decode($data);
		}
		return $data;
	}
	private function createFile($encoding = false) {
		$encoding = strtolower($encoding);
		$fname = file::temporary();
		$f = fopen($fname, "w");
		while (($line = $this->readEncodedLine($encoding)) !== false) {
			fwrite($f, $line);
		}
		fclose($f);
		return $fname;
	}
	private function createData($encoding = false) {
		$encoding = strtolower($encoding);
		$data = "";
		while (($line = $this->readEncodedLine($encoding)) !== false) {
			$data .= $line;
		}
		return $data;
	}
	private function createFileContent($type, $encoding, $disposition, $filename, $contentID = '') {
		$f = self::createFile($encoding);
		
		$fields["content_id"] = $contentID;
		$fields["content_type"] = $type;
		$fields["filename"] = basename($filename);
		$fields["disposition"] = $disposition;
		$fields["content_data"] = Content_Data::move_from_path($this->application, $f, false);
		
		$content = $this->application->object_factory(__NAMESPACE__ . "\\" . "Mail_Content", $fields);
		
		return $content;
	}
	private function createDataContent($type, $encoding = false) {
		$data = self::createData($encoding);
		
		$fields = array();
		
		$fields["content_type"] = strtolower($type);
		$fields["content_data"] = Content_Data::from_string($this->application, $data, false);
		
		return $this->application->object_factory(__NAMESPACE__ . "\\" . "Mail_Content", $fields);
	}
	private function parseMultiPart() {
		$headers = false;
		$content = array();
		
		$state = "start";
		
		$bound = $this->bound_stack[0];
		
		$state_prefix = str_repeat("*", 90);
		while (!feof($this->mail_file)) {
			switch ($state) {
				case "start":
					$line = $this->readLine();
					if ($this->checkBoundary($line)) {
						if ($this->is_bound_end) {
							$state = "done";
							if (mail::debug()) {
								echo "$state_prefix*** STATE *** -> done\n";
							}
						} else {
							$state = "headers";
							if (mail::debug()) {
								echo "$state_prefix*** STATE *** -> headers\n";
							}
						}
					} else {
						zesk()->logger->debug("Mail_Parser::parseMultiPart: start state didn't find boundary '--$bound' on first line... ($line)");
					}
					break;
				case "headers":
					$headers = self::parseHeaders();
					if (mail::debug()) {
						echo "$state_prefix*** STATE *** -> content\n";
					}
					$state = "content";
					break;
				case "content":
					$partContent = self::parseContent($headers);
					if (is_object($partContent)) {
						$content[] = $partContent;
					} else if (is_array($partContent)) {
						$content = array_merge($content, $partContent);
					}
					if ($this->is_bound_end) {
						if (mail::debug()) {
							echo "$state_prefix*** STATE *** -> done\n";
						}
						$state = "done";
					} else {
						if (mail::debug()) {
							echo "$state_prefix*** STATE *** -> headers\n";
						}
						$state = "headers";
					}
					$headers = false;
					break;
				case "done":
					if (feof($this->mail_file)) {
						zesk()->logger->notice("self::parse: Extra lines in file for boundary $bound");
					}
					return $content;
			}
		}
		$bound = $this->bound_stack[0];
		zesk()->logger->notice("Mail_Parser::parseMultiPart: Didn't find end boundary: $bound--");
		return $content;
	}
	
	/*
	 Examples:
	 
	 Content-Type: audio/microsoft-wave; name="smooch.wav";
	 x-mac-type="57415645"; x-mac-creator="5343504C"
	 Content-Transfer-Encoding: base64
	 Content-Disposition: attachment; filename="smooch.wav"
	 
	 Content-Type: text/plain; name="SerialSync.txt";
	 x-mac-type="42494E41"; x-mac-creator="74747874"
	 Content-Transfer-Encoding: base64
	 Content-Disposition: attachment; filename="SerialSync.txt"
	 
	 Content-Type: text/plain; charset=us-ascii
	 Content-Id:
	 Content-Disposition: inline
	 
	 Content-Type: application/x-msdownload; name="Calc32.exe"
	 Content-Transfer-Encoding: base64
	 Content-Description: Calc32.exe
	 Content-Disposition: attachment; filename="Calc32.exe"
	 
	 Content-Transfer-Encoding Values:
	 
	 Content-Disposition Values:
	 Content-Disposition: attachment;
	 Content-Disposition: attachment; filename="Calc32.exe"
	 Content-Disposition: attachment; filename="FILEMON.HLP"
	 Content-Disposition: attachment; filename="SerialSync.txt"
	 Content-Disposition: attachment; filename="smooch.wav"
	 Content-Disposition: inline
	 Content-Disposition: inline; filename="SerialSync.txt"
	 
	 Content-Description:
	 Content-Disposition:
	 Content-Id:
	 Content-Transfer-Encoding:
	 Content-Type:
	 
	 */
	private function parseContentHeaders($headers, &$encoding, &$disposition, &$filename, &$contentID, &$type, &$boundary) {
		/*
		 Pull out our data
		 */
		$desc = self::_getHeader($headers, "Content-Description");
		$encoding = self::_getHeader($headers, "Content-Transfer-Encoding");
		$contentID = self::_getHeader($headers, "Content-ID");
		$contentID = unquote($contentID, "<>");
		
		$disposition = false;
		$disposAttrib = array();
		
		$temp = self::_getHeader($headers, "Content-Disposition");
		if ($temp) {
			$disposition = self::parseContentLine($temp, $disposAttrib);
		}
		
		$type = false;
		$typeAttrib = array();
		
		$typeMajor = false;
		$typeMinor = false;
		$temp = self::_getHeader($headers, "Content-Type");
		if ($temp) {
			$type = strtolower(self::parseContentLine($temp, $typeAttrib));
			list($typeMajor, $typeMinor) = explode("/", $type);
		}
		
		/*
		 Is this a bad mailer?
		 */
		if ((!$encoding) && (!$disposition) && (!$type)) {
			zesk()->logger->warning("Mail_Parser::parseContentHeaders($this->mail_file): No Content-Type, Content-Transfer-Encoding, or Content-Disposition in mail section! line # $this->line_index");
			$type = "text/plain";
			return true;
		}
		
		$filename = false;
		$isFile = false;
		
		/*
		 First, determine what format the data is in:
		 7bit
		 base64
		 quoted-printable
		 */
		if ($encoding) {
			$encoding = strtolower($encoding);
			switch ($encoding) {
				case "7bit":
					break;
				case "base64":
					break;
				case "quoted-printable":
					break;
				default :
					zesk()->logger->notice("Mail_Parser::parseContentHeaders: Unknown encoding: \"$encoding\", using binary...");
					$encoding = "binary";
					break;
			}
		}
		if ($disposition) {
			switch ($disposition) {
				case "inline":
					if (!$encoding) {
						$encoding = "quoted-printable";
					}
					break;
				case "attachment":
					$isFile = true;
					break;
				default :
					zesk()->logger->warning("Mail_Parser::parseContentHeaders: Unknown disposition: \"$disposition\", assume attachment...");
					$dispos = "attachment";
					break;
			}
			if (isset($disposAttrib["filename"])) {
				$filename = $disposAttrib["filename"];
			}
		}
		if (!$filename && $desc) {
			$filename = $desc;
		}
		if ($type) {
			if (isset($typeAttrib["name"])) {
				$filename = $typeAttrib["name"];
			}
			$boundary = avalue($typeAttrib, "boundary");
			switch ($typeMajor) {
				case "text":
					if (!$filename) {
						$filename = "unknown_$typeMinor.txt";
					}
					if (!$encoding) {
						$encoding = "7bit";
					}
					break;
				default :
					if (!$filename) {
						$filename = "unknown_" . $typeMajor . "_" . $typeMinor;
					}
					$isFile = true;
					break;
			}
		} else {
			$type = "application/unknown";
		}
		if (!$isFile) {
			$filename = "";
		}
		return true;
	}
	private static function _guess_encoding(array $lines) {
		$is7bit = true;
		$isBase64 = true;
		foreach ($lines as $line) {
			if ($isBase64 && (preg_match('|[^\sA-Za-z0-9/+]|', $line) != 0)) {
				$isBase64 = false;
			}
			if ($is7bit && (preg_match("/[\x80-\xFF]/", $line) != 0)) {
				$is7bit = false;
			}
			if ((!$isBase64) && (!$is7bit)) {
				break;
			}
		}
		if ($isBase64) {
			return "base64";
		}
		if ($is7bit) {
			return "7bit";
		}
		return "binary";
	}
}

