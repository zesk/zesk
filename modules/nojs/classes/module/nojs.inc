<?php

class Module_NoJS extends zesk\Module {
	public function hook_html(zesk\Request $request, zesk\Response_Text_HTML $response, zesk\Template $template) {
		$response->head_prefix(HTML::tag('script', "(function(x){x.className=x.className.replace(/\\bno-js\\b/,'js')}(document.documentElement));"));
		$response->html_attributes(HTML::add_class($response->html_attributes(), "no-js"));
	}
}
