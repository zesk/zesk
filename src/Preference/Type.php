<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage user
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\Preference;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\EntityListeners;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Throwable;
use zesk\Application;
use zesk\Doctrine\Model;
use zesk\Doctrine\ModelListener;
use zesk\Doctrine\Trait\AutoID;
use zesk\Exception\NotFoundException;
use zesk\Preference\Class\Class_Type;
use zesk\Preference\Class\Class_Value;

/**
 * @see    Value
 * @see    Type
 * @see    Class_Value
 * @see    Class_Type
 * @author kent
 *
 */
#[Entity]
#[EntityListeners([ModelListener::class])]
#[Table(name: "PreferenceType")]
#[UniqueConstraint(name: "code", columns: ['code'])]
class Type extends Model {
	
	use AutoID;
	
	#[Column(type: "string", length: 64, nullable: false)]
	public string $code = "";
	
	#[Column(type: "string", length: 64, nullable: false)]
	public string $name = "";
	
	#[Column(type: "json", nullable: false)]
	public array $attributes = [];
	
	/**
	 * @param Application $application
	 * @param string      $code
	 * @param string      $name
	 * @param array       $attributes
	 * @param array       $options
	 */
	public function __construct(Application $application, string $code, string $name = "", array $attributes = [], array $options = [])
	{
		parent::__construct($application, $options);
		$this->code = $code;
		$this->name = $name;
		$this->attributes = $attributes;
		
	}
	
	/**
	 * Find a preference type with the given name
	 *
	 * @param Application $application
	 * @param string      $code
	 * @param string      $name
	 * @param array       $attributes
	 * @param array       $options
	 * @return self
	 * @throws NotFoundException
	 */
	public static function registerName(Application $application, string $code, string $name = '', array $attributes = [], array $options = []): self
	{
		try {
			$result = new self($application, $code, $name, $attributes, $options);
			assert($result instanceof self);
			return $result;
		} catch (Throwable $e) {
			throw new NotFoundException('Can not register {code} ({name})', [
				'code' => $code,
				'name' => $name,
			], $e->getCode(), $e);
		}
	}
	
	/**
	 * Find a preference type with the given name
	 *
	 * @param Application $application
	 * @param string      $code
	 * @return self
	 * @throws NotFoundException
	 */
	public static function findName(Application $application, string $code): self
	{
		try {
			$em = $application->entityManager();
			$item = $em->getRepository(self::class)->findOneBy(['code' => $code]);
			assert($item instanceof self);
			return $item;
		} catch (Throwable $e) {
			throw new NotFoundException('Can not find {class} of {code}', [
				'code'  => $code,
				'class' => self::class,
			], $e->getCode(), $e);
		}
	}
}
