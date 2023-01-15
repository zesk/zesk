<?php
declare(strict_types=1);

/**
 * @version $Id: Preference.php 4555 2017-04-06 18:32:10Z kent $
 * @package zesk
 * @subpackage user
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk\Preference;

use Throwable;
use zesk\Database_Exception_Duplicate;
use zesk\Database_Exception_NoResults;
use zesk\Database_Exception_SQL;
use zesk\Database_Exception_Table_NotFound;
use zesk\Exception;
use zesk\Exception_Configuration;
use zesk\Exception_Key;
use zesk\Exception_Parameter;
use zesk\Exception_Semantics;
use zesk\Exception_Syntax;
use zesk\ORM\Database_Query_Select;
use zesk\ORM\Exception_ORMDuplicate;
use zesk\ORM\Exception_ORMEmpty;
use zesk\ORM\Exception_ORMNotFound;
use zesk\ORM\Exception_Store;
use zesk\ORM\ORMBase;
use zesk\ORM\User;
use zesk\PHP;

/**
 *
 * @see Class_Value
 *
 * @author kent
 */
class Value extends ORMBase {
	public const MEMBER_ID = 'id';

	public const MEMBER_USER = 'user';

	public const MEMBER_TYPE = 'type';

	public const MEMBER_VALUE = 'value';

	public const ALIAS_TYPE = 'T';

	public const ALIAS_VALUE = 'X';

	/**
	 * @var string
	 */
	protected string $typeClass = Type::class;

	/**
	 * @return string
	 */
	public function typeClass(): string {
		return $this->typeClass;
	}

	/**
	 * @return mixed
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 */
	public function value(): mixed {
		return $this->member(self::MEMBER_VALUE);
	}

	/**
	 * Store - check requirements
	 *
	 * @return self
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMDuplicate
	 * @throws Exception_Store
	 * @see ORMBase::store()
	 */
	public function store(): self {
		if ($this->memberIsEmpty(self::MEMBER_USER)) {
			throw new Exception_ORMEmpty('NULL value for user');
		}
		if ($this->memberIsEmpty(self::MEMBER_TYPE)) {
			throw new Exception_ORMEmpty('NULL value for type');
		}
		return parent::store();
	}

	/**
	 * Internal function to retrieve
	 *
	 * @param User $user
	 * @param string $name
	 * @return Database_Query_Select
	 * @throws Exception_Semantics
	 * @throws Exception_Configuration
	 */
	private static function _valueQuery(User $user, string $name): Database_Query_Select {
		$preference = $user->application->ormRegistry(self::class);
		assert($preference instanceof Value);
		return $preference->querySelect(self::ALIAS_VALUE)->link($preference->typeClass(), [
			'alias' => self::ALIAS_TYPE,
		])->appendWhere([
			self::ALIAS_VALUE . '.' . self::MEMBER_USER => $user, self::ALIAS_TYPE . '.' . Type::MEMBER_CODE => $name,
		]);
	}

	/**
	 * Does the user have a preference value?
	 *
	 * @param user $user
	 * @param string $name
	 * @return boolean
	 */
	public static function userHas(User $user, string $name): bool {
		try {
			return self::_valueQuery($user, $name)->addWhat(self::ALIAS_VALUE, 'COUNT(' . self::ALIAS_VALUE . '.' . self::MEMBER_VALUE . ')')->integer(self::ALIAS_VALUE) !== 0;
		} catch (Throwable) {
			return false;
		}
	}

