<?php declare(strict_types=1);

/**
 *
 */
namespace Server\classes\Module;

use zesk\Application;
use zesk\Module;

/**
 *
 * @author kent
 *
 */
class Module_Server extends Module {
	protected array $modelClasses = [
		'zesk\\Server',
		'zesk\\ServerMeta',
		'zesk\\Lock',
	];

	public function sites(Application $application) {
		return [
			'remote' => [
				'document_root' => path($this->path, 'site'),
				'description' => __('Server remote control. Secure, authenticated system management.'),
				'class' => 'Application_Server',
			],
		];
	}
}
