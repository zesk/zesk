<?php declare(strict_types=1);
/**
 *
 */
namespace zesk\Bootstrap3\DateTimePicker;

/**
 *
 * @author kent
 *
 */
class Module extends \zesk\Module_JSLib implements \zesk\Interface_Module_Head {
	protected $css_paths = [
		'/share/bootstrap-datetimepicker/css/bootstrap-datetimepicker.min.css',
	];

	protected $javascript_paths = [
		'/share/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js',
	];

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Module::initialize()
	 */
	public function initialize(): void {
		parent::initialize();
		$this->application->share_path(path($this->path, 'share'), 'bootstrap-datetimepicker-widget');
	}
}
