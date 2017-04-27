<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/phone.inc $
 * @package zesk
 * @subpackage objects
 */
namespace zesk;

class Contact_Phone extends Contact_Info {
	function label_type() {
		return Contact_Label::LabelType_Phone;
	}
}
