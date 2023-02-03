<?php
declare(strict_types=1);

namespace zesk;

interface Interface_Member_Model_Factory {
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
	 * @throws Exception_NotFound
	 */
	public function memberModelFactory(string $member, string $class, mixed $value = null, array $options = []): Model;
}
