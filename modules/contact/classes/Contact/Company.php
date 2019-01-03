<?php
/**
 * @package zesk
 * @subpackage contact
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Contact_Company extends Contact_Info {
	public function label_type() {
		return Contact_Label::LabelType_Company;
	}
}
