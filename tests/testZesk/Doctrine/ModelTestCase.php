<?php
declare(strict_types=1);
/**
 * Tests for Doctrine Entities (Models)
 *
 * @package    zesk
 * @subpackage Doctrine
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk\Doctrine;

use Doctrine\Persistence\ObjectRepository;
use Throwable;
use zesk\Doctrine\Model;
use zesk\Exception\ConfigurationException;
use zesk\Exception\NotFoundException;
use zesk\Exception\SemanticsException;
use zesk\PHP;

class ModelTestCase extends DatabaseTestBaseCase
{
	protected array $entities = [];

	/**
	 * @return void
	 * @throws Throwable
	 * @throws ConfigurationException
	 * @throws NotFoundException
	 * @throws SemanticsException
	 */
	public function initialize(): void
	{
		parent::initialize();

		try {
			$this->schemaSynchronize($this->entities);
		} catch (Throwable $e) {
			PHP::log('Schema synchronization failed: ' . $this::class . ' ' . $e->getMessage());

			throw $e;
		}
	}

	public function truncateModelTable(string $entityName): void
	{
		$this->em->getConnection()->executeQuery("TRUNCATE $entityName");
		$this->em->flush($entityName);
		$this->assertRowCount(0, $entityName);
	}

	/**
	 * @param string $entityName
	 * @return void
	 */
	public function deleteAllEntitiesIteratively(string $entityName): void
	{
		$resultSet = $this->getRepository($entityName)->findAll();
		foreach ($resultSet as $entityObject) {
			$this->em->remove($entityObject);
		}
		$this->em->flush($entityName);
		$this->assertRowCount(0, $entityName);
	}

	/**
	 *
	 */
	final public function assertModel(Model $object): void
	{
		$entityName = $object::class;

		$this->em->getRepository($entityName);

		$this->schemaSynchronize($entityName);

		$this->assertRowCount(0, $entityName);
	}
}
