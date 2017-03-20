<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/View/Errors.php $
 * @package zesk
 * @subpackage view
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class View_Errors extends View {
	/**
	 * 
	 * @param array $errors
	 * @param unknown $name
	 * @param string $prefix
	 * @return string
	 */
	public static function one_error(array $errors, $name, $prefix = "<br />") {
		$error_string = avalue($errors, $name);
		if (!$error_string) {
			return "";
		}
		return $prefix . HTML::tag('span', '.error', $error_string);
	}
	
	/**
	 * 
	 * @param array $errors
	 * @return mixed|NULL|string
	 */
	public static function html(array $errors) {
		$model = new Model();
		$model->errors = $errors;
		return app()->widget_factory('View_Errors', array(
			'column' => 'errors'
		))->execute($model);
	}
}

