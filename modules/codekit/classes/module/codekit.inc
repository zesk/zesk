<?php

class Module_CodeKit extends Module_JSLib implements \zesk\Interface_Module_Head {
	public function hook_head(zesk\Request $request, zesk\Response_Text_HTML $response, zesk\Template $template) {
		if ($this->application->development()) {
			$ip = "127.0.0.1";
			$response->javascript_inline(map(file_get_contents(path(module::path('codekit'), 'etc/js/ck-2.1.5.js')), array(
				"ip" => $ip,
				"name" => php_uname('n')
			)));
		}
		parent::hook_head($request, $response, $template);
	}
}
