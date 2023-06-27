<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage World
 */

namespace zesk\World;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Throwable;
use zesk\Application;
use zesk\Doctrine\Model;
use zesk\Doctrine\Trait\AutoID;
use zesk\Exception\NotFoundException;

/**
 * @see Country
 */
#[Entity]
#[UniqueConstraint(name: 'uq', columns: ['code'])]
class Country extends Model
{
	/**
	 *
	 */
	public const CODE_COLUMN_LENGTH = 2;

	public const NAME_COLUMN_LENGTH = 64;

	/**
	 *
	 */
	use AutoID;

	#[Column(type: 'string', length: self::CODE_COLUMN_LENGTH)]
	public string $code;

	#[Column(type: 'string', length: self::NAME_COLUMN_LENGTH)]
	public string $name;

	/**
	 * @param Application $application
	 * @param string $code
	 * @param string $name
	 * @param array $options
	 */
	public function __construct(Application $application, string $code, string $name, array $options = [])
	{
		parent::__construct($application, $options);
		$this->code = substr($code, 0, self::CODE_COLUMN_LENGTH);
		$this->name = substr($name, 0, self::NAME_COLUMN_LENGTH);
	}

	/**
	 * @param Application $application
	 * @param string|int $mixed
	 * @return self
	 * @throws NotFoundException
	 */
	public static function findCountry(Application $application, string|int $mixed): self
	{
		$em = $application->entityManager();

		try {
			if (is_int($mixed)) {
				$result = $em->find(self::class, $mixed);
			} else {
				$result = $em->getRepository(self::class)->findOneBy(['code' => $mixed]);
			}
			if ($result) {
				return $result;
			}
		} catch (Throwable) {
		}

		throw new NotFoundException(self::class);
	}
}
