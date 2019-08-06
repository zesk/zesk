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
class Module_DropFile extends Module_JSLib {
	/**
	 *
	 * @var array
	 */
	protected $css_paths = array(
		'/share/dropfile/css/dropfile.css',
	);

	/**
	 *
	 * @var array
	 */
	protected $javascript_paths = array(
		'/share/dropfile/js/dropfile.js',
	);

	/**
	 *
	 * @var array
	 */
	protected $javascript_settings = array();

	/**
	 *
	 * @var array
	 */
	protected $jquery_ready = array();

	/**
	 *
	 * @var array
	 */
	private $javascript_include_settings = array(
		"column",
		"url",
		"max_files",
		"target",
		"accept",
		"enabled_class",
		"target_class",
		"document_target_class",
		"allowed_types",
	);

	/**
	 *
	 * @see Module::initialize()
	 */
	public function initialize() {
		$this->javascript_settings = $this->options_include($this->javascript_include_settings);
		$this->application->configuration->path_set(array(
			Controller_Image_Picker::class,
			'upload_theme_map',
			'dropfile',
		), 'zesk/control/dropfile/image/contents');
	}
}
