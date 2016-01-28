<?php
/**
 * $URL: http://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/company.inc $
 * @package zesk
 * @subpackage contact
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */

/**
 * Zesk_Contact_Company
 */
class Zesk_Contact_Company extends Contact_Info {
	function label_type() {
		return Contact_Label::LabelType_Company;
	}
}
/**
 * Class_Zesk_Contact_Company
 */
class Class_Zesk_Contact_Company extends Class_Contact_Info {}
