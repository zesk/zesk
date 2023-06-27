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
 * @see Model
 */
interface ModelFactory
{
	/**
	 * Create a model
	 *
	 * @param string $class
	 * @param array $value
	 * @param array $options
	 * @return Model
	 */
	public function modelFactory(string $class, array $value = [], array $options = []): Model;
}
