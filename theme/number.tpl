<?php

/* @var $this \zesk\Template */
/* @var $zesk \zesk\Kernel */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_Text_HTML */
/* @var $current_user \zesk\User */
$configuration = $zesk->configuration;
/**
 * @see Currency
 * @see Currency::format
 */
$precision = isset($this->precision) ? $this->precision : $configuration->path_get("Number::precision", 0);
$decimal_point = isset($this->decimal_point) ? $this->decimal_point : $configuration->path_get('Number::decimal_point', $locale('Number::decimal_point:=.'));
$thousands_separator = isset($this->thousands_separator) ? $this->thousands_separator : $configuration->path_get('Number::thousands_separator', $locale('Number::thousands_separator:=.'));

echo number_format($this->content, $precision, $decimal_point, $thousands_separator);
