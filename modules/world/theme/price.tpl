<?php
//function price_format($price, $n_dec = 2, $prefix = '$', $suffix = '') {
/* @var $this Template */
$n_dec = $this->geti('decimal_precision', 2);

echo $this->prefix . sprintf("%01.${n_dec}f", $this->content) . $this->suffix;

