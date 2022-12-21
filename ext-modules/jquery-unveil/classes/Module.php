<?php declare(strict_types=1);

/**
 *
 */
namespace zesk\jQueryUnveil;

use zesk\Lists;
use zesk\HTML;
use zesk\Module_JSLib;

/**
 *
 * @author kent
 *
 */
class Module extends Module_JSLib {
	protected $javascript_paths = [
		'/share/jquery-unveil/jquery.unveil.js',
	];

	/**
	 * jQuery ready code
	 *
	 * @var array
	 */
	protected $jquery_ready = [
		'$("img").unveil();',
		"zesk.addHook('document::ready', function (context) {\n\t\$(\"img\", context).unveil();\n});",
	];

	/**
	 * Register global hooks for this module
	 */
	public function initialize(): void {
		$this->application->hooks->add(HTML::tag_attributes_alter_hook_name('img'), [
			$this,
			'img_alter',
		]);
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
		$class = $attributes['class'] ?? '';
		if (Lists::contains($class, 'veil', ' ')) {
			return null;
		}
		$src = $attributes['src'];
		$attributes['data-src'] = $src;
		$attributes['src'] = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
		return $attributes;
	}
}
