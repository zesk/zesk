<?php
/**
 * $URL: http://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/other.inc $
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @package zesk
 * @subpackage subpackage
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */

/**
 * Class Zesk_Contact_Other
 *
 * @author kent
 */
class Zesk_Contact_Other extends Contact_Info {

	function label_type() {
		return Contact_Label::LabelType_Other;
	}
}

