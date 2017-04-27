<?php

/**
 * 
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Module_jQuery_Unveil extends Module_JSLib {
	protected $javascript_paths = array(
		"/share/jquery-unveil/jquery.unveil.js"
	);
	
	/**
	 * jQuery ready code
	 *
	 * @var array
	 */
	protected $jquery_ready = array(
		"\$(\"img\").unveil();",
		"zesk.add_hook('document::ready', function (context) {\n\t\$(\"img\", context).unveil();\n});"
	);
	
	/**
	 * Register global hooks for this module
	 */
	public function initialize() {
		$this->application->hooks->add(HTML::tag_attributes_alter_hook_name("img"), array(
			$this,
			"img_alter"
		));
		parent::initialize();
	}
	
	public function img_alter(array $attributes, $content) {
		if (isset($this->options['disabled']) && $this->options['disabled']) {
			return null;
		}
		if (array_key_exists('data-src', $attributes)) {
			return null;
		}
		if (!array_key_exists('src', $attributes)) {
			return null;
		}
		$class = avalue($attributes, 'class', '');
		if (Lists::contains($class, 'veil', ' ')) {
			return null;
		}
		$src = $attributes['src'];
		$attributes['data-src'] = $src;
		$attributes['src'] = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7";
		return $attributes;
	}
}
