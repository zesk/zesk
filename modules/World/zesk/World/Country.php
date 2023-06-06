<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage World
 */

namespace zesk\World;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Throwable;
use zesk\Application;
use zesk\Doctrine\Model;
use zesk\Doctrine\Trait\AutoID;
use zesk\Exception\NotFoundException;

/**
 * @see Country
 */
#[Entity]
class Country extends Model {
	use AutoID;

	#[Column(type: 'string', length: 2)]
	public string $code;

	#[Column(type: 'string')]
	public string $name;

	/**
	 * @param Application $application
	 * @param string|int $mixed
	 * @return self
	 * @throws NotFoundException
	 */
	public static function findCountry(Application $application, string|int $mixed): self {
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
