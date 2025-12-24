<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage system
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 * Created on Tue Jul 15 12:37:21 EDT 2008
 */

namespace zesk\World;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\EntityListeners;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Throwable;
use zesk\Application;
use zesk\Configuration;
use zesk\Doctrine\Model;
use zesk\Doctrine\ModelListener;
use zesk\Doctrine\Trait\AutoID;
use zesk\Exception\NotFoundException;
use zesk\Locale\Locale;
use zesk\StringTools;

/**
 *
 * @see    Class_Language
 * @author kent
 * @property int $id
 * @property string $code
 * @property string $dialect
 * @property string $name
 */
#[Entity]
#[EntityListeners([ModelListener::class])]
#[Table(name: 'Language')]
#[UniqueConstraint(name: 'langDialect', columns: [
	'code',
	'dialect',
])]
class Language extends Model
{
	use AutoID;

	#[Column(type: 'string', length: 2, nullable: false)]
	public string $code;

	#[Column(type: 'string', length: 2, nullable: false)]
	public string $dialect;

	#[Column(type: 'string', length: 128, nullable: false)]
	public string $name;

	public function __construct(Application $application, string $code, string $dialect, string $name = '', array|Configuration $options = [])
	{
		parent::__construct($application, $options);
		$this->code = $code;
		$this->dialect = $dialect;
		$this->name = $name;
	}

	/**
	 * @throws ORMException
	 */
	public static function register(Application $application, string $code, string $dialect, string $name): self
	{
		$em = $application->entityManager();

		try {
			return self::find($application, Locale::formatLocale($code, $dialect));
		} catch (NotFoundException) {
			$item = new self($application, $code, $name, $dialect);
			$em->persist($item);
			$em->refresh($item);
			return $item;
		}
	}

	/**
	 * @return string
	 * @todo Add a test
	 */
	public function localeString(): string
	{
		return Locale::formatLocale($this->code, $this->dialect);
	}

	/**
	 * @param Application $application
	 * @param string $localeString Full locale string
	 * @return self
	 * @throws NotFoundException
	 */
	public static function find(Application $application, string $localeString): self
	{
		[
			$language,
			$dialect,
		] = StringTools::pair($localeString, '_', $localeString);

		try {
			return $application->entityManager()->getRepository(self::class)->findOneBy([
				'code' => $language,
				'dialect' => $dialect,
			]);
		} catch (Throwable $e) {
			throw new NotFoundException('{class} with {code}', [
				'class' => self::class,
				'code' => $localeString,
			], $e->getCode(), $e);
		}
	}
}
