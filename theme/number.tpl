<?php declare(strict_types=1);

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \zesk\User */
$configuration = $application->configuration;
/**
 * @see Currency
 * @see Currency::format
 */
$precision = $this->precision ?? $configuration->path_get('Number::precision', 0);
$decimal_point = $this->decimal_point ?? $configuration->path_get('Number::decimal_point', $locale('Number::decimal_point:=.'));
$thousands_separator = $this->thousands_separator ?? $configuration->path_get('Number::thousands_separator', $locale('Number::thousands_separator:=.'));

echo number_format($this->content, $precision, $decimal_point, $thousands_separator);
