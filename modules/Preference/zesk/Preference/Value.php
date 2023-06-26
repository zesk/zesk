<?php
declare(strict_types=1);
/**
 * @version $Id: Preference.php 4555 2017-04-06 18:32:10Z kent $
 * @package zesk
 * @subpackage user
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Preference;

use Throwable;
use zesk\Exception;
use zesk\Exception\ClassNotFound;
use zesk\Exception\ConfigurationException;
use zesk\Exception\ParameterException;
use zesk\Exception\ParseException;
use zesk\Exception\SemanticsException;
use zesk\Exception\SyntaxException;
use zesk\Interface\Userlike;
use zesk\ORM\Database_Query_Select;
use zesk\ORM\ORMBase;
use zesk\ORM\ORMDuplicate;
use zesk\ORM\ORMEmpty;
use zesk\ORM\ORMNotFound;
use zesk\ORM\StoreException;
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
	 * @throws KeyNotFound
	 * @throws ORMNotFound
	 */
	public function value(): mixed {
		return $this->member(self::MEMBER_VALUE);
	}

	/**
	 * Store - check requirements
	 *
	 * @return self
	 * @throws Database\Exception\SQLException
	 * @throws ConfigurationException
	 * @throws KeyNotFound
	 * @throws ORMDuplicate
	 * @throws ORMEmpty
	 * @throws ORMNotFound
	 * @throws ParameterException
	 * @throws SemanticsException
	 * @throws StoreException
	 * @throws ClassNotFound
	 * @throws ParseException
	 * @throws ParseException
	 * @see ORMBase::store()
	 */
	public function store(): self {
		if ($this->memberIsEmpty(self::MEMBER_USER)) {
			throw new ORMEmpty('NULL value for user');
		}
		if ($this->memberIsEmpty(self::MEMBER_TYPE)) {
			throw new ORMEmpty('NULL value for type');
		}
		return parent::store();
	}

	/**
	 * Internal function to retrieve
	 *
	 * @param User $user
	 * @param string $name
	 * @return Database_Query_Select
	 * @throws SemanticsException
	 * @throws ConfigurationException
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
	 * @param Userlike $user
	 * @param string $name
	 * @return mixed
	 * @throws Database\Exception\SQLException
	 * @throws ConfigurationException
	 * @throws KeyNotFound
	 * @throws ORMEmpty
	 * @throws ORMNotFound
	 * @throws ParameterException
	 * @throws SemanticsException
	 */
	public static function userGet(Userlike $user, string $name): mixed {
		if (empty($name)) {
			throw new ParameterException('{method}({user}, {name}, ...) Name is empty', [
				'method' => __METHOD__, 'user' => $user->id(), 'name' => $name,
			]);
		}

		try {
			$row = self::_valueQuery($user, $name)->appendWhat([
				self::MEMBER_ID => self::ALIAS_VALUE . '.' . self::MEMBER_ID,
				'value' => self::ALIAS_VALUE . '.' . self::MEMBER_VALUE,
			])->one();
		} catch (Database\Exception\NoResults $e) {
			throw new ORMNotFound(self::class, 'No preference {name} for user {id}', [
				'id' => $user->id(), 'name' => $name,
			], $e);
		}
		$value = $row[self::MEMBER_VALUE];
		$valueLength = strlen($value);
		if ($valueLength >= 4 && $value[1] === ':' && $value[$valueLength - 1] === ';') {
			try {
				return PHP::unserialize($value);
			} catch (SyntaxException $e) {
				$user->application->warning('Invalid serialized PHP: {value}', ['value' => $value]);
			}
		}
		$user->application->warning('Invalid preference string for {user}: {key}={value} ({valueLength} chars) - deleting ({debug})', [
			'user' => $user, 'key' => $name, 'value' => $value, 'debug' => PHP::dump($row),
			'valueLength' => $valueLength,
		]);

		try {
			$user->application->ormRegistry(Value::class)->queryDelete()->addWhere('id', $row[self::MEMBER_ID])->execute();
		} catch (Throwable $e) {
			throw new ORMNotFound(self::class, 'Failed to delete invalid {name} for user {id}', [
				'id' => $user->id(), 'name' => $name,
			], $e);
		}

		throw new ORMNotFound(self::class, 'Preference {name} not found for user {id}', [
			'id' => $user->id(), 'name' => $name,
		]);
	}

	/**
	 * @param Userlike $user
	 * @param array $values
	 * @return array
	 * @throws Database\Exception\TableNotFound
	 * @throws ORMDuplicate
	 * @throws ORMNotFound
	 */
	public static function userSet(Userlike $user, array $values): array {
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
	 * @throws Database\Exception\TableNotFound
	 * @throws ORMDuplicate
	 * @throws ORMNotFound
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
				throw new ORMNotFound(__CLASS__, '{exceptionClass} {message} doing update', $e->variables(), $e);
			}
		} catch (KeyNotFound|Database\Exception\NoResults|Database\Exception\Duplicate $error) {
			$user->application->error($error);

			try {
				return $app->ormRegistry(__CLASS__)->queryInsert()->setValues($where + [
					self::MEMBER_VALUE => $serializedValue,
				])->id();
			} catch (Exception $e) {
				throw new ORMDuplicate(__CLASS__, '{exceptionClass} {message}', $e->variables(), $e);
			}
		}
	}
}
