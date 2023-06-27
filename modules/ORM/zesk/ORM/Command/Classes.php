<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\ORM\Command;

use zesk\ArrayTools;
use zesk\Command\SimpleCommand;
use zesk\Text;

/**
 * Output all classes required in the application
 *
 * @category Database
 */
class Classes extends SimpleCommand
{
	protected array $shortcuts = ['classes'];

	protected array $option_types = [
		'format' => 'string',
		'database' => 'boolean',
		'table' => 'boolean',
	];

	protected array $option_help = [
		'database' => 'show database related to object',
		'table' => 'show table related to object',
	];

	public function run(): int
	{
		$application = $this->application;
		$classes = $application->ormModule()->allClasses();
		$objects_by_class = [];
		$is_table = false;
		$rows = [];
		$filters = [
			'class',
		];
		if ($this->optionBool('database')) {
			$filters[] = 'database';
		}
		if ($this->optionBool('table')) {
			$filters[] = 'table';
		}
		foreach ($classes as $data) {
			$result = ArrayTools::filter($data, $filters);
			if (array_key_exists('database', $result)) {
				$result['database'] ??= '-default-';
			}
			$rows[] = $result;
		}
		$format = $this->option('format');
		if ($format === 'text' || empty($format)) {
			echo Text::formatTable($rows);
		} else {
			$this->renderFormat($rows);
		}
		return 0;
	}
}
