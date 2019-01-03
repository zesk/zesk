<?php
namespace zesk;

interface Interface_Member_Model_Factory {
	/**
	 * Whenever an object attached to this object is requested, this method is called.
	 *
	 * Override in subclasses to get special behavior.
	 *
	 * @param string $member
	 *        	Name of the member we are fetching
	 *
	 * @param string $class
	 *        	Class of member
	 * @param string $data
	 *        	Current data stored in member
	 * @param array $options
	 *        	Options to create when creating object
	 * @return Model|null
	 */
	public function member_model_factory($member, $class, $mixed = null, array $options = array());
}
