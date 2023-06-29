<?php
declare(strict_types=1);


/**
 * @see Class_County
 * @author kent
 *
 */

namespace zesk\World;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use zesk\ArrayTools;
use zesk\Doctrine\Model;
use zesk\Doctrine\Trait\AutoID;
use zesk\Doctrine\Trait\Name;

/**
 * @author kent
 * @property int $id
 * @property string $name
 * @property Province $province
 */
#[Entity]
class County extends Model
{
	use AutoID;
	use Name;

	#[OneToOne(targetEntity: Province::class)]
	#[JoinColumn(name: 'province')]
	public Province $province;

	/**
	 * @return array[]
	 */
	public static function permissions(): array
	{
		return ArrayTools::map(parent::basePermissions(), [
			'object' => 'County', 'objects' => 'Counties', 'class' => self::class,
		]);
	}
}
