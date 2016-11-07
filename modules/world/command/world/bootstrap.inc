<?php
class Command_World_Bootstrap extends Command_Base {
	protected $option_types = array(
		'drop' => 'boolean',
		'all' => 'boolean',
		'country' => 'boolean',
		'currency' => 'boolean',
		'language' => 'boolean',
		'province' => 'boolean'
	);
	protected $option_help = array(
		'drop' => 'Truncate all tables (may cause renumbering)',
		'all' => 'Bootstrap all codes',
		'country' => 'Bootstrap country codes',
		'currency' => 'Bootstrap currency codes',
		'language' => 'Bootstrap language codes',
		'province' => 'Bootstrap US/CA provinces'
	);
	private static $straps = array(
		'country' => 'World_Bootstrap_Country',
		'currency' => 'World_Bootstrap_Currency',
		'language' => 'World_Bootstrap_Language',
		'province' => 'World_Bootstrap_Province'
	);
	function run() {
		$straps = array();
		if ($this->option_bool('all')) {
			$straps = array_keys(self::$straps);
		} else {
			foreach (self::$straps as $k => $code) {
				if ($this->option_bool($k)) {
					$straps[] = $k;
				}
			}
		}
		if (count($straps) === 0) {
			$this->error("Specify something to bootstrap");
			return 1;
		}
		if ($this->option_bool('drop')) {
			$this->verbose_log("Truncating all tables ... may cause ID renumbering.");
			zesk::set('', true);
		}
		foreach ($straps as $strap) {
			$class = self::$straps[$strap];
			$object = Object::factory($class, $this->options);
			$this->log("Bootstrapping $strap ...");
			$object->boostrap($this->application);
		}
	}
}
