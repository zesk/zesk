<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/date.inc $
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
	function label_type() {
		return Contact_Label::LabelType_Date;
	}
}
