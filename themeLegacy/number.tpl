<?php declare(strict_types=1);

namespace zesk;

use World\Currency;

/* @var $this Template */
/* @var $locale Locale */
/* @var $application Application */
/* @var $session Session */
/* @var $router Router */
/* @var $route Route */
/* @var $request Request */
/* @var $response Response */
/* @var $current_user User */
$configuration = $application->configuration;
/**
 * @see Currency
 * @see Currency::format
 */
$precision = $this->precision ?? $configuration->getPath('Number::precision', 0);
$decimal_point = $this->decimal_point ?? $configuration->getPath('Number::decimal_point', $locale('Number::decimal_point:=.'));
$thousands_separator = $this->thousands_separator ?? $configuration->getPath('Number::thousands_separator', $locale('Number::thousands_separator:=.'));

echo number_format($this->getFloat('content'), $precision, $decimal_point, $thousands_separator);
