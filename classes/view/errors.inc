<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/view/errors.inc $
 * @package zesk
 * @subpackage view
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
class View_Errors extends View {

	protected $theme = "view/errors";

	public static function one_error(array $errors, $name, $prefix = "<br />") {
		$error_string = avalue($errors, $name);
		if (!$error_string) {
			return "";
		}
		return $prefix . html::tag('span', '.error', $error_string);
	}
	
	public static function html(array $errors) {
		$model = new Model();
		$model->errors = $errors;
		return widget::factory('View_Errors', array('column' => 'errors'))->execute($model);
	}
}

