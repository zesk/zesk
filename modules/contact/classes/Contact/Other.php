<?php declare(strict_types=1);
/**
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @package zesk
 * @subpackage subpackage
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
namespace zesk;

class Contact_Other extends Contact_Info {
	/**
	 * @see Contact_Label::LabelType_Foo
	 *
	 * @return string
	 */
	public function label_type() {
		return Contact_Label::LabelType_Other;
	}
}
