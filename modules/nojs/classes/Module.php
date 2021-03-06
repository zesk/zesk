<?php
namespace zesk\NoJS;

use zesk\Request;
use zesk\Response;
use zesk\HTML;
use zesk\Template;

class Module extends \zesk\Module {
	public function hook_html(Request $request, Response $response, Template $template) {
		$response->html_attributes(HTML::add_class($response->html_attributes(), "no-js"));
	}

	public function hook_head(Request $request, Response $response, Template $template) {
		return HTML::tag('script', "(function(x){x.className=x.className.replace(/\\bno-js\\b/,'js')}(document.documentElement));");
	}
}
