<?php

/**
 * @see Currency
 * @see Currency::format
 */
$precision = $this->get("precision", zesk::geti("precision", 0));
$decimal_point = $this->get('decimal_point', zesk::get('decimal_point', __('Number::decimal_point:=.')));
$thousands_separator = $this->get('thousands_separator', zesk::get('thousands_separator', __('Number::thousands_separator:=.')));

echo number_format($this->content, $precision, $decimal_point, $thousands_separator);
