<?php
declare(strict_types=1);

namespace zesk\World;

use zesk\Command\SimpleCommand;

/**
 * Register default Country, Currency, Language, and Province data in the database.
 *
 * @category ORM Module
 * @author kent
 *
 */
class Command_Bootstrap extends SimpleCommand {
	protected array $shortcuts = ['world-bootstrap'];

	protected array $option_types = [
		'drop' => 'boolean',
		'all' => 'boolean',
		'country' => 'boolean',
		'currency' => 'boolean',
		'language' => 'boolean',
		'province' => 'boolean',
	];

	protected array $option_help = [
		'drop' => 'Truncate all tables (may cause renumbering)',
		'all' => 'Bootstrap all codes',
		'country' => 'Bootstrap country codes',
		'currency' => 'Bootstrap currency codes',
		'language' => 'Bootstrap language codes',
		'province' => 'Bootstrap US/CA provinces',
	];

	private static array $straps = [
		'country' => Bootstrap_Country::class,
		'currency' => Bootstrap_Currency::class,
		'language' => Bootstrap_Language::class,
		'province' => Bootstrap_Province::class,
	];

	public function run(): int {
		$straps = [];
		if ($this->optionBool('all')) {
			$straps = array_keys(self::$straps);
		} else {
			foreach (self::$straps as $k => $code) {
				if ($this->optionBool($k)) {
					$straps[] = $k;
				}
			}
		}
		if (count($straps) === 0) {
			$this->error('Specify something to bootstrap');
			return 1;
		}
		if ($this->optionBool('drop')) {
			$this->verboseLog('Truncating all tables ... may cause ID renumbering.');
			$this->application->configuration->setPath('zesk\\Module_World::drop', true);
		}
		foreach ($straps as $strap) {
			$class = self::$straps[$strap];
			$this->log("Bootstrapping $strap ...");
			$object = $this->application->factory($class, $this->application, $this->options);
			$object->bootstrap($this->application);
		}
		return 0;
	}
}
