<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Interface
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\Interface;

use zesk\Exception\NotFoundException;
use zesk\Model;

interface MemberModelFactory {
	/**
	 * Whenever an object attached to this object is requested, this method is called.
	 *
	 * Override in subclasses to get special behavior.
	 *
	 * @param string $member Name of the member we are fetching
	 * @param string $class Class of member
	 * @param mixed $value Current data stored in member
	 * @param array $options Options to create when creating model
	 * @return Model
	 * @throws NotFoundException
	 */
	public function memberModelFactory(string $member, string $class, mixed $value = null, array $options = []): Model;
}
