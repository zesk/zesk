<?php
declare(strict_types=1);
/**
 * @package    modules
 * @subpackage world
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\World;

use Doctrine\ORM\Exception\ORMException;
use zesk\Application;
use zesk\ArrayTools;
use zesk\Hookable;
use zesk\Interface\Initializer;
use zesk\Types;

abstract class Bootstrap extends Hookable implements Initializer
{
	protected string $includeOption = '';

	/**
	 *
	 * @var array
	 */
	private array $includeCodes = [];

	/**
	 * @param Application $application
	 * @param array $options
	 */
	public function __construct(Application $application, array $options = [])
	{
		parent::__construct($application, $options);
		$this->initialize();
	}

	/**
	 * @throws ORMException
	 */
	abstract public function bootstrap(): void;

	/**
	 * @param string $class
	 * @return void
	 */
	protected function deleteEverything(string $class): void
	{
		$em = $this->application->entityManager();
		$em->createQueryBuilder()->delete($class, 'p')->getQuery()->execute();
	}

	/**
	 * @return void
	 * @throws
	 */
	public function initialize(): void
	{
		$this->setConfiguration(Module::class);
		if ($this->includeOption) {
			$includeCodes = $this->optionIterable($this->includeOption);
			if ($includeCodes) {
				$this->includeCodes = array_change_key_case(ArrayTools::keysFromValues(Types::toList($includeCodes), true));
			}
		}
	}

	/**
	 *
	 * @param string $code
	 * @return bool
	 */
	protected function isIncluded(string $code): bool
	{
		if (count($this->includeCodes) !== 0) {
			return $this->includeCodes[strtolower($code)] ?? false;
		}
		return true;
	}

	/**
	 * @return array
	 */
	public function __serializeChild(): array
	{
		return [];
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function __unserializeChild(array $data): void
	{
		$this->initialize();
	}
}
