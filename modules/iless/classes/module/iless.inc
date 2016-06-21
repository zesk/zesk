<?php
class Module_iLess extends Module {
	public static function compiler() {
		return new Compiler_Less();
	}
	public function style_name($set = null) {
		return $set === null ? $this->set_option('style_name', $set) : $this->option('style_name', strtolower(zesk::application_class()));
	}
	private function _default_css_path() {
		return $this->option('default_css_path', "/css/" . $this->style_name() . ".css");
	}
	private function _css_path() {
		return $this->option('css_path', '/cache/css/' . $this->style_name() . '.css');
	}
	private function _less_path() {
		return $this->option('less_path', path(zesk::document_root(), '/less/' . $this->style_name() . '.less'));
	}
	private function _full_css_path() {
		return path(zesk::document_root(), $this->_css_path());
	}
	
	/**
	 * CSS changed, dirty compiled less file
	 */
	public function css_theme_dirty() {
		file::unlink(path(zesk::document_root(), $this->_css_path()));
	}
	public function css_theme_changed(array $variables = array()) {
		$this->_compile_css_theme($variables);
	}
	protected function hook_process_variable_color($value, array $settings) {
		$value = css::color_normalize($value);
		if (empty($value)) {
			$value = ltrim($settings['default'], "#");
		}
		return '#' . $value;
	}
	protected function hook_process_variable_font($value, array $settings) {
		$fonts = explode(",", $value);
		$values = array();
		foreach ($fonts as $font) {
			$font = unquote(trim($font));
			$values[] = new ILess\Node\QuotedNode("'$font'", $font);
		}
		return new ILess\Variable('site_theme_font', new ILess\Node\ValueNode($values));
	}
	private function _compile_css_theme(array $variables = array()) {
		$doc_root = zesk::document_root();
		$site_css = $this->_css_path();
		$full_path = $this->_full_css_path();
		$source = $this->_less_path();
		dir::depend(dirname($full_path));
		$compiler = Module_iLess::compiler();
		
		$types = $variables + $this->hook_array("less_variables", array(), array());
		$variables = array();
		foreach ($types as $name => $settings) {
			$settings['name'] = $name;
			$type = avalue($settings, 'type', 'normal');
			$value = zesk::get($name, avalue($settings, 'default', null));
			$variables[$name] = $this->hook_array("process_variables_$type", array(
				$value,
				$settings
			), $value);
		}
		log::debug("LESS Vars: {vars}", array(
			"vars" => _dump($variables)
		));
		$compiler->variables($variables);
		$compiler->compile_file($source, $full_path);
		log::notice("Write css theme {path}", array(
			"path" => $full_path
		));
	}
	
	/**
	 * Get compiled CSS file
	 *
	 * @return string
	 */
	public function css_theme(array $variables = array(), array $options = array()) {
		$this->set_option($options);
		$default_css = $this->default_css_path();
		if (!$this->okay || $this->option_bool('ignore_theme')) {
			return $default_css;
		}
		$full_path = $this->_full_css_path();
		if (!is_file($full_path)) {
			$this->css_theme_changed($variables);
			if (!is_file($full_path)) {
				return $default_css;
			}
		}
		return $this->_css_path();
	}
	public static function dynamic_css(array $variables = array(), array $options = array()) {
	}
}
