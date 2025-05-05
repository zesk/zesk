<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use zesk\Application;
use zesk\Kernel;
use zesk\Model as ModelBase;

/**
 * Base class for entities to inherit from in Zesk
 *
 * Provides utilities
 */
class Model extends ModelBase {
	
	protected string $emName = "";
	
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
	
	/**
	 * @return void
	 * @throws ORMException
	 */
	public function delete(): void
	{
		$this->entityManager()->remove($this);
	}
	
	public function postLoad(): void
	{
		$this->application = Kernel::wakeupApplication();
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
	}
}
