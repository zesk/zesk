<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/builder/base.inc $
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */

abstract class Contact_Builder_Base extends Options {
	protected $data = array();
	protected $contact_class = null;
	
	function __construct($data = null, $options = null) {
		parent::__construct($options);
		$this->data = is_array($data) ? $data : array();
	}
	
	abstract public function process(Contact_Import $import, $key, $value);
}
