<?php declare(strict_types=1);

/**
 * @package zesk
 * @subpackage view
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
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
	public static function one_error(array $errors, $name, $prefix = '<br />') {
		$error_string = $errors[$name] ?? null;
		if (!$error_string) {
			return '';
		}
		return $prefix . HTML::tag('span', '.error', $error_string);
	}

	/**
	 *
	 * @param array $errors
	 * @return mixed|NULL|string
	 */
	public static function html(Application $application, Response $response, array $errors) {
		$model = new Model($application);
		$response = $application->responseFactory($application->request());
		$model->errors = $errors;
		return $application->widgetFactory(__CLASS__, [
			'column' => 'errors',
		])->response($response)->execute($model);
	}
}
