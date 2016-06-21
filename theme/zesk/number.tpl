<?php
if (false) {
	/* @var $this Template */
	
	$application = $this->application;
	/* @var $application Application */
	
	$request = $this->request;
	/* @var $request Request */
	
	$response = $this->response;
	/* @var $response Response_HTML */
}

/**
 * @see Currency
 * @see Currency::format
 */
$precision = $this->get("precision", zesk::geti("precision", 0));
$decimal_point = $this->get('decimal_point', zesk::get('decimal_point', __('Number::decimal_point:=.')));
$thousands_separator = $this->get('thousands_separator', zesk::get('thousands_separator', __('Number::thousands_separator:=.')));

echo number_format($this->content, $precision, $decimal_point, $thousands_separator);
