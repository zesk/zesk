<?php declare(strict_types=1);
/**
 * Tests for Doctrine Entities (Models)
 *
 * @package zesk
 * @subpackage Doctrine
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectRepository;
use zesk\Application;
use zesk\Exception\Semantics;
use zesk\Kernel;
use zesk\Types;

class ModelTestCase extends DatabaseTestCase {
	/**
	 * @param ORMBase $object
	 * @return void
	 * @throws Duplicate
	 * @throws SQLException
	 * @throws TableNotFound
	 */
	final protected function prepareORMTable(ORMBase $object): void {
		$this->dropAndCreateTable($object->table(), $object->schema());
		$object->schemaChanged();
	}

	public function truncateModelTable(string $entity): void {
		$resultSet = $this->getRepository($entity)->findAll();
	}

	/**
	 * @param string $entityName
	 * @return void
	 * @throws \Doctrine\ORM\Exception\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 */
	public function deleteAllEntities(string $entityName): void {
		$resultSet = $this->getRepository($entityName)->findAll();
		foreach ($resultSet as $entityObject) {
			$this->em->remove($entityObject);
		}
		$this->em->flush();
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

		$this->doctrineModule()->schemaSynchronize($entityName);

		$this->assertRowCount(0, $entityName);
	}
}
