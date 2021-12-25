<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 * This object is used to duplicate large objects which have a lot of inter-references.
 *
 * While duplicating the object, references to old and new object are managed, and
 * allows for defaults to be applied to the new objects during duplication.
 *
 * @author kent
 *
 */
class Options_Duplicate extends Options {
	/**
	 *
	 * @var array
	 */
	public $map = [];

	/**
	 *
	 * @var array
	 */
	public $members = [];

	/**
	 *
	 * @param string $member
	 * @return boolean
	 */
	public function has_member($member) {
		return array_key_exists($member, $this->members);
	}

	/**
	 *
	 * @param string $member
	 * @return boolean
	 */
	public function has_map($member) {
		return array_key_exists($member, $this->map);
	}

	/**
	 *
	 * @param string $member
	 * @param mixed $value
	 * @return self|array
	 */
	public function member($member = null, $value = null) {
		if ($member === null) {
			return $this->members;
		}
		if (is_array($member)) {
			foreach ($member as $k => $v) {
				$this->member($k, $v);
			}
			return $this;
		}
		if ($value === null) {
			unset($this->members[$member]);
			return $this;
		}
		$this->members[$member] = $value;
		return $this;
	}

	/**
	 *  $old is now $new in new object, so map appropriate fields
	 *
	 * @param string $member
	 * @param ORM $old
	 * @param  ORM $new
	 * @return self
	 */
	public function map($member, $old, $new) {
		$this->map[$member][$old->id()] = $new->id();
		return $this;
	}

	/**
	 * Apply Options_Duplicate to
	 *
	 * Sets all inherited members, and maps all object IDs from one copy to another
	 *
	 * @param  $object
	 * @return $this
	 * @throws Exception_Semantics
	 */
	public function process_duplicate($object) {
		$members = [];
		foreach ($this->members as $member => $new_value) {
			if ($object->has_member($member)) {
				$members[$member] = $new_value;
			}
		}
		foreach ($this->map as $member => $map) {
			if ($object->has_member($member)) {
				$id = $object->member_integer($member);
				$new_id = avalue($map, $id, null);
				if ($new_id !== null) {
					if (array_key_exists($member, $members)) {
						throw new Exception_Semantics("Member {member} for object class {class} is already set in the member map", [
							"member" => $member,
							"class" => get_class($object),
						]);
					}
					$members[$member] = $new_id;
				}
			}
		}
		if (count($members) > 0) {
			$object->set_member($members);
		}
		return $this;
	}
}
