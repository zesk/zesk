<?php
declare(strict_types=1);
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
	public array $map = [];

	/**
	 *
	 * @var array
	 */
	public array $members = [];

	/**
	 *
	 * @param string $member
	 * @return boolean
	 */
	public function hasMember(string $member): bool {
		return array_key_exists($member, $this->members);
	}

	/**
	 *
	 * @param string $member
	 * @return boolean
	 */
	public function has_map(string $member): bool {
		return array_key_exists($member, $this->map);
	}

	/**
	 *
	 * @param string $member
	 * @param mixed $value
	 * @return self|array
	 */
	public function setMember(string $member, mixed $value = null): self {
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
	 * @param ORM $new
	 * @return self
	 */
	public function map(string $member, ORM $old, ORM $new): self {
		$this->map[$member][$old->id()] = $new->id();
		return $this;
	}

	/**
	 * Apply Options_Duplicate to
	 *
	 * Sets all inherited members, and maps all object IDs from one copy to another
	 *
	 * @param ORM $object
	 * @return $this
	 * @throws Exception_Deprecated
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 */
	public function processDuplicate(ORM $object): self {
		$members = [];
		foreach ($this->members as $member => $new_value) {
			if ($object->hasMember($member)) {
				$members[$member] = $new_value;
			}
		}
		foreach ($this->map as $member => $map) {
			if ($object->hasMember($member)) {
				$id = $object->memberInteger($member);
				$new_id = $map[$id] ?? null;
				if ($new_id !== null) {
					if (array_key_exists($member, $members)) {
						throw new Exception_Semantics('Member {member} for object class {class} is already set in the member map', [
							'member' => $member,
							'class' => get_class($object),
						]);
					}
					$members[$member] = $new_id;
				}
			}
		}
		if (count($members) > 0) {
			$object->setMembers($members);
		}
		return $this;
	}
}
