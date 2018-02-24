<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/View/Currency.php $
 * @package zesk
 * @subpackage default
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2006, Market Acumen, Inc.
 */
namespace zesk;

class View_Currency extends View {
	function render() {
		$v = $this->value();
		if (empty($v) || abs($v) < $this->option_double('zero_epsilon', 0.000001)) {
			$result = $this->empty_string();
			if ($this->option_bool("empty_string_no_wrap")) {
				return $result;
			}
		} else {
			$ndig = $this->option('currency_fraction_digits', 2);
			$symbol = $this->first_option("currency_symbol;currency", '$');
			$dec_point = $this->option("decimal_point", __('Currency::decimal_point:=.'));
			$thou_sep = $this->option("thousands_separator", __('Currency::thousands_separator:=.'));
			$result = $symbol . number_format($v, $ndig, $dec_point, $thou_sep);
		}
		return $result;
	}
	public static function format(Application $application, $amount, $currency = null) {
		$view = new View_Currency($application, array(
			"column" => "amount"
		));
		$model = new Model($application);
		$model->amount = $amount;
		if ($currency) {
			$view->set_option("currency", $currency);
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
