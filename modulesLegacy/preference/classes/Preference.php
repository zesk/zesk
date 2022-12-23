<?php
declare(strict_types=1);

/**
 * @version $Id: Preference.php 4555 2017-04-06 18:32:10Z kent $
 * @package zesk
 * @subpackage user
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

/**
 *
 * @see Class_Preference
 *
 * @author kent
 */
class Preference extends ORMBase {
	public const type_class = 'zesk\\Preference_Type';

	/**
	 * Store - check requirements
	 *
	 * @return boolean
	 * @see ORMBase::store()
	 */
	public function store(): self {
		if ($this->memberIsEmpty('user')) {
			throw new Exception_Parameter('NULL value for user');
		}
		if ($this->memberIsEmpty('type')) {
			throw new Exception_Parameter('NULL value for type');
		}
		return parent::store();
	}

	/**
	 * Does the user have a perference value?
	 *
	 * @param user $user
	 * @param string $name
	 * @return boolean
	 */
	public static function user_has(User $user, $name) {
		$pref = self::user_get($user, $name, null);
		return !empty($pref);
	}

	public static function user_has_one(User $user, $name) {
		return $user->application->ormRegistry(__CLASS__)->querySelect()->join('zesk\\Preference_Type', ['alias' => 'T', ])->addWhere('T.code', $name)->addWhere('X.user', $user)->what('value', 'COUNT(X.value)')->one_integer('value') !== 0;
	}

	/**
	 * Internal function to retrieve
	 *
	 * @param user $user
	 * @return Ambigous <zesk\Database_Query_Select, zesk\Database_Query_Select>
	 * @todo Replace user_has and userGet to use this instead for query basis - write test to ensure
	 *       it works identically first!
	 */
	private static function _value_query(User $user, $name) {
		$query = $user->application->ormRegistry(__CLASS__)->querySelect()->link(self::type_class, 'type')->where([
			'X.user' => $user,
			'type.code' => $name,
		]);
		return $query;
	}

	public static function user_get(User $user, $pref_name, $default = null) {
		if (empty($pref_name)) {
			throw new Exception_Parameter('{method}({user}, {name}, ...) Name is empty', [
				'method' => __METHOD__,
				'user' => $user->id(),
				'name' => $pref_name,
			]);
		}
		$pref_name = strtolower($pref_name);
		$row = $user->application->ormRegistry(__CLASS__)->querySelect()->link(self::type_class, ['alias' => 'T', ])->addWhat('value', 'X.value')->addWhat('id', 'X.id')->appendWhere([
			'T.code' => $pref_name,
			'X.user' => $user,
		])->one();
		if (!is_array($row)) {
			return $default;
		}
		$value = $row['value'];
		$vlen = strlen($value);
		if ($vlen >= 4 && $value[1] === ':' && $value[$vlen - 1] === ';') {
			return PHP::unserialize($value);
		} else {
			$user->application->logger->warning('Invalid preference string for {user}: {key}={value} ({vlen} chars) - deleting ({debug})', [
				'user' => $user,
				'key' => $pref_name,
				'value' => $value,
				'debug' => PHP::dump($row),
				'vlen' => $vlen,
			]);
			$user->application->ormRegistry(Preference::class)->query_delete()->addWhere('id', $row['id'])->execute();
		}
		return $default;
	}

	public static function user_get_single(User $user, $name, $default) {
		$result = $user->application->ormRegistry(__CLASS__)->querySelect()->join(self::type_class, [
			'alias' => 'T',
		])->appendWhere(['T.code' => $name, 'X.user' => $user])->addWhat('value', 'X.value')->one('value');
		if ($result === null) {
			return $default;
		}
		return @unserialize($result);
	}

	/**
	 * @throws Exception_ORM_Duplicate
	 * @throws Exception_Parameter
	 * @throws Exception_Semantics
	 */
	public static function userSet(User $user, array $values): array {
		$app = $user->application;
		$result = [];
		foreach ($values as $name => $value) {
			$type = Preference_Type::registerName($app, $name);
			if (!$type) {
				$result[$name] = null;
				continue;
			}
			$orm_id = self::_register($user, $type, $value);
			$result[$name] = $orm_id;
		}
		return $result;
	}

	public static function userGet(User $user, array $preferences) {
		$names = array_keys($preferences);
		$row = $user->application->ormRegistry(__CLASS__)->querySelect()->link(self::type_class, ['alias' => 'T', ])->addWhat('value', 'X.value')->addWhat('id', 'X.id')->addWhere('T.code', $names)->addWhere('X.user', $user)->toArray();
		if (!is_array($row)) {
			return $default;
		}
		$value = $row['value'];
		$vlen = strlen($value);
		if ($vlen >= 4 && $value[1] === ':' && $value[$vlen - 1] === ';') {
			return PHP::unserialize($value);
		} else {
			$user->application->logger->warning('Invalid preference string for {user}: {key}={value} ({vlen} chars) - deleting ({debug})', [
				'user' => $user,
				'key' => $pref_name,
				'value' => $value,
				'debug' => PHP::dump($row),
				'vlen' => $vlen,
			]);
			$user->application->ormRegistry(Preference::class)->query_delete()->addWhere('id', $row['id'])->execute();
		}
		return $default;
	}

	/**
	 * @param User $user
	 * @param string $type
	 * @param mixed $value
	 * @return int
	 * @throws Exception_ORM_Duplicate
	 * @throws Exception_Parameter
	 * @throws Exception_Semantics
	 */
	private static function _register(User $user, Preference_Type $type, mixed $value): int {
		$app = $user->application;
		$dbvalue = serialize($value);
		$query = $app->ormRegistry(__CLASS__)->querySelect()->addWhat('id')->addWhat('value')->appendWhere([
			'user' => $user,
			'type' => $type,
		]);
		$result = $query->one(null);
		if ($result) {
			if ($result['value'] === $dbvalue) {
				return intval($result['id']);
			}
			$app->ormRegistry(__CLASS__)->queryUpdate()->value('value', $dbvalue)->addWhere('id', $result['id'])->execute();
			return intval($result['id']);
		}
		return $app->ormFactory(__CLASS__, ['type' => $type, 'value' => $value, 'user' => $user, ])->store()->id();
	}
}
