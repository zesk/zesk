<?php declare(strict_types=1);

/**
 * @version $Id: email.inc 4481 2017-03-24 18:21:48Z kent $
 * @package zesk
 * @subpackage objects
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

class Contact_Email extends Contact_Info {
	/**
	 *
	 * @see Contact_Label::LabelType_Foo
	 *
	 * @return string
	 */
	public function label_type() {
		return Contact_Label::LabelType_Email;
	}

	public function verified(): void {
		$this->verified = 'now';
		$this->store();
	}

	public static function find_email(Application $app, $email) {
		return $app->ormFactory(__CLASS__, [
			'value' => $email,
		])->find();
	}
}
