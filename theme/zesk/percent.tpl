<?php
/* @var $this Template */
$decimals = $this->get1("1;decimals");
if (!$decimals) {
	$decimals = zesk::get1("percent.decimals;numeric.decimals;decimals", 0);
}
echo sprintf("%.${decimals}f", $this->get1("0;content")) . "%";
