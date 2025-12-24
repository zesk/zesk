<?php
declare(strict_types=1);
/**
 * @version    $Id: Preference.php 4555 2017-04-06 18:32:10Z kent $
 * @package    zesk
 * @subpackage user
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\Preference;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\EntityListeners;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Doctrine\ORM\OptimisticLockException;
use Throwable;
use zesk\Application;
use zesk\Doctrine\Model;
use zesk\Doctrine\ModelListener;
use zesk\Doctrine\Trait\AutoID;
use zesk\Doctrine\User;
use zesk\Exception\NotFoundException;
use zesk\Interface\Userlike;

/**
 *
 * @see    Class_Value
 *
 * @author kent
 */
#[Entity]
#[EntityListeners([ModelListener::class])]
#[Table(name: 'PreferenceValue')]
#[UniqueConstraint(name: 'userType', columns: [
	'user',
	'type',
])]
class Value extends Model
{
	public function __construct(Application $application, Type $type, mixed $value, array $options = [])
	{
		parent::__construct($application, $options);
		$this->type = $type;
		$this->value = $value;
	}

	use AutoID;

	#[OneToOne]
	#[JoinColumn(referencedColumnName: 'id')]
	public Type $type;

	#[Column(type: 'json', nullable: false)]
	public mixed $value;

	/**
	 * Internal function to retrieve
	 *
	 * @param User $user
	 * @param Type|string $name
	 * @return Value
	 * @throws NotFoundException
	 */
	private static function find(Userlike $user, Type|string $name): Value
	{
		$application = $user->application;
		$em = $application->entityManager();
		if ($name instanceof Type) {
			$type = $name;
			$name = $name->name;
		} else {
			$type = Type::findName($application, $name);
		}

		try {
			$value = $em->getRepository(self::class)->findOneBy([
				'user' => $user,
				'type' => $type,
			]);
			assert($value instanceof Value);
			return $value;
		} catch (Throwable) {
			throw new NotFoundException('No value found for {user} {name}', [
				'user' => $user,
				'name' => $name,
			]);
		}
	}

	/**
	 * Does the user have a preference value?
	 *
	 * @param user $user
	 * @param string $name
	 * @return boolean
	 */
	public static function userHas(Userlike $user, string $name): bool
	{
		try {
			return self::find($user, $name) !== null;
		} catch (Throwable) {
			return false;
		}
	}

	/**
	 * @param User $user
	 * @param string $name
	 * @return mixed
	 * @throws NotFoundException
	 */
	public static function userGet(Userlike $user, string $name): mixed
	{
		$value = self::find($user, $name);
		return $value->value;
	}

	/**
	 * @param User $user
	 * @param array $values
	 * @return array
	 * @throws NotFoundException
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public static function userSet(Userlike $user, array $values): array
	{
		$app = $user->application;
		$em = $app->entityManager();
		$result = [];
		foreach ($values as $name => $value) {
			$type = Type::registerName($app, $name);

			try {
				$valueObject = self::find($user, $type);
				$valueObject->value = $value;
			} catch (NotFoundException) {
				$valueObject = new self($app, $type, $value);
			}
			$em->persist($valueObject);
		}
		$em->flush();
		return $result;
	}
}
