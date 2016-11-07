<?php
/**
 * @version $Id: preference.inc 4122 2016-11-07 20:43:38Z kent $
 * @package zesk
 * @subpackage user
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2013, Market Acumen, Inc.
 */
use zesk\User;
use zesk\Object;
use zesk\Database_Query_Select;
use zesk\Exception_Parameter;
use zesk\Application;
/**
 * @see Class_Preference
 *
 * @author kent
 */
class Preference extends Object {
	/**
	 * Store - check requirements
	 *
	 * @see Object::store()
	 * @return boolean
	 */
	function store() {
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
	static function user_has(User $user, $name) {
		if (!$user->authenticated()) {
			return false;
		}
		$pref = self::user_get($user, $name, null);
		return !empty($pref);
	}
	static function user_has_one(User $user, $name) {
		return $user->application->query_select(__CLASS__)
			->join('Preference_Type', array(
			'alias' => 'T'
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
	private static function _value_query(user $user, $name) {
		$query = $user->application->query_select(__CLASS__)->link("Preference_Type", "type")->where(array(
			'X.user' => $user,
			'Preference_Type.code' => $name
		));
		return $query;
	}
	static function user_get(user $user, $pref_name, $default = null) {
		if (!$user->authenticated()) {
			return $default;
		}
		$pref_name = strtolower($pref_name);
		$prefs = $user->_preference_cache;
		if (!is_array($prefs)) {
			$prefs = $user->application->query_select("Preference")
				->link("Preference_Type", array(
				"alias" => "T"
			))
				->what("name", "T.code")
				->what("value", "X.value")
				->what("id", "X.id")
				->where('X.user', $user)
				->to_array("name");
			foreach ($prefs as $k => $row) {
				$id = $row['id'];
				$value = $row['value'];
				$vlen = strlen($value);
				if ($vlen >= 4 && $value[1] === ':' && $value[$vlen - 1] === ';') {
					$prefs[strtolower($k)] = @unserialize($value);
				} else {
					log::warning("Invalid preference string for {user}: {key}={value} - deleting", array(
						"user" => $user,
						"key" => $k,
						"value" => $value
					));
					$user->application->query_delete("Preference")->where("id", $id)->execute();
				}
			}
			$user->_preference_cache = $prefs;
		}
		return avalue($prefs, $pref_name, $default);
	}
	static function user_get_single(User $user, $name, $default) {
		$result = $user->application->query_select(__CLASS__)
			->join('Preference_Type', array(
			'alias' => 'T'
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
	static function user_set(User $user, $name, $value = null) {
		if (!$user->authenticated()) {
			return false;
		}
		if (is_array($name)) {
			foreach ($name as $k => $v) {
				$type = Preference_Type::register_name($k);
				if (!$type)
					continue;
				Preference::_register($user, $type, $v);
			}
			return true;
		}
		$type = Preference_Type::register_name($name);
		if (!$type) {
			return false;
		}
		return Preference::_register($user, $type, $value);
	}
	private static function _register(User $user, $type, $value) {
		$app = $user->application;
		$dbvalue = serialize($value);
		$result = $app->query_select(__CLASS__)
			->what('id', 'id')
			->what('value', 'value')
			->where('user', $user)
			->where('type', $type)
			->one();
		if ($result) {
			if ($result['value'] === $dbvalue) {
				return $result['id'];
			}
			$app->query_update(__CLASS__)
				->value("value", $dbvalue)
				->where('id', $result['id'])
				->execute();
			return $result['id'];
		}
		return $app->object_factory(__CLASS__, array(
			'type' => $type,
			'value' => $value,
			"user" => $user
		))->store();
	}
}
