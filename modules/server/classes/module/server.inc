<?php

/**
 * 
 * @author kent
 *
 */
class Module_Server extends Module {
	protected $classes = array(
		'Server',
		'Server_Data',
		'Lock'
	);
	public static function sites() {
		$server_path = app()->modules->path("server");
		return array(
			'remote' => array(
				'document_root' => path($server_path, 'site'),
				'description' => __("Server remote control. Secure, authenticated system management."),
				'class' => 'Application_Server'
			)
		);
	}
}

