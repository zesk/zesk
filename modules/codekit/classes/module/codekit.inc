<?php
class Module_CodeKit extends zesk\Module_JSLib implements \zesk\Interface_Module_Head {
	public function hook_head(zesk\Request $request, zesk\Response $response, zesk\Template $template) {
		if ($this->application->development()) {
			$ip = "127.0.0.1";
			$js_version = "3.0.3";
			$response->javascript_inline(map(file_get_contents(path($this->path, "etc/js/ck-$js_version.js")), array(
				"codekit_port" => $this->option_integer("codekit_port", 5758),
				"request_port" => $this->option_integer("request_port", $request->port()),
				"ip" => $ip,
				"name" => php_uname('n')
			), true, "{{", "}}"));
		}
		parent::hook_head($request, $response, $template);
	}
}
