<?php
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2017, Market Acumen, Inc.
 */
namespace zesk;

/**
 * For things which support model factory calls
 */
interface Interface_Factory {
	
	/**
	 * Create a model
	 *
	 * @param string $class
	 * @param array $options
	 * @return Model
	 */
	public function model_factory($class, $mixed = null, array $options = array());
}

