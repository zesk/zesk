<?php
/**
 * $URL
 * @author $Author: kent $
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * @package zesk
 * @subpackage whois
 */
namespace zesk\Whois;

class Contact extends Object {
	protected $columns = array(
		"ID",
		"Contact",
		"Whois_Contact_Type",
		"Whois_Result"
	);
	protected $has_one = array(
		"Contact",
		"Whois_Contact_Type",
		"Whois_Result"
	);
}
