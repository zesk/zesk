<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage default
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2006, Market Acumen, Inc.
 */
namespace zesk;

/**
 * @see Class_Contact_Date
 */
class Contact_Date extends Contact_Info {
	public function label_type() {
		return Contact_Label::LabelType_Date;
	}
}
