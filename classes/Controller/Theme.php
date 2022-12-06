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
	 * @var string
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
	 * Create a new Controller_Template
	 *
	 * @param Application $app
	 * @param array $options
	 */
	protected function initialize(): void {
		parent::initialize();
		if ($this->hasOption('template')) {
			$this->application->deprecated('{class} is using option template - should not @deprecated 2017-11', [
				'class' => get_class($this),
			]);
		}
		if ($this->theme === '') {
			$this->theme = strval($this->option('theme', self::DEFAULT_THEME));
		}
		$this->auto_render = $this->optionBool('auto_render', $this->auto_render);
	}

	/**
	 * Set auto render value
	 *
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
	 * @return $this
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
		$this->autoRender(false);
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
	 * (non-PHPdoc)
	 * @see Controller::after()
	 */
	public function after(string $result = null, string $output = null): void {
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
	 * (non-PHPdoc)
	 * @see Controller::variables()
	 */
	public function variables(): array {
		return [
			'theme' => $this->theme,
		] + parent::variables() + $this->variables;
	}

	/**
	 * TODO Clean this up
	 *
	 * @param Control $control
	 * @param Model $object
	 * @param array $options
	 */
	protected function control(Control $control, Model $object = null, array $options = []) {
		$control->response($this->response);
		$content = $control->execute($object);
		$this->callHook(avalue($options, 'hook_execute', 'control_execute'), $control, $object, $options);
		$title = $control->option('title', avalue($options, 'title'));
		if ($title) {
			$this->response->setTitle($title, false); // Do not overwrite existing values
		}
		$this->response->response_data([
			'status' => $status = $control->status(),
			'message' => array_values($control->messages()),
			'error' => array_values($control->children_errors()),
		]);
		return $content;
	}
}
