<?php
/**
 * @version $Id: tag.inc 4481 2017-03-24 18:21:48Z kent $
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

class Contact_Tag extends Object {
	public static function register_tag($name, $user) {
		$x = new self(array(
			"name" => $name,
			"user" => $user
		));
		if (!$x->register()) {
			return null;
		}
		return $x;
	}
	public static function to_array(User $user, $where = null) {
		$query = $user->application->query_select(__CLASS__);
		return $query->where('user', $user)
			->where($where)
			->order_by('name')
			->to_array('id');
	}
	function store() {
		$result = parent::store();
		if ($result) {
			$this->grant_user($this->User);
		}
		return $result;
	}
	function grant_user(User $user) {
		$this->query_insert()
			->values(array(
			"user" => $user,
			"contact_tag" => $this->id()
		))
			->replace()
			->execute();
	}
}
