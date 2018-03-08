<?php
/**
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
abstract class Contact_Builder_Base extends Options {
	/**
	 * 
	 * @var array
	 */
	protected $data = array();
	
	/**
	 * 
	 * @var string
	 */
	protected $contact_class = null;
	
	/**
	 * 
	 * @param array $data
	 * @param array $options
	 */
	function __construct($data = null, array $options = array()) {
		parent::__construct($options);
		$this->data = is_array($data) ? $data : array();
	}
	
	/**
	 * 
	 * @param Contact_Import $import
	 * @param string $key
	 * @param mixed $value
	 */
	abstract public function process(Contact_Import $import, $key, $value);
}
