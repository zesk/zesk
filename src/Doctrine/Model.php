<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Throwable;
use zesk\Application;
use zesk\Configuration;
use zesk\Kernel;
use zesk\Model as ModelBase;

/**
 * Base class for entities to inherit from in Zesk
 *
 * Provides utilities
 */
class Model extends ModelBase {
	
	protected string $emName = '';
	
	/**
	 * @var EntityManager|null
	 */
	private ?EntityManager $em = null;
	
	public function __construct(Application $application, array $options = [])
	{
		parent::__construct($application, $options);
	}
	
	/**
	 * @return EntityManager
	 */
	public function entityManager(): EntityManager
	{
		return $this->em ??= $this->application->entityManager($this->emName);
	}
	
	public function hydrate(Application $application): void
	{
		$this->application = $application;
	}
	
	/**
	 * @return array
	 */
	public function members(): array
	{
		$meta = $this->entityManager()->getClassMetadata(get_class($this));
		$members = [];
		foreach ($meta->getFieldNames() as $name) {
			$members[$name] = $this->$name;
		}
		foreach ($meta->getAssociationMappings() as $name => $mapping) {
			$fieldName = $mapping->fieldName;
			if ($mapping->targetEntity) {
				$value = $this->$fieldName?->id;
			} else {
				$value = strval($mapping->$fieldName);
			}
			$members[$fieldName] = $value;
		}
		return $members;
	}
	
	/**
	 * @return void
	 * @throws ORMException
	 */
	public function delete(): void
	{
		$this->entityManager()->remove($this);
	}
	
	/**
	 * @return $this
	 * @throws ORMException
	 */
	public function store(): self
	{
		$this->entityManager()->persist($this);
		return $this;
	}
	
	public function entityRepository(): EntityRepository
	{
		return $this->entityManager()->getRepository(self::class);
	}
	
	public function reconnect(EntityManager $em): void
	{
		$evm = $em->getEventManager();
		if ($evm instanceof EventManager) {
			$this->application = $evm->application;
		} else {
			$this->application = Kernel::wakeupApplication();
		}
		$this->em = $em;
		
		try {
			$this->options = clone $this->application->configuration->path(get_class($this));
		} catch (Throwable) {
			$this->options = new Configuration();
		}
	}
}
