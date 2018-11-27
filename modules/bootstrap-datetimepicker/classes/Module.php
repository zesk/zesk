<?php
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
	protected $css_paths = array(
		'/share/bootstrap-datetimepicker/css/bootstrap-datetimepicker.min.css',
	);

	protected $javascript_paths = array(
		'/share/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js',
	);
}
