<?php declare(strict_types=1);

/**
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/**
 * Output all classes required in the application
 *
 * @category Database
 */
class Command_Classes extends Command_Base {
	protected array $option_types = [
		'format' => 'string',
		'database' => 'boolean',
		'table' => 'boolean',
	];

	protected array $option_help = [
		'database' => 'show database related to object',
		'table' => 'show table related to object',
	];

	public function run() {
		$application = $this->application;
		$classes = $application->orm_module()->all_classes();
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
				$result['database'] = aevalue($result, 'database', '-default-');
			}
			$rows[] = $result;
		}
		$format = $this->option('format');
		if ($format === 'text' || empty($format)) {
			echo Text::format_table($rows);
		} else {
			$this->render_format($rows);
		}
		return 0;
	}
}
