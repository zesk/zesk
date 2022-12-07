<?php declare(strict_types=1);
namespace zesk\CodeKit;

use zesk\Module_JSLib;
use zesk\Template;
use zesk\Request;
use zesk\Response;
use zesk\Interface_Module_Head;

class Module extends Module_JSLib implements Interface_Module_Head {
	public function hook_head(Request $request, Response $response, Template $template): void {
		if ($this->application->development()) {
			$ip = '127.0.0.1';
			$js_version = '3.0.3';
			$response->inlineJavaScript(map(file_get_contents(path($this->path, "etc/js/ck-$js_version.js")), [
				'codekit_port' => $this->optionInt('codekit_port', 5758),
				'request_port' => $this->optionInt('request_port', $request->port()),
				'ip' => $ip,
				'name' => php_uname('n'),
			], true, '{{', '}}'));
		}
		parent::hook_head($request, $response, $template);
	}
}
