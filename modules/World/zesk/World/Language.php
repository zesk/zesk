<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 * Created on Tue Jul 15 12:37:21 EDT 2008
 */

namespace zesk\World;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Throwable;
use zesk\Application;
use zesk\Doctrine\Model;
use zesk\Doctrine\Trait\AutoID;
use zesk\Exception\KeyNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\SemanticsException;
use zesk\StringTools;

/**
 *
 * @see Class_Language
 * @author kent
 * @property int $id
 * @property string $code
 * @property string $dialect
 * @property string $name
 */
#[Entity]
#[Table(name: 'Language')]
#[UniqueConstraint(name: 'langDialect', columns: ['code', 'dialect'])]
class Language extends Model
{
	use AutoID;

	#[Column(type: 'string', length: 2, nullable: false)]
	public string $code;

	#[Column(type: 'string', length: 2, nullable: false)]
	public string $dialect;

	#[Column(type: 'string', length: 128, nullable: false)]
	public string $name;

	public function locale_string(): string
	{
		if (!$this->dialect) {
			return strtolower($this->code);
		}
		return strtolower($this->code) . '_' . strtoupper($this->dialect);
	}

	public static function find(Application $application, string $code): self
	{
		[$language, $dialect] = StringTools::pair($code, '_', $code, '');

		try {
			$item = $application->entityManager()->getRepository(self::class)->findOneBy([
				'code' => $language, 'dialect' => $dialect,
			]);
			return $item;
		} catch (Throwable) {
		}

		throw new NotFoundException('{class} with {code}', ['class' => self::class, 'code' => $code]);
	}
}
