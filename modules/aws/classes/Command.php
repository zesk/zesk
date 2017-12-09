<?php

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
	public function initialize() {
		parent::initialize();
		$this->awareness = $this->factory(Awareness::class, $this->application);
	}
}
