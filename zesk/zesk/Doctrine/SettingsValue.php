<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Doctrine;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\OptimisticLockException;
use zesk\Application;
use zesk\Exception\NotFoundException;

/**
 *
 * @author kent
 *
 */
#[Entity]
#[Table(name: 'Settings')]
class SettingsValue extends Model
{
	#[Id, Column(type: 'string', nullable: false)]
	public string $name;

	#[Column(type: 'json', nullable: false)]
	public mixed $value = null;

	public function __construct(Application $application, string $name, mixed $value = null, array $options = [])
	{
		parent::__construct($application, $options);
		$this->name = $name;
		$this->value = $value;
	}

	/**
	 * @param Application $application
	 * @param string $name
	 * @param string $entityManager
	 * @return SettingsValue
	 * @throws NotFoundException
	 */
	public static function find(Application $application, string $name, string $entityManager = ''): SettingsValue
	{
		$item = $application->entityManager($entityManager)->getRepository(self::class)->find($name);
		if ($item) {
			return $item;
		}

		throw new NotFoundException('No {class} with name {name} found', ['class' => self::class, 'name' => $name]);
	}

	/**
	 * @param Application $application
	 * @param string $name
	 * @param mixed|null $value
	 * @param string $entityManager
	 * @return SettingsValue
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public static function register(Application $application, string $name, mixed $value = null, string $entityManager = ''): SettingsValue
	{
		$em = $application->entityManager($entityManager);
		$repo = $em->getRepository(self::class);
		$item = $repo->find($name);
		if ($item) {
			if ($item->value !== $value) {
				$item->value = $value;
				$em->persist($item);
				$em->flush($item);
				return $item;
			}
			return $item;
		}
		$item = new self($application, $name, $value);
		$em->persist($item);
		$em->flush($item);
		return $item;
	}
}
