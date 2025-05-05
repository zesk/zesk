<?php
declare(strict_types=1);

namespace zesk\Doctrine;

use Doctrine\Common\EventManager as BaseEventManager;
use zesk\Application;

/**
 *
 */
class EventManager extends BaseEventManager
{
	/**
	 * @var Application
	 * @readonly
	 */
	public Application $application;

	public function __construct(Application $application)
	{
		$this->application = $application;
	}
}
