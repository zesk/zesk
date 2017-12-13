<?php

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
	protected $model_classes = array(
		'zesk\\Server',
		'zesk\\Server_Data',
		'zesk\\Lock'
	);
	public function sites(Application $application) {
		return array(
			'remote' => array(
				'document_root' => path($this->path, 'site'),
				'description' => __("Server remote control. Secure, authenticated system management."),
				'class' => 'Application_Server'
			)
		);
	}
}

