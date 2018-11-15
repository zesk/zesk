<?php
namespace zesk\CodeKit;

use zesk\Module_JSLib;
use zesk\Template;
use zesk\Request;
use zesk\Response;
use zesk\Interface_Module_Head;

class Module extends Module_JSLib implements Interface_Module_Head {
    public function hook_head(Request $request, Response $response, Template $template) {
        if ($this->application->development()) {
            $ip = "127.0.0.1";
            $js_version = "3.0.3";
            $response->javascript_inline(map(file_get_contents(path($this->path, "etc/js/ck-$js_version.js")), array(
                "codekit_port" => $this->option_integer("codekit_port", 5758),
                "request_port" => $this->option_integer("request_port", $request->port()),
                "ip" => $ip,
                "name" => php_uname('n'),
            ), true, "{{", "}}"));
        }
        parent::hook_head($request, $response, $template);
    }
}
