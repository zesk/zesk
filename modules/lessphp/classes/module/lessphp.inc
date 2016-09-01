<?php
class Module_LessPHP extends Module {
	public static function hooks(zesk\Hooks $hook) {
		$method = "links_preproccess";
		$hook->add("Response_HTML::$method", array(
			__CLASS__,
			$method
		));
	}
	public static function links_preprocess(array $attributes) {
		if ($attributes['rel'] !== 'stylesheet') {
			return $attributes;
		}
		$src = $attributes['src'];
		if (file::extension($src) !== "less") {
			return $attributes;
		}
		log::notice("Module_LessPHP: $src");
		return $attributes;
	}
	
	/**
	 * Create a compiler
	 *
	 * @return lessc
	 */
	public static function compiler() {
		return new Compiler_Less();
	}
}
