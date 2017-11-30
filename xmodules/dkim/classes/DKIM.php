<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/dkim/classes/dkim.inc $
 * @package zesk
 * @subpackage DKIM
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Fri Mar 06 09:29:20 EST 2009 09:29:20
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class DKIM {
	/**
	 * 
	 * @var string
	 */
	private $PublicKey;
	/**
	 * 
	 * @var string
	 */
	private $PrivateKey;
	/**
	 * 
	 * @var unknown
	 */
	private $Domain;
	
	/**
	 * 
	 * @var unknown
	 */
	private $Selector;
	
	/**
	 * 
	 * @param unknown $domain
	 * @param unknown $public_key_path
	 * @param unknown $private_key_path
	 * @param string $selector
	 * @throws Exception_DKIM
	 */
	public function __construct($domain, $public_key_path, $private_key_path, $selector = false) {
		$this->Domain = $domain;
		$this->Selector = empty($selector) ? "all" : "";
		
		$this->PublicKey = @file_get_contents($public_key_path);
		$this->PrivateKey = @file_get_contents($private_key_path);
		
		if (empty($this->PublicKey) || empty($this->PrivateKey)) {
			throw new Exception_DKIM("DKIM public/private keys not found. To generate:\n\n" . "openssl genrsa -out domain.key 1024\n" . "openssl rsa -in domain.key -out domain.pub -pubout -outform PEM\n\n" . "Then pass in the paths to these files:\n" . "\$dkim = new DKIM('domain', 'path/to/domain.pub', 'path/to/domain.key');\n");
		}
	}
	public static function enabled() {
		return function_exists("openssl_sign");
	}
	public function sign($headers_lines, $body) {
		$headers_lines = $this->add_domain_key($headers_lines, $body);
		$headers_lines = $this->add_dkim($headers_lines, $body);
		
		return $headers_lines;
	}
	public function dns_txt_record() {
		return $this->dns_txt_record_selector($this->Selector, "v=DKIM1; k=rsa; g=*; s=email; h=sha1; t=s;") . $this->dns_txt_record_selector($this->Selector . "4870", "g=; k=rsa;");
	}
	
	/*
	 recname	IN	TXT	( "v=DKIM1; g=*; k=rsa; "
	 "p=MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA1Z4F"
	 "JEMHjJDuBmt25zvYFVejlARZGt1L8f0s1+rLxIPYkfCogQi+Y8"
	 "oLEg9vvEKnLx9aogZzuNt6j4Sty3LgXxaIwHnMqk0LldbA/mh3"
	 "wLZb16Wc6btXHON0o3uDipxqGK2iRLTvcgAnNDegseOS+i0aJE"
	 "nNSl663ywRBp/QKezhUC7cnbqR/H8dz8pEOjeawNN3nexdHGsk"
	 "+RaafYvCFvU+70CQORcsk+mxb74SwGT2CGHWxVywQA9yrV+sYk"
	 "JpxaufZLo6xp0Z7RZmbf1eGlCAdhkEy+KYQpQkw2Cdl7iKIK4+"
	 "17gr+XZOrfFLJ5IwpVK/a19m3BLxADf0Kh3oZwIDAQAB" )
	 */
	private function dns_txt_record_selector($selector, $prefix) {
		$pub_lines = explode("\n", $this->PublicKey);
		$txt_data = $selector . "._domainkey IN TXT ( ";
		$spaces = strlen($txt_data);
		$txt_record[] = $txt_data . "\"$prefix \"";
		
		$txt_data = "p=";
		foreach ($pub_lines as $pub_line) {
			$pub_line = trim($pub_line);
			if (empty($pub_line)) {
				continue;
			}
			if (strpos($pub_line, '-----') !== 0) {
				$txt_data .= $pub_line;
			}
		}
		$txt_data .= ";";
		do {
			$txt_record[] = str_repeat(" ", $spaces) . "\"" . substr($txt_data, 0, 50) . "\"";
			$txt_data = substr($txt_data, 50);
		} while (strlen($txt_data) > 0);
		
		$txt_record = implode("\n", $txt_record) . " )\n";
		return $txt_record;
	}
	private static function quoted_printable($txt) {
		$line = "";
		$n = strlen($txt);
		for ($i = 0; $i < $n; $i++) {
			$ord = ord($txt[$i]);
			if (((0x21 <= $ord) && ($ord <= 0x3A)) || $ord == 0x3C || ((0x3E <= $ord) && ($ord <= 0x7E))) {
				$line .= $txt[$i];
			} else {
				$line .= "=" . sprintf("%02X", $ord);
			}
		}
		return $line;
	}
	private function sign_content($s) {
		$signature = false;
		if (openssl_sign($s, $signature, $this->PrivateKey)) {
			return base64_encode($signature);
		} else {
			throw new Exception_DKIM("Cannot sign message: $s");
		}
	}
	public static function dump($what, $data) {
		echo "dump ($what):\n------\n";
		echo self::_dump($data);
		echo "\n------\n";
	}
	public static function _dump($data) {
		return str_replace(array(
			"\r",
			"\n",
			"\t",
			" "
		), array(
			"'OD'",
			"'OA'\n",
			"'09'",
			"'20'"
		), $data);
	}
	private static function dkim_simple_header_canonicalization($s) {
		return $s;
	}
	private static function dkim_relaxed_header_canonicalization($s) {
		$s = self::unfold_header_lines($s);
		
		$lines = explode("\r\n", $s);
		
		foreach ($lines as $key => $line) {
			list($heading, $value) = explode(":", $line, 2);
			
			$heading = strtolower($heading);
			$value = preg_replace('/\s+/', " ", $value); // Compress useless spaces
			
			$lines[$key] = $heading . ":" . trim($value); // Don't forget to remove WSP around the value
		}
		
		$s = implode("\r\n", $lines);
		
		return $s;
	}
	private static function dkim_simple_body_canonicalization($body) {
		if (empty($body)) {
			return "\r\n";
		}
		
		$body = str_replace("\r", "", $body);
		$body = str_replace("\n", "\r\n", $body);
		$body = rtrim($body, "\r\n") . "\r\n";
		
		//self::dump('dkim_simple_body_canonicalization', $body);
		
		return $body;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $headers_lines
	 * @return unknown
	 */
	private static function clean_headers($headers_lines) {
		$headers_lines = trim($headers_lines, "\r\n");
		$headers_lines = str_replace("\r", "", $headers_lines);
		$headers_lines = preg_replace("/\n+/", "\n", $headers_lines);
		$headers_lines = str_replace("\n", "\r\n", $headers_lines);
		
		return $headers_lines;
	}
	public static function clean_newlines($email) {
		$email = str_replace("\r\n", "\n", $email);
		$email = str_replace("\n", "\r\n", $email);
		return $email;
	}
	private static function unfold_header_lines($headers) {
		return preg_replace('/\r\n\s+/', ' ', $headers);
	}
	private static function parse_headers($headers) {
		$headers = self::unfold_header_lines($headers);
		
		// Divide headers into lines
		$headers = explode("\r\n", $headers);
		
		$result = array();
		foreach ($headers as $lineno => $header) {
			$parts = explode(":", $header, 2);
			if (count($parts) === 1) {
				throw new Exception_DKIM("Invalid header found at line " . ($lineno + 1) . " in headers: $header");
			}
			$result[strtolower(trim($parts[0]))] = $header;
		}
		return $result;
	}
	private function add_dkim($headers_lines, $body) {
		$headers_lines = self::clean_headers($headers_lines);
		$headers = self::parse_headers($headers_lines);
		
		$from_header = array_key_exists('from', $headers) ? $headers['from'] : null;
		$to_header = array_key_exists('to', $headers) ? $headers['to'] : null;
		$subject_header = array_key_exists('subject', $headers) ? $headers['subject'] : null;
		
		if ($from_header === null || $to_header === null || $subject_header === null) {
			throw new Exception_DKIM("From:, To:, or Subject: headers are not present in headers_line: $headers_lines");
		}
		$from = str_replace('|', '=7C', self::quoted_printable($from_header));
		$to = str_replace('|', '=7C', self::quoted_printable($to_header));
		$subject = str_replace('|', '=7C', self::quoted_printable($subject_header));
		
		$body = self::dkim_simple_body_canonicalization($body);
		
		$body_length = strlen($body); // Length of body (in case MTA adds something afterwards)
		$body_hash = base64_encode(pack("H*", sha1($body))); // Base64 of packed binary SHA-1 hash of body
		$now = time();
		
		$dkim = "DKIM-Signature: v=1; a=rsa-sha1; q=dns/txt; l=$body_length; s=$this->Selector;\r\n" . "  t=$now; c=relaxed/simple;\r\n" . "  h=From:To:Subject;\r\n" . "  d=$this->Domain;\r\n" . "  z=$from\r\n" . "  |$to\r\n" . "  |$subject;\r\n" . "  bh=$body_hash;\r\n" . "  b=";
		
		$to_be_signed = $this->dkim_relaxed_header_canonicalization("$from_header\r\n$to_header\r\n$subject_header\r\n$dkim");
		$b = $this->sign_content($to_be_signed);
		
		return $dkim . $b . "\r\n$headers_lines";
	}
	
	/*
	 3.4.2.2.  The "nofws" Canonicalization Algorithm
	 
	 The "No Folding Whitespace" algorithm (nofws) is more complicated
	 than the "simple" algorithm for two reasons; folding whitespace is
	 removed from all lines and header continuation lines are unwrapped.
	 
	 o  Each line of the email is presented to the signing algorithm in
	 the order it occurs in the complete email, from the first line
	 of the headers to the last line of the body.
	 
	 o  Header continuation lines are unwrapped so that header lines
	 are processed as a single line.
	 
	 o  If the "h" tag is used, only those header lines (and their
	 continuation lines if any) added to the "h" tag list are
	 included.
	 
	 o  The "h" tag only constrains header lines.  It has no bearing on
	 body lines, which are always included.
	 
	 o  For each line in the email, remove all folding whitespace
	 characters.  Folding whitespace is defined in RFC 2822 as being
	 the decimal ASCII values 9 (HTAB), 10 (NL), 13 (CR), and 32
	 (SP).
	 
	 o  Append CRLF to the resulting line.
	 
	 o  Trailing empty lines are ignored.  An empty line is a line of
	 zero length after removal of the local line terminator.  Note
	 that the test for an empty line occurs after removing all
	 folding whitespace characters.
	 
	 If the body consists entirely of empty lines, then the
	 header/body line is similarly ignored.
	 */
	private static function nofws_strip($string) {
		return str_replace(array(
			"\n",
			"\r",
			" ",
			"\t"
		), array(
			'',
			'',
			'',
			''
		), $string);
	}
	public static function domainkeys_simple_canonicalize($headers, $body) {
		$headers = self::clean_headers($headers);
		$body = self::clean_newlines($body);
		if (!empty($headers)) {
			$headers = rtrim($headers, "\r\n") . "\r\n";
		}
		if (!empty($body)) {
			$prefix = (empty($headers)) ? "" : "\r\n";
			$body = $prefix . rtrim($body, "\r\n") . "\r\n";
		}
		$result = $headers . $body;
		//self::dump("domainkeys_simple_canonicalize", $result);
		return $result;
	}
	public static function domainkeys_nofws_canonicalize($headers, $body) {
		$headers = self::clean_headers($headers);
		$headers = self::unfold_header_lines($headers);
		$body = self::clean_newlines($body);
		
		$headers_lines = explode("\n", $headers);
		
		$hlines = array();
		$blines = array();
		foreach ($headers_lines as $header_line) {
			$line = self::nofws_strip($header_line);
			if (empty($line)) {
				continue;
			}
			$hlines[] = $line;
		}
		if (trim($body) !== '') {
			$body_lines = explode("\n", $body);
			foreach ($body_lines as $body_line) {
				$line = self::nofws_strip($body_line);
				$blines[] = $line;
			}
		}
		if (count($blines) !== 0) {
			if (count($hlines) !== 0) {
				$hlines[] = "";
			}
		}
		if (count($hlines) !== 0) {
			$hlines[] = "";
		}
		$blines = implode("\r\n", $blines);
		if ($blines) {
			$blines = rtrim($blines, "\r\n") . "\r\n";
		}
		$lines = implode("\r\n", $hlines) . $blines;
		//self::dump("domainkeys_nofws_canonicalize", $lines);
		return $lines;
	}
	private function add_domain_key($headers_lines, $body) {
		$headers_lines = $this->clean_headers($headers_lines);
		$headers = $this->parse_headers($headers_lines);
		
		$headers_signed = array_keys($headers);
		
		$headers_map = array_change_key_case(array_flip($headers_signed));
		
		$headers_canon = array();
		foreach ($headers as $k => $header_line) {
			if (array_key_exists($k, $headers_map)) {
				unset($headers_map[$k]);
				$headers_canon[] = $header_line;
			}
		}
		if (count($headers_map) !== 0) {
			throw new Exception_DKIM("Not all headers were removed: " . implode(", ", array_keys($headers_map)));
		}
		
		$headers_canon = implode("\r\n", $headers_canon);
		
		$domainkeys_tags = "a=rsa-sha1; c=nofws; d=$this->Domain; h=" . implode(":", $headers_signed) . "; q=dns; s={$this->Selector}4870;\r\n\tb=";
		
		$b = $this->sign_content(self::domainkeys_nofws_canonicalize($headers_canon, $body));
		
		return "DomainKey-Signature: " . $domainkeys_tags . $b . "\r\n$headers_lines";
	}
}
