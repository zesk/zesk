<?php
declare(strict_types=1);

namespace zesk\Configuration;

use zesk\Options;

abstract class Editor extends Options {
	protected string $content;

	public function __construct($content = '', array $options = []) {
		parent::__construct($options);
		$this->content = $content;
	}

	/**
	 * Setter for content
	 *
	 * @param string $set
	 * @return self
	 */
	public function setContent(string $set): self {
		$this->content = $set;
		return $this;
	}

	/**
	 * Getter for content
	 *
	 * @return string
	 */
	public function content(): string {
		return $this->content;
	}

	/**
	 *
	 * @param array $edits
	 * @return string New content of configuration file
	 */
	abstract public function edit(array $edits): string;
}
