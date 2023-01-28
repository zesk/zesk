<?php declare(strict_types=1);
namespace Server\classes\Server\Feature;

use Server\classes\Server\Packager\Server_Packager_APT;
use Server\classes\Server\Server_Feature;

class Server_Feature_Aptitute extends Server_Feature {
	public function configure(): void {
		if (!$this->platform->packager instanceof Server_Packager_APT) {
			$this->application->logger->warning('Server_Feature_Aptitute being configured, but packager is {class}', [
				'class' => get_class($this->platform->packager),
			]);
		}
		$this->configuration_files('apt', [
			'sources.list',
			'preferences.d/',
			'sources.list.d/',
			'trusted.gpg.d/',
			'trustdb.gpg',
			'trusted.gpg',
		], '/etc/apt/', [
			'user' => 'root',
			'mode' => 0o755,
		]);
	}
}
