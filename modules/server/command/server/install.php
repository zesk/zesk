<?php declare(strict_types=1);

/**
 *
 *
 * @author kent
 */
namespace zesk;

/**
 * Run server software installation and configuration, ensuring this server is up-to-date.
 * @author kent
 * @category Management
 */
class Command_Server_Install extends Command {
	protected array $load_modules = [
		'server',
	];

	/**
	 * Help string
	 *
	 * @var string
	 */
	protected $help = "Run server software installation and configuration, ensuring this server is up-to-date.";

	/**
	 * Command-line options for this command
	 *
	 * @var array
	 */
	protected array $option_types = [
		'awareness' => 'boolean',
		'verbose' => 'boolean',
		'host-path' => 'dir',
		'host-name' => 'string',
		'server-url' => 'string',
		'simulate-path' => 'dir',
		'configure-type' => 'string',
		'*' => 'string',
	];

	protected array $option_help = [
		'awareness' => 'Use Amazon EC2 Awareness to configure',
		'verbose' => 'Be verbose',
		'host-name' => 'Use this host name for configuration',
		'host-path' => 'Local directory with host configuration information (implies configure-type "files")',
		'server-url' => 'Remote URL to configure this server (implies configure-type "client")',
		'simulate-path' => 'Local directory to output root configuration steps',
		'configure-type' => 'One of "files" or "server"',
		'*' => "A list of specific features to configure",
	];

	protected array $option_defaults = [
		'awareness' => false,
		'verbose' => false,
		'simulate-path' => '/var/zesk/server/',
	];

	protected function run(): void {
		$this->configure('server-install');

		$this->verbose_log("Running {class}", [
			"class" => __CLASS__,
		]);

		try {
			/* @var $platform Server_Platform */
			$platform = Server_Platform::factory($this->application, $this);
		} catch (Exception $e) {
			$this->usage($e->getMessage());
		}
		$this->verbose_log("Configuring {class}", [
			"class" => get_class($platform),
		]);

		$features = null;
		if ($this->has_arg()) {
			$features = [];
			do {
				$feature = $this->get_arg("feature");
				if ($platform->feature_exists($feature)) {
					$features[] = $feature;
				} else {
					$this->error("Ignoring feature {feature} - unknown", compact("feature"));
				}
			} while ($this->has_arg());
			if (count($features) === 0) {
				$this->usage("No features to configure, aborting.");
			}
		}
		$platform->configure($features);
	}
}
