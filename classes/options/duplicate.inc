<?php
class Options_Duplicate extends Options {
	public $map = array();
	public $members = array();
	public function has_member($member) {
		return array_key_exists($member, $this->members);
	}
	public function has_map($member) {
		return array_key_exists($member, $this->map);
	}
	public function member($member = null, $value = null) {
		if ($member === null) {
			return $this->members;
		}
		if (is_array($member)) {
			foreach ($member as $k => $v) {
				$this->set_member($k, $v);
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
	public function map($member, Object $old, Object $new) {
		$this->map[$member][$old->id()] = $new->id();
		return $this;
	}
	
	/**
	 * Apply Options_Duplicate to Object
	 *
	 * Sets all inherited members, and maps all object IDs from one copy to another
	 *
	 * @param Object $object        	
	 */
	public function process_duplicate(Object $object) {
		$members = array();
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
						throw new Exception_Semantics("Member {member} for object class {class} is already set in the member map", array(
							"member" => $member,
							"class" => get_class($object)
						));
					}
					$members[$member] = $new_id;
				}
			}
		}
		if (count($members) > 0) {
			$object->set_member($members);
		}
	}
}