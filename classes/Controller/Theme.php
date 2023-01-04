<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 * Created on Fri Apr 02 21:15:05 EDT 2010 21:15:05
 */
namespace zesk;

/**
 *
 * @author kent
 */
abstract class Controller_Theme extends Controller {
	/**
	 * Default content type for Response generated upon instantiation.
	 *
	 * @var ?string
	 */
	protected ?string $default_content_type = Response::CONTENT_TYPE_HTML;

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
	 * @param mixed|null $mixed
	 * @return self
	 */
	public function json(mixed $mixed = null): self {
		$this->setAutoRender(false);
		return parent::json($mixed);
	}

	/**
	 * @param int $code
	 * @param string $message
	 * @return Controller_Theme
	 */
	public function error(int $code, string $message = ''): self {
		$this->setAutoRender(false);
		return parent::error($code, $message);
	}

	/**
	 *
	 * @param Exception $e
	 */
	public function exception(\Exception $e): void {
		if ($this->auto_render && $this->theme) {
			$this->application->logger->error('Exception in controller {this-class} {class}: {message}', [
				'this-class' => get_class($this),
			] + Exception::exceptionVariables($e));
		}
	}

	/**
	 * @throws Exception_Semantics
	 * @throws Exception_Redirect
	 * @see Controller::after()
	 */
	public function after(string|array|Response|null $result, string $output = ''): void {
		if ($this->auto_render) {
			if (!$this->response->isHTML()) {
				return;
			}
			$content = null;
			if (is_string($result)) {
				$content = $result;
			} elseif (is_string($output) && !empty($output)) {
				$content = $output;
			}
			if ($this->request->preferJSON()) {
				$this->json([
					'content' => $content,
				] + $this->response->toJSON());
			} else {
				$this->response->content = $this->theme ? $this->theme($this->theme, [
					'content' => $content,
				] + $this->variables(), $this->optionArray('theme_options')) : $content;
			}
		}
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
