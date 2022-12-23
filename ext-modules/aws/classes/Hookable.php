<?php
declare(strict_types=1);

/**
 * Base class for inheriting options across all AWS classes
 *
 * @author kent
 */

namespace aws\classes;

use zesk\Application;

/**
 *
 * @author kent
 *
 */
class Hookable extends \zesk\Hookable {
	/**
	 *
	 * @var Application
	 */
	public $application = null;

	/**
	 *
	 * @param Application $application
	 * @param array $options
	 */
	public function __construct(Application $application, array $options = []) {
		parent::__construct($application, $options);
		$this->inheritConfiguration();
	}
}
