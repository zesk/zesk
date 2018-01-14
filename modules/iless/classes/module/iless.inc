<?php
/**
 *
 */
use zesk\File;
use zesk\Directory;
use zesk\CSS;

/**
 *
 * @author kent
 *
 */
class Module_iLess extends zesk\Module {

	/**
	 *
	 * @return Compiler_Less
	 */
	public function compiler() {
		return new Compiler_Less($this->application);
	}

	/**
	 *
	 * @param unknown $set
	 * @return void|mixed|string
	 */
	public function style_name($set = null) {
		return $set === null ? $this->set_option('style_name', $set) : $this->option('style_name', strtolower($this->application->application_class()));
	}

	/**
	 *
	 * @return string
	 */
	private function _default_css_path() {
		return $this->option('default_css_path', "/css/" . $this->style_name() . ".css");
	}

	/**
	 *
	 * @return string
	 */
	private function _css_path() {
		return $this->option('css_path', '/cache/css/' . $this->style_name() . '.css');
	}

	/**
	 *
	 * @return string
	 */
	private function _less_path() {
		return $this->option('less_path', path($this->application->document_root(), '/less/' . $this->style_name() . '.less'));
	}

	/**
	 *
	 * @return string
	 */
	private function _full_css_path() {
		return path($this->application->document_root(), $this->_css_path());
	}

	/**
	 * CSS changed, dirty compiled less file
	 */
	public function css_theme_dirty() {
		File::unlink(path($this->application->document_root(), $this->_css_path()));
	}
	public function css_theme_changed(array $variables = array()) {
		$this->_compile_css_theme($variables);
	}

	/**
	 *
	 * @param unknown $value
	 * @param array $settings
	 * @return string
	 */
	protected function hook_process_variable_color($value, array $settings) {
		$value = CSS::color_normalize($value);
		if (empty($value)) {
			$value = ltrim($settings['default'], "#");
		}
		return '#' . $value;
	}

	/**
	 *
	 * @param unknown $value
	 * @param array $settings
	 * @return \ILess\Variable
	 */
	protected function hook_process_variable_font($value, array $settings) {
		$objects = $this->application->objects;
		$fonts = explode(",", $value);
		$values = array();
		foreach ($fonts as $font) {
			$font = unquote(trim($font));
			$values[] = $objects->factory("ILess\\Node\\QuotedNode", "'$font'", $font);
		}
		$value_node = $objects->factory("ILess\Node\ValueNode", $values);
		return $objects->factory("ILess\Variable", 'site_theme_font', $value_node);
	}

	/**
	 *
	 * @param array $variables
	 */
	private function _compile_css_theme(array $variables = array()) {
		$doc_root = $this->application->document_root();
		$site_css = $this->_css_path();
		$full_path = $this->_full_css_path();
		$source = $this->_less_path();
		Directory::depend(dirname($full_path));
		$compiler = $this->compiler();

		$types = $variables + $this->call_hook_arguments("less_variables", array(), array());
		$variables = array();
		foreach ($types as $name => $settings) {
			$settings['name'] = $name;
			$type = avalue($settings, 'type', 'normal');
			$value = $this->application->configuration->path_get($name, avalue($settings, 'default', null));
			$variables[$name] = $this->call_hook_arguments("process_variables_$type", array(
				$value,
				$settings
			), $value);
		}
		$this->application->logger->debug("LESS Vars: {vars}", array(
			"vars" => _dump($variables)
		));
		$compiler->variables($variables);
		$compiler->compile_file($source, $full_path);
		$this->application->logger->notice("Write css theme {path}", array(
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
}
