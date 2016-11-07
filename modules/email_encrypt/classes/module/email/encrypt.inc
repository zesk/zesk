<?php
class Module_Email_Encrypt extends Module_JSLib {
	
	/**
	 * Array of strings of JS to load, or array of path (key) => $options to load
	 *
	 * @var array
	 */
	protected $javascript_paths = array(
		'/share/email_encrypt/decrypt.js'
	);
	public function initialize() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$zesk->hooks->add(HTML::tag_attributes_alter_hook_name("a"), array(
			$this,
			"alter_a_tag"
		));
	}
	
	/**
	 * Alter a tag attributes
	 * 
	 * @param array $attributes
	 * @return unknown
	 */
	public function alter_a_tag(array $attributes) {
		if (!isset($attributes['href'])) {
			return $attributes;
		}
		$href = $attributes[$href];
		if (!begins($href, "mailto:")) {
			return $attributes;
		}
		$offset = mt_rand(1, 10);
		$doff = 32 + $offset;
		
		$payload = $this->caeser_encrypt($href, $offset);
		
		$attributes['href'] = '#';
		$attributes['data-href-seed'] = $offset;
		$attributes['data-href-encrypted'] = $payload;
		
		return $attributes;
	}
	
	/**
	 * 
	 * @param string $x payload
	 * @param integer $y offset
	 * @return string
	 */
	private function caesar_encrypt($x, $y) {
		$p = "";
		for ($i = 0; $i < strlen($x); $i++) {
			$p = $p . chr((ord($x[$i]) - 32) % 240 + $y + 32);
		}
		return urlencode($p);
	}
	
	
// 	public static function encrypt($e, $hr = false, $noscript = true) {
// 		if (empty($e)) {
// 			return "";
// 		}
// 		$offset = mt_rand(1, 10);
// 		$doff = 32 + $offset;
// 		$link = "<a href=\"mailto:" . $e . "\">$hr</a>";
// 		$j = "";
// 		$scr = "";
// 		$scr = $scr . "var e = unescape(\"" . self::caesar_encrypt($link, $offset) . "\");\n";
// 		$scr = $scr . "var i,p='';for(i=0;i<e.length;i++){p+=String.fromCharCode(((e.charCodeAt(i)-" . $doff . ")%240)+32);}\n";
// 		$code = zesk::get('encrypt-email-id', 0);
// 		$scr .= "\$('#enc_email_$code').html(p);";
// 		Response::instance()->jquery("(function () { " . $scr . " })();");
// 		$j = $j . "<span id=\"enc_email_$code\"></span>";
// 		zesk::set('encrypt-email-id', $code + 1);
		
// 		$e = explode("@", $e);
// 		if ($noscript) {
// 			$j = $j . "<noscript>" . $e[0] . " <em>-a<!-- " . mt_rand() . " -->t-</em> ";
// 			$f = explode(".", $e[1]);
// 			$j = $j . implode(" <em>d<!-- " . mt_rand() . " -->ot</em> ", $f);
// 			$j = $j . "</noscript>";
// 		}
		
// 		return $j;
// 	}
}
