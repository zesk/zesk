<?php declare(strict_types=1);
/**
 * @version $Id: tag.inc 4481 2017-03-24 18:21:48Z kent $
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/**
 * @see Class_Contact_Tag
 * @author kent
 *
 */
class Contact_Tag extends ORMBase {
	public static function register_tag($name, $user) {
		$x = new self([
			'name' => $name,
			'user' => $user,
		]);
		if (!$x->register()) {
			return null;
		}
		return $x;
	}

	public static function toArray(User $user, $where = null) {
		$query = $user->application->ormRegistry(__CLASS__)->querySelect();
		return $query->addWhere('user', $user)
			->where($where)
			->order_by('name')
			->toArray('id');
	}

	public function store(): self {
		$result = parent::store();
		if ($result) {
			$this->grant_user($this->User);
		}
		return $result;
	}

	public function grant_user(User $user): void {
		$this->query_insert()
			->values([
				'user' => $user,
				'contact_tag' => $this->id(),
			])
			->replace()
			->execute();
	}
}
