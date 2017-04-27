<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/other.inc $
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
	function label_type() {
		return Contact_Label::LabelType_Other;
	}
}

