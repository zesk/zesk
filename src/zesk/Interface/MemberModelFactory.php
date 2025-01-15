<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\Interface;

use zesk\Model;

/**
 * For things which support model factory calls
 * @see Model
 */
interface MemberModelFactory {
	/**
	 * Create a model
	 *
	 * @param string $member
	 * @param string $class
	 * @param array $value
	 * @param array $options
	 * @return Model
	 */
	public function memberModelFactory(string $member, string $class, mixed $value = null, array $options = []): Model;
}
