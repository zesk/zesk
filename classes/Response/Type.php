<?php
namespace zesk\Response;

use zesk\Response;
use zesk\Application;

/**
 * @see Response
 * @author kent
 *
 */
abstract class Type {
	/**
	 *
	 * @var \zesk\Application
	 */
	protected $application = null;

	/**
	 *
	 * @var \zesk\Response
	 */
	protected $parent = null;

	/**
	 *
	 * @param \zesk\Response $response
	 */
	final public function __construct(Response $response) {
		$this->parent = $response;
		$this->application = $response->application;
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
	 * @param unknown $content
	 * @return boolean
	 */
	public function render($content) {
		ob_start();
		$this->output($content);
		return ob_end_clean();
	}

	/**
	 * Outputs to stdout the content
	 * @param mixed $content
	 * @return void
	 */
	abstract public function output($content);

	/**
	 * Convert to JSON array
	 *
	 * @return array
	 */
	abstract public function to_json();
}
