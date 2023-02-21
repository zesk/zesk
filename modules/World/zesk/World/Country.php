<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage World
 */

namespace zesk\World;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\SequenceGenerator;
use zesk\Application;
use zesk\Exception\NotFoundException;

/**
 * @see Country
 */
#[Entity]
class Country {
	#[Id, Column(type: 'integer'), SequenceGenerator]
	public null|int $id = null;

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
		try {
			if (is_numeric($mixed)) {
				$c = new Country($application, $mixed);
				return $c->fetch();
			} else {
				$c = new Country($application, [
				]);
				$country = $c->find();
				assert($country instanceof self);
				return $country;
			}
		} catch (Database\Exception\Connect|ORMNotFound $e) {
			throw $e;
		} catch (Exception $e) {
			throw new NotFoundException(self::class, $e->getMessage(), $e->variables(), $e);
		}
	}
}
