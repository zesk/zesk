<?php declare(strict_types=1);

/**
 *
 */
namespace zesk\AWS;

use zesk\AWS\EC2\Awareness;

/**
 *
 * @author kent
 *
 */
abstract class Command extends \zesk\Command_Base {
	/**
	 *
	 * @var Awareness
	 */
	protected $awareness = null;

	/**
	 * Set up awareness interface
	 *
	 * {@inheritdoc}
	 *
	 * @see \zesk\Command_Base::initialize()
	 */
	public function initialize(): void {
		parent::initialize();
		$this->awareness = $this->application->factory(Awareness::class, $this->application);
	}
}
