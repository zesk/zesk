<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/whois/classes/whois/server.inc $
 * @author $Author: kent $
 * @package zesk
 * @subpackage whois
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
namespace zesk\Whois;

class Module extends \zesk\Module {
	protected $object_classes = array(
		"zesk\\Whois\\Contact",
		"zesk\\Whois\\NameServer",
		"zesk\\Whois\\Query",
		"zesk\\Whois\\Registrar",
		"zesk\\Whois\\Result",
		"zesk\\Whois\\Search",
		"zesk\\Whois\\TLD",
		"zesk\\Whois\\Server"
	);
}
