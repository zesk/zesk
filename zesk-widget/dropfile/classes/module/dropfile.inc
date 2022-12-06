<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

use aws\classes\Module;

/**
 *
 * @author kent
 *
 */
class Module_DropFile extends Module_JSLib {
	/**
	 *
	 * @var array
	 */
	protected $css_paths = [
		'/share/dropfile/css/dropfile.css',
	];

	/**
	 *
	 * @var array
	 */
	protected $javascript_paths = [
		'/share/dropfile/js/dropfile.js',
	];

	/**
	 *
	 * @var array
	 */
	protected $javascript_settings = [];

	/**
	 *
	 * @var array
	 */
	protected $jquery_ready = [];

	/**
	 *
	 * @var array
	 */
	private $javascript_include_settings = [
		'column',
		'url',
		'max_files',
		'target',
		'accept',
		'enabled_class',
		'target_class',
		'document_target_class',
		'allowed_types',
	];

	/**
	 *
	 * @see Module::initialize()
	 */
	public function initialize(): void {
		$this->javascript_settings = $this->options_include($this->javascript_include_settings);
		$this->application->configuration->path_set([
			Controller_Image_Picker::class,
			'upload_theme_map',
			'dropfile',
		], 'zesk/control/dropfile/image/contents');
	}
}
