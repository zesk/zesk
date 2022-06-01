<?php declare(strict_types=1);
/**
 *
 */
namespace zesk\Cron;

use zesk\Exception_NotFound;
use zesk\Timestamp;
use zesk\Text;

/**
 * Run zesk cron hooks
 *
 * @category Management
 * @author kent
 */
class Command_Cron extends \zesk\Command_Base {
	protected $help = 'Run zesk cron hooks';

	protected array $option_types = [
		'list' => 'boolean',
		'last' => 'boolean',
		'reset' => 'boolean',
	];

	protected array $option_help = [
		'list' => 'List cron functions which would be run',
		'last' => 'Show last run times',
		'reset' => 'Reset all cron state information, forcing all cron tasks to run next time cron is run.',
	];

	public function run() {
		try {
			/* @var $cron Module */
			$cron = $this->application->modules->object('cron');
		} catch (Exception_NotFound $e) {
			$this->error('Cron module is not enabled');
			return 1;
		}
		if ($this->optionBool('reset')) {
			$result = $cron->reset();
			$this->verbose_log($result ? 'Cron reset' : 'Cron reset failed');
			return $result ? 0 : 1;
		}
		if ($this->optionBool('list')) {
			$list_status = $cron->list_status();
			$this->render_format($list_status);
			return 0;
		}
		if ($this->optionBool('last')) {
			$result = $cron->last_run();
			$locale = $this->application->locale;
			$now = Timestamp::now();
			$rows = [];
			foreach ($result as $key => $ts) {
				if ($ts instanceof Timestamp) {
					$rows[] = [
						'key' => $key,
						'value' => $ts->isEmpty() ? null : $ts->iso8601(),
						'seconds ago' => $ts->isEmpty() ? $this->application->locale->__('Never') : $now->difference($ts, Timestamp::UNIT_SECOND),
					];
				}
			}
			echo Text::format_table($rows);
			return 0;
		}
		$result = $cron->run();
		if ($result === null) {
			$this->verbose_log('Cron is already running.');
		} else {
			$this->verbose_log('Cron run successfully: ' . implode(', ', $result));
		}
		return 0;
	}
}
