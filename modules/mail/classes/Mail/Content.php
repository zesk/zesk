<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/mail/classes/mail/content.inc $
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 * @package zesk
 * @subpackage mail
 */
namespace zesk;

/**
 * Class to hold data blobs in email
 *
 * @author kent
 * @see Class_Mail_Content
 * @property Mail_Message $mail
 * @property Content_Data $content_data
 */
class Mail_Content extends Object {
	function original_file_name($set = null) {
		if ($set !== null) {
			$this->filename = $set;
			return $this;
		}
		return $this->filename;
	}
	function disposition($set = null) {
		if ($set !== null) {
			$this->disposition = $set;
			return $this;
		}
		return $this->disposition;
	}
	function dump() {
		$r = "";
		
		$r .= "Content-Type: " . $this->content_type . "\n";
		$r .= "Content-Length: " . $this->content_data->size . "\n";
		$r .= "Original File Name: " . $this->original_file_name() . "\n";
		
		if (!empty($this->content)) {
			$r .= $this->content;
		}
		
		return $r;
	}
	function size() {
		return $this->content_data->size();
	}
	function contents() {
		return $this->content_data->data();
	}
	function fp() {
		return $this->content_data->fp();
	}
	function hash() {
		return $this->hash;
	}
}

