<?php
declare(strict_types=1);
/**
 * Tests for Doctrine Entities (Models)
 *
 * @package zesk
 * @subpackage Doctrine
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Doctrine;

use Doctrine\Persistence\ObjectRepository;

class ModelTestCase extends DatabaseTestCase {

	protected array $entities = [];

	public function initialize(): void {
		parent::initialize();
		$this->schemaSynchronize($this->entities);
	}

	public function truncateModelTable(string $entityName): void {
		$this->em->getConnection()->executeQuery("TRUNCATE $entityName");
		$this->em->flush($entityName);
		$this->assertRowCount(0, $entityName);
	}

	/**
	 * @param string $entityName
	 * @return void
	 */
	public function deleteAllEntitiesIteratively(string $entityName): void {
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
	final public function assertModel(Model $object): void {
		$entityName = $object::class;
		$this->assertEquals($entityName, $object::class); // PHP, assume

		$e = $this->em->getRepository($entityName);
		$this->assertInstanceOf(ObjectRepository::class, $e);

		$this->schemaSynchronize($entityName);

		$this->assertRowCount(0, $entityName);
	}
}
