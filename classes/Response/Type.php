<?php
namespace zesk\Response;

use zesk\Hookable;
use zesk\Response;

abstract class Type extends Hookable {
	/**
	 *
	 * @var Response
	 */
	protected $parent = null;

	/**
	 *
	 * @param Response $response
	 */
	final public function __construct(Response $response) {
		$this->parent = $response;
		parent::__construct($response->application);
		$this->initialize();
	}

	/**
	 * Return true if the result is stored in a file in the file system
	 *
	 * @return boolean
	 */
	abstract protected function hasFile();

	/**
	 * Output any special headers
	 */
	abstract protected function headers();

	/**
	 *
	 */
	abstract protected function initialize();

	/**
	 *
	 * @param mixed $content
	 * @return string
	 */
	abstract public function render($content);

	/**
	 * Outputs to stdout the content
	 * @param mixed $content
	 * @return void
	 */
	abstract public function passthru($content);
}