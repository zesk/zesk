<?php
/**
 * 
 */
namespace zesk\EmailEncrypt;

use zesk\HTML;

/**
 * 
 * @author kent
 *
 */
class Module extends \zesk\Module_JSLib {
	
	/**
	 * Array of strings of JS to load, or array of path (key) => $options to load
	 *
	 * @var array
	 */
	protected $javascript_paths = array(
		'/share/email_encrypt/decrypt.js'
	);
	public function initialize() {
		$this->application->hooks->add(HTML::tag_attributes_alter_hook_name("a"), array(
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
}
