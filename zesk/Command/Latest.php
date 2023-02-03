<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage command
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk;

use Git\classes\Repository;

/**
 * @author kent
 */
class Command_Latest extends Command_Base {
	protected array $shortcuts = ['latest'];

	protected array $load_modules = [
		'Git',
	];

	public function run(): int {
		/* @var $git \zesk\Git\Module */
		$git = $this->application->gitModule();

		$vendor_path = $this->application->path('vendor/zesk');
		$zesk_home = $this->application->zeskHome();
		$vendor_zesk = dirname($zesk_home);

		if ($vendor_zesk !== $vendor_path) {
			$this->error("$zesk_home is not in the vendor directory. Stopping.");
			return 1;
		}
		if (!is_dir("$vendor_zesk/zesk")) {
			$this->error("$vendor_zesk/zesk must be a directory. Stopping.");
			return 1;
		}

		chdir($zesk_home);
		$repos = $git->determineRepository($zesk_home);

		if (count($repos) === 0) {
			$old = "$vendor_zesk/zesk.COMPOSER";
			$new = "$vendor_zesk/zesk.GIT";
			$active = "$vendor_zesk/zesk";

			Directory::delete($old);
			$this->exec('git clone https://github.com/zesk/zesk {new}', [
				'new' => $new,
			]);
			if (!is_dir($new)) {
				$this->error('Unable to git clone into {new}', [
					'new' => $new,
				]);
				return 2;
			}
			rename($active, $old);
			rename($new, $active);
			$this->log('Zesk now linked to the latest');
			return 0;
		}

		foreach ($repos as $repo) {
			if (!$repo instanceof Repository) {
				continue;
			}
			/* @var $repo zesk\Git\Repository */
			$zesk_home = realpath($zesk_home);
			if (realpath($repo->path()) === $zesk_home) {
				$this->exec('git -C {0} pull origin master', $zesk_home);
			} else {
				$this->error('Found repo above {home} at {path}, ignoring', [
					'home' => $zesk_home,
					'path' => $repo->path(),
				]);
			}
		}
	}
}
