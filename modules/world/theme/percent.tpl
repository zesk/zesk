<?php
$percent = $this->get1('percent;content');
$n_dec = $this->geti('decimal_precision', 0);
$percent = $percent * 100.0;
$suffix = "%";
$format = "";
if ($n_dec === null) {
	if ($percent - round($percent, 0) >= 0.06) {
		$format = "%.1f";
	} else {
		echo intval($percent) . $suffix;
	}
} else {
	$format = "%.{$n_dec}f";
}
echo sprintf($format, $percent) . $suffix;