	/**
	 * @param User $user
	 * @param string $name
	 * @return mixed
	 * @throws Database_Exception_SQL
	 * @throws Exception_Configuration
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Semantics
	 */
	public static function userGet(User $user, string $name): mixed {
		if (empty($name)) {
			throw new Exception_Parameter('{method}({user}, {name}, ...) Name is empty', [
				'method' => __METHOD__, 'user' => $user->id(), 'name' => $name,
			]);
		}

		try {
			$row = self::_valueQuery($user, $name)->addWhatIterable([
				self::MEMBER_ID => self::ALIAS_VALUE . '.' . self::MEMBER_ID,
				'value' => self::ALIAS_VALUE . '.' . self::MEMBER_VALUE,
			])->one();
		} catch (Database_Exception_NoResults $e) {
			throw new Exception_ORMNotFound(self::class, 'No preference {name} for user {id}', [
				'id' => $user->id(), 'name' => $name,
			], $e);
		}
		$value = $row[self::MEMBER_VALUE];
		$valueLength = strlen($value);
		if ($valueLength >= 4 && $value[1] === ':' && $value[$valueLength - 1] === ';') {
			try {
				return PHP::unserialize($value);
			} catch (Exception_Syntax $e) {
				$user->application->logger->warning('Invalid serialized PHP: {value}', ['value' => $value]);
			}
		}
		$user->application->logger->warning('Invalid preference string for {user}: {key}={value} ({valueLength} chars) - deleting ({debug})', [
			'user' => $user, 'key' => $name, 'value' => $value, 'debug' => PHP::dump($row),
			'valueLength' => $valueLength,
		]);

		try {
			$user->application->ormRegistry(Value::class)->queryDelete()->addWhere('id', $row[self::MEMBER_ID])->execute();
		} catch (Throwable $e) {
			throw new Exception_ORMNotFound(self::class, 'Failed to delete invalid {name} for user {id}', [
				'id' => $user->id(), 'name' => $name,
			], $e);
		}

		throw new Exception_ORMNotFound(self::class, 'Preference {name} not found for user {id}', [
			'id' => $user->id(), 'name' => $name,
		]);
	}

	/**
	 * @param User $user
	 * @param array $values
	 * @return array
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_ORMDuplicate
	 * @throws Exception_ORMNotFound
	 */
	public static function userSet(User $user, array $values): array {
		$app = $user->application;
		$result = [];
		foreach ($values as $name => $value) {
			$type = Type::registerName($app, $name);
			$orm_id = self::_register($user, $type, $value);
			$result[$name] = $orm_id;
		}
		return $result;
	}

	/**
	 * @param User $user
	 * @param Type $type
	 * @param mixed $value
	 * @return int
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_ORMDuplicate
	 * @throws Exception_ORMNotFound
	 */
	private static function _register(User $user, Type $type, mixed $value): int {
		$where = [
			self::MEMBER_USER => $user, self::MEMBER_TYPE => $type,
		];

		try {
			$app = $user->application;
			$serializedValue = serialize($value);
			$query = $app->ormRegistry(__CLASS__)->querySelect()->addWhat(self::MEMBER_ID)->addWhat(self::MEMBER_VALUE)->appendWhere($where);
			$result = $query->one();
			if ($result[self::MEMBER_VALUE] === $serializedValue) {
				return intval($result[self::MEMBER_ID]);
			}

			try {
				$app->ormRegistry(__CLASS__)->queryUpdate()->value(self::MEMBER_VALUE, $serializedValue)->addWhere(self::MEMBER_ID, $result[self::MEMBER_ID])->execute();
				return intval($result[self::MEMBER_ID]);
			} catch (Exception $e) {
				throw new Exception_ORMNotFound(__CLASS__, '{exceptionClass} {message} doing update', $e->variables(), $e);
			}
		} catch (Exception_Key|Database_Exception_NoResults|Database_Exception_Duplicate $error) {
			$user->application->logger->error($error);

			try {
				return $app->ormRegistry(__CLASS__)->queryInsert()->setValues($where + [
					self::MEMBER_VALUE => $serializedValue,
				])->id();
			} catch (Exception $e) {
				throw new Exception_ORMDuplicate(__CLASS__, '{exceptionClass} {message}', $e->variables(), $e);
			}
		}
	}
}
