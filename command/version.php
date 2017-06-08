<?php
namespace zesk;

/**
 * Version editor allows you to modify and bump version numbers easily for releases
 * 
 * @author kent
 */
class Command_Version extends Command_Base {
	protected $option_types = array(
		'tag' => 'string',
		'major' => 'boolean',
		'minor' => 'boolean',
		'patch' => 'boolean',
	);
}