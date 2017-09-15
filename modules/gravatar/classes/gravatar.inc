<?php
use zesk\Object;

/**
 * 
 * @author kent
 *
 */
class Gravatar extends Object {
	const api_root = 'http://gravatar.com/avatar/f740e98279c1b9de00060d43df5465ba.png?s=99&d=404';
	const api_variable_size = 's';
	const api_variable_default = 'd';
	function lang_variable_default_values() {
		return array(
			'404' => __('Show no image'),
			'mm' => __('Mystery Man'),
			'identicon' => __('Geometric (Unique per email, Generated)'),
			'monsterid' => __('Monster (Unique per email, Generated)'),
			'wavatar' => __('Faces (Unique per email, Generated)'),
			'retro' => __('Retro (Unique per email, Generated)'),
			'blank' => __('Transparent image')
		);
	}
}
