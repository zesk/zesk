<?php
namespace zesk;

class Module_NoJS extends Module {
	public function hook_html(Request $request, Response_Text_HTML $response, Template $template) {
		$response->head_prefix(HTML::tag('script', "(function(x){x.className=x.className.replace(/\\bno-js\\b/,'js')}(document.documentElement));"));
		$response->html_attributes(HTML::add_class($response->html_attributes(), "no-js"));
	}
}
