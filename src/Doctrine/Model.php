<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use zesk\Application;
use zesk\Model as ModelBase;

/**
 * Base class for entities to inherit from in Zesk
 *
 * Provides utilities
 */
class Model extends ModelBase
{
	protected EntityManager $em;

	public function __construct(Application $application, array $options = [])
	{
		parent::__construct($application, $options);
		$this->em = $this->application->entityManager();
	}

	/**
	 * @return void
	 * @throws ORMException
	 */
	public function delete(): void
	{
		$this->em->remove($this);
	}
}
