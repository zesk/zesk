<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage objects
 */
namespace zesk;

class Contact_Phone extends Contact_Info {
	public function label_type() {
		return Contact_Label::LabelType_Phone;
	}
}
