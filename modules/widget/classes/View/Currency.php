<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage default
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2006, Market Acumen, Inc.
 */

namespace zesk;

class View_Currency extends View {
	public function render() {
		$v = $this->value();
		if (is_numeric($v)) {
			$v = floatval($v);
			if (abs($v) < $this->optionFloat('zero_epsilon', 0.000001)) {
				return $this->empty_string();
			}
		} else {
			return $this->empty_string();
		}
		$ll = $this->application->locale;
		$num_digits = $this->option('currency_fraction_digits', 2);
		$symbol = $this->firstOption(["currency_symbol", "currency"], '$');
		$dec_point = $this->option("decimal_point", $ll->__('Currency::decimal_point:=.'));
		$thou_sep = $this->option("thousands_separator", $ll->__('Currency::thousands_separator:=.'));
		return $symbol . number_format($v, $num_digits, $dec_point, $thou_sep);
	}

	public static function format(Application $application, $amount, $currency = null) {
		$view = new View_Currency($application, [
			"column" => "amount",
		]);
		$model = new Model($application);
		$model->amount = $amount;
		if ($currency) {
			$view->setOption("currency", $currency);
		}
		$request = $application->request() ?? Request::factory($application, "http://test/");
		$view->response($application->response_factory($request));
		$view->request($request);
		return $view->execute($model);
	}
}

/**
 *
 * @deprecated
 *
 */
function format_currency($amount, $currency = null) {
	return View_Currency::format($amount, $currency);
}

/**
 *
 * @deprecated
 *
 */
function currency_format($amount, $currency = null) {
	return View_Currency::format($amount, $currency);
}
