<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage picker
 * @author kent
 * @copyright &copy; 2022 Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Module_Picker extends Module_JSLib {
	protected $javascript_paths = [
		'/share/picker/js/picker.js',
	];

	protected $css_paths = [
		'/share/picker/css/picker.css',
	];

	public function hook_cron(): void {
		$this->application->locale->__('No matches found for search &ldquo;{q}&rdquo;.');
	}
}
