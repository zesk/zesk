<?php
namespace zesk;

class Module_jGrowl extends Module_JSLib {
	protected $css_paths = array(
		'/share/jgrowl/jquery.jgrowl.css',
	);

	protected $javascript_paths = array(
		'/share/jgrowl/jquery.jgrowl.js',
	);

	protected $jquery_ready = array(
		'zesk.add_hook("message", function (message, options) {
			if (is_array(message)) {
				message = html.tag("ul", html.tags("li", message));
			}
			options = $.extend(zesk.get_path("modules.jgrowl", {}), options || {}); $.jGrowl(message, options);
		});',
	);

	protected $javascript_settings_inherit = array(
		'position' => 'top-right',
		'life' => 8000,
	);

	public function ready(Response $response) {
		parent::ready($response);
	}
}
