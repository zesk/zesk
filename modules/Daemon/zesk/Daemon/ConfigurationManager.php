<?php
/**
 * @package zesk
 * @subpackage Daemon
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

declare(strict_types=1);

namespace zesk\Daemon;

use zesk\Application;

class ConfigurationManager extends Manager {
	public function __construct(Application $application) {
		parent::__construct($application);
		$this->inheritConfiguration();
	}

	public function minimumProcessCount(): int {
		return $this->optionInt('minimumProcessCount', 1);
	}
}
