<?php

/* @var $response Response_HTML */
$response = $this->response;

/* @var $this Template */

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

echo html::div('.redirect', html::tag('label', __('Redirect:')) . $this->content);

if (zesk::get('response/redirect.tpl::debug_backtrace', $this->application->development())) {
	echo html::div('.backtrace', $this->theme('exception/trace', array('content' => debug_backtrace(false))));
}
echo $this->end();
echo $this->end();
