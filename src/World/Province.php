<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 * @package    zesk
 * @subpackage World
 */

namespace zesk\World;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\EntityListeners;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use zesk\Application;
use zesk\Configuration;
use zesk\Doctrine\Model;
use zesk\Doctrine\ModelListener;
use zesk\Doctrine\Trait\AutoID;
use zesk\Doctrine\Trait\CodeName;

/**
 *
 */
#[Entity]
#[EntityListeners([ModelListener::class])]
class Province extends Model
{
	use AutoID;
	use CodeName;

	#[ManyToOne]
	#[JoinColumn(name: 'country')]
	public Country $country;

	public function __construct(Application $application, Country $country, string $code, string $name, array|Configuration $options = [])
	{
		parent::__construct($application, $options);
		$this->country = $country;
		$this->code = $code;
		$this->name = $name;
	}

	/**
	 * @param Application $application
	 * @param Country $country
	 * @param string $code
	 * @param string $name
	 * @return self
	 * @throws ORMException
	 */
	public static function register(Application $application, Country $country, string $code, string $name): self
	{
		$em = $application->entityManager();
		$province = $em->getRepository(self::class)->findOneBy(['country' => $country, 'code' => $code]);
		if ($province) {
			return $province;
		}
		$province = new self($application, $country, $code, $name);
		$em->persist($province);
		$em->refresh($province);
		return $province;
	}
}
