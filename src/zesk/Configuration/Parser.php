<?php
declare(strict_types=1);

namespace zesk\Configuration;

use zesk\Configuration\Parser\CONF;
use zesk\Configuration\Parser\JSON;
use zesk\Configuration\Parser\SH;
use zesk\Exception\ParseException;
use zesk\Exception\UnimplementedException;
use zesk\Options;
use zesk\Adapter\SettingsArray;
use zesk\Exception\ClassNotFound;
use zesk\Interface\SettingsInterface;
use zesk\PHP;

abstract class Parser extends Options {
	/**
	 * @var string
	 */
	protected string $name;

	/**
	 * @var mixed
	 */
	protected mixed $content;

	/**
	 * @var SettingsInterface
	 */
	protected SettingsInterface $settings;

	/**
	 *
	 * @var ?Dependency
	 */
	protected ?Dependency $dependency = null;

	/**
	 *
	 * @var ?Loader
	 */
	protected ?Loader $loader = null;

	/**
	 *
	 * @param string $type
	 * @param string $content
	 * @param ?SettingsInterface $settings
	 * @param array $options
	 * @return self
	 * @throws ClassNotFound
	 */
	public static function factory(string $type, mixed $content, SettingsInterface $settings = null, array $options = []): self {
		$class = match (PHP::cleanFunction(strtoupper($type))) {
			'CONF' => CONF::class,
			'JSON' => JSON::class,
			'SH' => SH::class,
			default => throw new ClassNotFound(__NAMESPACE__ . '\\' . $type),
		};
		return new $class($content, $settings, $options);
	}

	/**
	 *
	 * @param string $content
	 * @param SettingsInterface|null $settings
	 * @param array $options
	 */
	final public function __construct(mixed $content = '', SettingsInterface $settings = null, array $options = []) {
		parent::__construct($options);
		if (!$settings) {
			$values = [];
			$settings = new SettingsArray($values);
		}
		$this->setSettings($settings)->setContent($content)->initialize();
	}

	/**
	 * Setter for settings
	 *
	 * @param SettingsInterface $settings
	 * @return $this
	 */
	public function setSettings(SettingsInterface $settings): self {
		$this->settings = $settings;
		return $this;
	}

	/**
	 * Getter for settings
	 *
	 * @return SettingsInterface
	 */
	public function settings(): SettingsInterface {
		return $this->settings;
	}

	/**
	 * Setter
	 *
	 * @param Dependency $dependency
	 * @return self
	 */
	public function setConfigurationDependency(Dependency $dependency): self {
		$this->dependency = $dependency;
		return $this;
	}

	/**
	 * Setter for loader
	 *
	 * @param Loader $loader
	 * @return $this
	 */
	public function setConfigurationLoader(Loader $loader): self {
		$this->loader = $loader;
		return $this;
	}

	/**
	 *
	 * @param string $set
	 * @return self
	 */
	public function setContent(mixed $set): self {
		$this->content = $set;
		return $this;
	}

	/**
	 *
	 * @return string
	 */
	public function content(): mixed {
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
	 * @throws ParseException
	 */
	abstract public function process(): void;

	/**
	 * @param string $content
	 * @param array $options
	 * @return Editor
	 * @throws UnimplementedException
	 */
	public function editor(string $content, array $options = []): Editor {
		throw new UnimplementedException(__METHOD__);
	}
}
