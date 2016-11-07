<?php
if (false) {
	/* @var $this zesk\Template */
	
	$zesk = $this->zesk;
	/* @var $zesk zesk\Kernel */
	
	$application = $this->application;
	/* @var $application TimeBank */
}
/* @var $this zesk\Template */
$decimals = $this->get1("1;decimals");
if (!$decimals) {
	$decimals = $zesk->configuration->path_get_first(array(
		"zesk\Locale::percent_decimals",
		"zesk\Locale::numeric_decimals"
	), 0);
}
echo sprintf("%.${decimals}f", $this->get1("0;content")) . "%";
