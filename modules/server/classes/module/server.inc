<?php
class Module_Server extends Module {

	protected $classes = array(
		'Server',
		'Server_Data',
		'Lock'
	);

	public static function sites() {
		return array(
			'remote' => array(
				'document_root' => path(Module::path('server'), 'site'),
				'description' => __("Server remote control. Secure, authenticated system management."),
				'class' => 'Application_Server'
			)
		);
	}
}

