<?php

class Module_CodeKit extends Module_JSLib implements Module_Interface_Head {
	public function hook_head(Request $request, Response_HTML $response, Template $template) {
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
