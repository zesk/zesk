<?php
/**
 * @version $Id: email.inc 4481 2017-03-24 18:21:48Z kent $
 * @package zesk
 * @subpackage objects
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

class Contact_Email extends Contact_Info {
	/**
	 * @see Contact_Label::LabelType_Foo
	 *
	 * @return string
	 */
	function label_type() {
		return Contact_Label::LabelType_Email;
	}
	function verified() {
		$this->verified = "now";
		$this->store();
	}
	public static function find_email($email) {
		return app()->object_factory(__CLASS__, array(
			'value' => $email
		))->find();
	}
}
