<?php
/**
 * @version $Id: tag.inc 4481 2017-03-24 18:21:48Z kent $
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

/**
 * @see Class_Contact_Tag
 * @author kent
 *
 */
class Contact_Tag extends ORM {
	public static function register_tag($name, $user) {
		$x = new self(array(
			"name" => $name,
			"user" => $user,
		));
		if (!$x->register()) {
			return null;
		}
		return $x;
	}

	public static function to_array(User $user, $where = null) {
		$query = $user->application->orm_registry(__CLASS__)->query_select();
		return $query->where('user', $user)
			->where($where)
			->order_by('name')
			->to_array('id');
	}

	public function store() {
		$result = parent::store();
		if ($result) {
			$this->grant_user($this->User);
		}
		return $result;
	}

	public function grant_user(User $user) {
		$this->query_insert()
			->values(array(
			"user" => $user,
			"contact_tag" => $this->id(),
		))
			->replace()
			->execute();
	}
}
