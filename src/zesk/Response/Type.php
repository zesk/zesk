<?php
declare(strict_types=1);
namespace zesk\Response;

use zesk\Response;
use zesk\Application;

/**
 * @see Response
 * @author kent
 *
 */
abstract class Type
{
	/**
	 * @var Application
	 */
	protected Application $application;

	/**
	 * @var Response
	 */
	protected Response $parent;

	/**
	 *
	 * @param Response $response
	 */
	final public function __construct(Response $response)
	{
		$this->parent = $response;
		$this->application = $response->application;
		$this->initialize();
	}

	/**
	 * Output any special headers
	 */
	protected function headers(): void
	{
	}

	/**
	 * Override in subclasses to extend constructor. Make sure to call parent::initialize()!
	 */
	protected function initialize(): void
	{
	}

	/**
	 *
	 * @param string $content
	 * @return string
	 */
	public function render(string $content): string
	{
		ob_start();
		$this->output($content);
		return ob_get_clean();
	}

	/**
	 * Outputs to stdout the content
	 * @param mixed $content
	 * @return void
	 */
	abstract public function output(string $content): void;

	/**
	 * Convert to JSON array
	 *
	 * @return mixed
	 */
	abstract public function toJSON(): mixed;
}
