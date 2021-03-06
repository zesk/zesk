<?php

/**
 * @version $Id: Preference.php 4555 2017-04-06 18:32:10Z kent $
 * @package zesk
 * @subpackage user
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2013, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @see Class_Preference
 *
 * @author kent
 */
class Preference extends ORM {
	const type_class = "zesk\\Preference_Type";

	/**
	 * Store - check requirements
	 *
	 * @see ORM::store()
	 * @return boolean
	 */
	public function store() {
		if ($this->member_is_empty("user")) {
			throw new Exception_Parameter("NULL value for user");
		}
		if ($this->member_is_empty("type")) {
			throw new Exception_Parameter("NULL value for type");
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
		return $user->application->orm_registry(__CLASS__)
			->query_select()
			->join('zesk\\Preference_Type', array(
			'alias' => 'T',
		))
			->where('T.code', $name)
			->where('X.user', $user)
			->what('value', 'COUNT(X.value)')
			->one_integer('value') !== 0;
	}

	/**
	 * Internal function to retrieve
	 *
	 * @todo Replace user_has and userGet to use this instead for query basis - write test to ensure
	 *       it works identically first!
	 * @param user $user
	 * @return Ambigous <zesk\Database_Query_Select, zesk\Database_Query_Select>
	 */
	private static function _value_query(User $user, $name) {
		$query = $user->application->orm_registry(__CLASS__)
			->query_select()
			->link(self::type_class, "type")
			->where(array(
			'X.user' => $user,
			'type.code' => $name,
		));
		return $query;
	}

	public static function user_get(User $user, $pref_name, $default = null) {
		if (empty($pref_name)) {
			throw new Exception_Parameter("{method}({user}, {name}, ...) Name is empty", array(
				"method" => __METHOD__,
				"user" => $user->id(),
				"name" => $pref_name,
			));
		}
		$pref_name = strtolower($pref_name);
		$row = $user->application->orm_registry(__CLASS__)
			->query_select()
			->link(self::type_class, array(
			"alias" => "T",
		))
			->what("value", "X.value")
			->what("id", "X.id")
			->where('T.code', $pref_name)
			->where('X.user', $user)
			->one();
		if (!is_array($row)) {
			return $default;
		}
		$value = $row['value'];
		$vlen = strlen($value);
		if ($vlen >= 4 && $value[1] === ':' && $value[$vlen - 1] === ';') {
			return PHP::unserialize($value);
		} else {
			$user->application->logger->warning("Invalid preference string for {user}: {key}={value} ({vlen} chars) - deleting ({debug})", array(
				"user" => $user,
				"key" => $pref_name,
				"value" => $value,
				"debug" => PHP::dump($row),
				"vlen" => $vlen,
			));
			$user->application->orm_registry(Preference::class)
				->query_delete()
				->where("id", $row['id'])
				->execute();
		}
		return $default;
	}

	public static function user_get_single(User $user, $name, $default) {
		$result = $user->application->orm_registry(__CLASS__)
			->query_select()
			->join(self::type_class, array(
			'alias' => 'T',
		))
			->where('T.code', $name)
			->where('X.user', $user)
			->what('value', 'X.value')
			->one('value');
		if ($result === null) {
			return $default;
		}
		return @unserialize($result);
	}

	public static function user_set(User $user, $name, $value = null) {
		if (empty($name)) {
			throw new Exception_Parameter("{method}({user}, {name}, ...) Name is empty", array(
				"method" => __METHOD__,
				"user" => $user->id(),
				"name" => $name,
			));
		}
		$app = $user->application;
		if (is_array($name)) {
			foreach ($name as $k => $v) {
				$type = Preference_Type::register_name($app, $k);
				if (!$type) {
					continue;
				}
				self::_register($user, $type, $v);
			}
			return true;
		}
		$type = Preference_Type::register_name($app, $name);
		if (!$type) {
			return false;
		}
		return self::_register($user, $type, $value);
	}

	private static function _register(User $user, $type, $value) {
		$app = $user->application;
		$dbvalue = serialize($value);
		$result = $app->orm_registry(__CLASS__)
			->query_select()
			->what('id', 'id')
			->what('value', 'value')
			->where('user', $user)
			->where('type', $type)
			->one();
		if ($result) {
			if ($result['value'] === $dbvalue) {
				return $result['id'];
			}
			$app->orm_registry(__CLASS__)
				->query_update()
				->value("value", $dbvalue)
				->where('id', $result['id'])
				->execute();
			return $result['id'];
		}
		return $app->orm_factory(__CLASS__, array(
			'type' => $type,
			'value' => $value,
			"user" => $user,
		))->store();
	}
}
