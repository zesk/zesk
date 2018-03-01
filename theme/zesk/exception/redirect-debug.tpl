<?php
namespace zesk;

/* @var $this \zesk\Template */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \zesk\User */

/* @var $this zesk\Template */
/* @var $exception zesk\Exception_Redirect */
?>
<style type="text/css">
div.redirect, div.backtrace {
	border: 2px solid black;
	padding: 20px;
	color: black;
	background-color: white;
}

div.redirect {
	
}

div.redirect label {
	float: left;
	padding-right: 10px;
}

div.redirect a {
	color: blue;
	font-weight: bold;
}
</style>
<?php

$response->html()->css_inline(HTML::extract_tag_contents("style", ob_get_clean()));

$this->begin('body/exception.tpl');

echo HTML::div('.redirect', HTML::tag('label', $locale->__('Redirect:')) . ' ' . $this->content);

if ($response->option_bool('redirect_show_backtrace', $application->development())) {
	echo HTML::div('.backtrace', $this->theme('exception/trace', array(
		'content' => $exception->getTrace()
	)));
}
echo $this->end();
