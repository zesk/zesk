<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Cron\Command;

use zesk\Command\SimpleCommand;
use zesk\Cron\Module;
use zesk\Exception;
use zesk\Exception\NotFoundException;
use zesk\Text;
use zesk\Timestamp;

/**
 * Run zesk cron hooks
 *
 * @category Management
 * @author kent
 */
class Cron extends SimpleCommand {
	protected array $shortcuts = ['cron'];

	protected string $help = 'Run zesk cron hooks';

	protected array $option_types = [
		'list' => 'boolean', 'last' => 'boolean', 'reset' => 'boolean',
	];

	protected array $option_help = [
		'list' => 'List cron functions which would be run', 'last' => 'Show last run times',
		'reset' => 'Reset all cron state information, forcing all cron tasks to run next time cron is run.',
	];

	public function run(): int {
		try {
			$cron = $this->application->modules->object('Cron');
			assert($cron instanceof Module);
		} catch (NotFoundException) {
			$this->error('Cron module is not enabled');
			return 1;
		}
		if ($this->optionBool('reset')) {
			try {
				$cron->reset();
				$this->verboseLog('Cron reset');
				return self::EXIT_CODE_SUCCESS;
			} catch (Exception $e) {
				$this->error('Failed to reset cron');
				$this->error($e);
				return self::EXIT_CODE_ENVIRONMENT;
			}
		}
		if ($this->optionBool('list')) {
			$list_status = $cron->listStatus();
			$this->renderFormat($list_status);
			return self::EXIT_CODE_SUCCESS;
		}
		if ($this->optionBool('last')) {
			$result = $cron->lastRun();
			$locale = $this->application->locale;
			$now = Timestamp::now();
			$rows = [];
			foreach ($result as $key => $ts) {
				if ($ts instanceof Timestamp) {
					$rows[] = [
						'key' => $key, 'value' => $ts->isEmpty() ? null : $ts->iso8601(),
						'seconds ago' => $ts->isEmpty() ? $locale->__('Never') : $now->difference($ts),
					];
				}
			}
			echo Text::formatTable($rows);
			return self::EXIT_CODE_SUCCESS;
		}
		$result = $cron->run();
		if ($result === null) {
			$this->verboseLog('Cron is already running.');
		} else {
			$this->verboseLog('Cron run successfully: ' . implode(', ', $result));
		}
		return self::EXIT_CODE_SUCCESS;
	}
}
