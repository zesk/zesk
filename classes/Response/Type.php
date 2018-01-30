<?php
namespace zesk\Response;

use zesk\Hookable;
use zesk\Response;

/**
 * @see Response
 * @author kent
 *
 */
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
}