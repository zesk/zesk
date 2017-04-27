<?php
/**
* $URL: https://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/url.inc $
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
	function label_type() {
		return Contact_Label::LabelType_URL;
	}
}
