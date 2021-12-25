<?php declare(strict_types=1);
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
	public function model_factory(string $class, mixed $mixed = null, array $options = []): Model;
}
