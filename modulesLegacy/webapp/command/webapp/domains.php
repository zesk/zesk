<?php declare(strict_types=1);
namespace zesk\WebApp;

use zesk\File;

/**
 * List domains registered with Web Application module, or load them from a file.
 *
 * @category Web Application Manager
 * @author kent
 *
 */
class Command_WebApp_Domains extends \zesk\Command_Base {
	public array $option_types = [
		'file' => 'file',
		'format' => 'string',
	];

	public $option_help = [
		'file' => 'Load domains from a file, one domain per line',
	];

	public function run() {
		if ($this->option('file')) {
			$lines = File::lines($this->option('file'));
			foreach ($lines as $line) {
				$line = trim($line);
				if (substr($line, 0, 1) === '#') {
					continue;
				}
				$this->application->ormFactory(Domain::class, [
					'name' => $line,
				])->register();
			}
		}
		$result = $this->application->ormRegistry(Domain::class)
			->querySelect()
			->addWhatIterable([
				'name' => 'name',
				'active' => 'active',
			])
			->order_by([
				'name',
				'active DESC',
			])
			->toArray('name', 'active');
		return $this->renderFormat($result);
	}
}
