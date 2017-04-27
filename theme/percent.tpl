<?php

namespace zesk;

/* @var $this \zesk\Template */
/* @var $zesk \zesk\Kernel */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_Text_HTML */
/* @var $current_user \zesk\User */

$decimals = $this->get1("1;decimals");
if (!$decimals) {
	$decimals = $zesk->configuration->path_get_first(array(
		"zesk\Locale::percent_decimals",
		"zesk\Locale::numeric_decimals"
	), 0);
}
echo sprintf("%.${decimals}f", $this->get1("0;content")) . "%";
