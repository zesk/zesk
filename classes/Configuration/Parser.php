<?php
namespace zesk;

abstract class Configuration_Parser extends Options {
	
	/**
	 * @var Display name
	 */
	protected $name;
	
	/**
	 * @var string
	 */
	protected $content = null;
	
	/**
	 * @var Interface_Settings
	 */
	protected $settings = null;
	/**
	 *
	 * @var Configuration_Dependency
	 */
	protected $dependency = null;
	/**
	 *
	 * @var Configuration_Loader
	 */
	protected $loader = null;
	
	/**
	 *
	 * @param unknown $type
	 * @param unknown $content
	 * @param Interface_Settings $settings
	 * @param array $options
	 * @return Configuration_Parser
	 */
	public static function factory($type, $content, Interface_Settings $settings = null, array $options = array()) {
		$class = __CLASS__ . "_" . PHP::clean_function(strtoupper($type));
		return new $class($content, $settings, $options);
	}
	
	/**
	 *
	 * @param unknown $content
	 * @param Interface_Settings $settings
	 * @param array $options
	 */
	public final function __construct($content, Interface_Settings $settings = null, array $options = array()) {
		parent::__construct($options);
		if (!$settings) {
			$values = array();
			$settings = new Adapter_Settings_Array($values);
		}
		$this->settings($settings);
		$this->content($content);
		$this->initialize();
	}
	
	/**
	 * Getter/setter for settings
	 *
	 * @param Interface_Settings $settings
	 * @return Interface_Settings
	 */
	public function settings(Interface_Settings $settings = null) {
		if ($settings) {
			$this->settings = $settings;
			return $this;
		}
		return $this->settings;
	}
	/**
	 * Getter/setter for settings
	 *
	 * @param Interface_Settings $settings
	 * @return Interface_Settings
	 */
	public function configuration_dependency(Configuration_Dependency $dependency = null) {
		if ($dependency) {
			$this->dependency = $dependency;
			return $this;
		}
		return $this->dependency;
	}
	/**
	 * Getter/setter for settings
	 *
	 * @param Interface_Settings $settings
	 * @return Interface_Settings
	 */
	public function configuration_loader(Configuration_Loader $loader = null) {
		if ($loader) {
			$this->loader = $loader;
			return $this;
		}
		return $this->loader;
	}
	/**
	 *
	 * @param string $set
	 * @return string|\zesk\Configuration_Parser
	 */
	public function content($set = null) {
		if ($set === null) {
			return $this->content;
		}
		$this->content = $set;
		return $this;
	}
	
	/**
	 *
	 */
	abstract function initialize();
	
	/**
	 *
	 */
	abstract public function validate();
	
	/**
	 * @return Interface_Settings
	 */
	abstract public function process();
	
	/**
	 * @return Configuration_Editor
	 */
	public function editor($content = null, array $options = array()) {
		throw new Exception_Unimplemented(__METHOD__);
	}
}
