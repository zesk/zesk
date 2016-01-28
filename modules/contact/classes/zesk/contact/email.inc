<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/contact/classes/zesk/contact/email.inc $
 * @package zesk
 * @subpackage default
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2006, Market Acumen, Inc.
 */

/**
 * Zesk_Contact_Email
 */
class Zesk_Contact_Email extends Contact_Info {
	function verified() {
		$this->Verified = "now";
		$this->store();
	}
	function label_type() {
		return Contact_Label::LabelType_Email;
	}
	public static function find_email($email) {
		return Object::factory('Contact_Email', array(
			'Value' => $email
		))->find();
	}
}
