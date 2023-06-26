<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Controller;

use zesk\Controller;
use zesk\Exception;
use zesk\Request;
use zesk\Response;

/**
 *
 * @author kent
 */
abstract class ThemeController extends Controller {
	/**
	 * Default content type for Response generated upon instantiation.
	 *
	 * @var string
	 */
	protected string $default_content_type = Response::CONTENT_TYPE_HTML;

	/**
	 *
	 * @var string
	 */
	protected string $theme = '';

	/**
	 *
	 * @var string
	 */
	public const DEFAULT_THEME = 'body/default';

	/**
	 *
	 * @var boolean
	 */
	private bool $auto_render = true;

	/**
	 * zesk\Template variables to pass
	 *
	 * @var array
	 */
	protected array $variables = [];

	/**
	 *
	 */
	public const OPTION_THEME = 'theme';

	/**
	 *
	 */
	public const OPTION_AUTO_RENDER = 'auto_render';

	/**
	 * Create a new Controller_Template
	 *
	 */
	protected function initialize(): void {
		parent::initialize();
		if ($this->theme === '') {
			$this->theme = $this->optionString(self::OPTION_THEME, self::DEFAULT_THEME);
		}
		$this->auto_render = $this->optionBool(self::OPTION_AUTO_RENDER, $this->auto_render);
	}

	/**
	 * Set auto render value
	 *
	 * @param bool $set
	 * @return self
	 */
	public function setAutoRender(bool $set): self {
		$this->auto_render = $set;
		return $this;
	}

	/**
	 * Get/set auto render value
	 *
	 * @return bool
	 */
	public function autoRender(): bool {
		return $this->auto_render;
	}

	/**
	 *
	 * @param Response $response
	 * @param mixed|null $mixed
	 * @return Response
	 */
	public function json(Response $response, mixed $mixed = null): Response {
		$this->setAutoRender(false);
		return $response->json()->setData($mixed);
	}

	/**
	 * @param Response $response
	 * @param int $code
	 * @param string $message
	 * @return Response
	 */
	public function error(Response $response, int $code, string $message = ''): Response {
		$this->setAutoRender(false);
		return parent::error($response, $code, $message);
	}

	/**
	 *
	 * @param Exception $e
	 */
	public function exception(\Exception $e): void {
		if ($this->autoRender() && $this->theme) {
			$this->application->error('Exception in controller {this-class} {class}: {message}', [
				'this-class' => get_class($this),
			] + Exception::exceptionVariables($e));
		}
	}

	/**
	 * @see Controller::after()
	 */
	public function after(Request $request, Response $response): Response {
		if ($this->autoRender()) {
			if (!$response->isHTML()) {
				return $response;
			}
			$content = $response->content();
			if ($request->preferJSON()) {
				$response->json()->setData(['content' => $content] + $response->toJSON());
				return $response;
			}
			if ($this->theme) {
				$content = $this->theme($this->theme, [
					'content' => $content,
				] + $this->variables(), $this->optionArray('theme_options'));
			}
			return $response->setContent($content);
		}
		return $response;
	}

	/**
	 *
	 */
	public function variables(): array {
		return [
			'theme' => $this->theme,
		] + parent::variables() + $this->variables;
	}
}
