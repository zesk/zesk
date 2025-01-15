<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Doctrine;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\EntityManager;
use zesk\Application;

/**
 * Base class for entities to inherit from in Zesk
 *
 * Provides utilities
 */
class Model extends \zesk\Model
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

	/**
	 * @param JSONWalker $options
	 * @return array
	 */
	public function json(JSONWalker $options): array
	{
		return $options->walk($this);
	}
}
