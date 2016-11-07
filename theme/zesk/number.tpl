<?php
if (false) {
	/* @var $this zesk\Template */
	
	$zesk = $this->zesk;
	/* @var $zesk zesk\Kernel */
	
	$application = $this->application;
	/* @var $application TimeBank */
	
	$router = $this->router;
	/* @var $request Router */
	
	$request = $this->request;
	/* @var $request Request */
	
	$response = $this->response;
	/* @var $response zesk\Response_Text_HTML */
}

$configuration = $zesk->configuration;
/**
 * @see Currency
 * @see Currency::format
 */
$precision = isset($this->precision) ? $this->precision : $configuration->path_get("Number::precision", 0);
$decimal_point = isset($this->decimal_point) ? $this->decimal_point : $configuration->path_get('Number::decimal_point', __('Number::decimal_point:=.'));
$thousands_separator = isset($this->thousands_separator) ? $this->thousands_separator : $configuration->path_get('Number::thousands_separator', __('Number::thousands_separator:=.'));

echo number_format($this->content, $precision, $decimal_point, $thousands_separator);
