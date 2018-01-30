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
	 * Output any special headers
	 */
	protected function headers() {
	}

	/**
	 * Override in subclasses to extend constructor. Make sure to call parent::initialize()!
	 */
	protected function initialize() {
	}

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
	public function output($content) {
		echo $this->render($content);
	}
}