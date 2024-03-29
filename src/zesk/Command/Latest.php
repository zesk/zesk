<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Command
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Command;

use zesk\Repository\Base as Repository;

/**
 * @author kent
 */
class Latest extends SimpleCommand {
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
			return self::EXIT_CODE_ENVIRONMENT;
		}
		if (!is_dir("$vendor_zesk/zesk")) {
			$this->error("$vendor_zesk/zesk must be a directory. Stopping.");
			return self::EXIT_CODE_ENVIRONMENT;
		}

		chdir($zesk_home);

		try {
			$repo = $git->factory($zesk_home);
			/* @var $repo Repository */
			$zesk_home = realpath($zesk_home);
			if (realpath($repo->path()) === $zesk_home) {
				$this->exec('git -C {0} pull origin master', $zesk_home);
			} else {
				$this->error('Found repo above {home} at {path}, ignoring', [
					'home' => $zesk_home,
					'path' => $repo->path(),
				]);
			}
			return 0;
		} catch (NotFoundException) {
		}
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
}
