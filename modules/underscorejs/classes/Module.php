<?php
/**
 *
 */
namespace zesk\UnderscoreJS;

/**
 *
 * @author kent
 *
 */
class Module extends \zesk\Module_JSLib {
	protected $javascript_paths = array(
		'/share/underscorejs/underscore.js' => array(
			"weight" => "first",
		),
	);
}
