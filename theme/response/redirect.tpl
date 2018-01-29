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
$styles = <<<EOF
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
EOF;
$response->css_inline($styles);

$this->begin('response/html.tpl');
$this->begin('body/exception.tpl');

echo HTML::div('.redirect', HTML::tag('label', $locale('Redirect:')) . $this->content);

if ($application->configuration->path_get('Response::redirect_show_backtrace', $this->application->development())) {
	echo HTML::div('.backtrace', $this->theme('exception/trace', array(
		'content' => debug_backtrace(false)
	)));
}
echo $this->end();
echo $this->end();
