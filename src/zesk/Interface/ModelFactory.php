<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\Interface;

use zesk\Model;

/**
 * For things which support model factory calls
 */
interface ModelFactory {
	/**
	 * Create a model
	 *
	 * @param string $class
	 * @param mixed|null $value
	 * @param array $options
	 * @return Model
	 */
	public function modelFactory(string $class, mixed $value = null, array $options = []): Model;
}
