<?php declare(strict_types=1);

/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Module_Server extends Module {
	protected array $model_classes = [
		'zesk\\Server',
		'zesk\\Server_Data',
		'zesk\\Lock',
	];

	public function sites(Application $application) {
		return [
			'remote' => [
				'document_root' => path($this->path, 'site'),
				'description' => __("Server remote control. Secure, authenticated system management."),
				'class' => 'Application_Server',
			],
		];
	}
}
