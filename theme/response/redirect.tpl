<?php
namespace zesk;

/* @var $this zesk\Template */
/* @var $zesk zesk\Kernel */
/* @var $application TimeBank */
/* @var $session Session */
/* @var $request Router */
/* @var $request Request */
/* @var $response zesk\Response_Text_HTML */
/* @var $current_user User */

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

if ($zesk->configuration->path_get('Response::redirect_show_backtrace', $this->application->development())) {
	echo HTML::div('.backtrace', $this->theme('exception/trace', array(
		'content' => debug_backtrace(false)
	)));
}
echo $this->end();
echo $this->end();
