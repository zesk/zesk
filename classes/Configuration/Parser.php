<?php
declare(strict_types=1);

namespace zesk;

abstract class Configuration_Parser extends Options {
	/**
	 * @var string
	 */
	protected string $name;

	/**
	 * @var mixed
	 */
	protected string $content;

	/**
	 * @var Interface_Settings
	 */
	protected Interface_Settings $settings;

	/**
	 *
	 * @var ?Configuration_Dependency
	 */
	protected ?Configuration_Dependency $dependency = null;

	/**
	 *
	 * @var ?Configuration_Loader
	 */
	protected ?Configuration_Loader $loader = null;

	/**
	 *
	 * @param string $type
	 * @param string $content
	 * @param ?Interface_Settings $settings
	 * @param array $options
	 * @return self
	 * @throws Exception_Class_NotFound
	 */
	public static function factory(string $type, string $content, Interface_Settings $settings = null, array $options = []): self {
		$class = __CLASS__ . '_' . PHP::cleanFunction(strtoupper($type));
		if (!class_exists($class)) {
			throw new Exception_Class_NotFound($class);
		}
		return new $class($content, $settings, $options);
	}

	/**
	 *
	 * @param string $content
	 * @param Interface_Settings $settings
	 * @param array $options
	 */
	final public function __construct(string $content = '', Interface_Settings $settings = null, array $options = []) {
		parent::__construct($options);
		if (!$settings) {
			$values = [];
			$settings = new Adapter_Settings_Array($values);
		}
		$this->setSettings($settings)->setContent($content)->initialize();
	}

	/**
	 * Setter for settings
	 *
	 * @param Interface_Settings $settings
	 * @return $this
	 */
	public function setSettings(Interface_Settings $settings): self {
		$this->settings = $settings;
		return $this;
	}

	/**
	 * Getter for settings
	 *
	 * @return Interface_Settings
	 */
	public function settings(): Interface_Settings {
		return $this->settings;
	}

	/**
	 * Setter
	 *
	 * @param Configuration_Dependency $dependency
	 * @return self
	 */
	public function setConfigurationDependency(Configuration_Dependency $dependency): self {
		$this->dependency = $dependency;
		return $this;
	}

	/**
	 * Setter for loader
	 *
	 * @param Configuration_Loader $loader
	 * @return $this
	 */
	public function setConfigurationLoader(Configuration_Loader $loader): self {
		$this->loader = $loader;
		return $this;
	}

	/**
	 *
	 * @param string $set
	 * @return self
	 */
	public function setContent(string $set): self {
		$this->content = $set;
		return $this;
	}

	/**
	 *
	 * @return string
	 */
	public function content(): string {
		return $this->content;
	}

	/**
	 *
	 */
	abstract public function initialize(): void;

	/**
	 *
	 */
	abstract public function validate(): bool;

	/**
	 * @return Interface_Settings
	 */
	abstract public function process(): void;

	/**
	 * @return Configuration_Editor
	 */
	public function editor(string $content, array $options = []): Configuration_Editor {
		throw new Exception_Unimplemented(__METHOD__);
	}
}
