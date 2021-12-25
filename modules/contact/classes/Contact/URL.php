<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage objects
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Contact_URL extends Contact_Info {
	public function label_type() {
		return Contact_Label::LabelType_URL;
	}
}
