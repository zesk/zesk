<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/controller/template.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Fri Apr 02 21:15:05 EDT 2010 21:15:05
 */
abstract class Controller_Template extends Controller {

	private $auto_render = true;

	/**
	 * @var Template
	 */
	protected $template = null;

	/**
	 * Template variables to pass
	 *
	 * @var array
	 */
	protected $variables = array();

	/**
	 * Create a new Controller_Template
	 *
	 * @param Application $app
	 * @param array $options
	 */
	public function __construct(Application $app, $options = null) {
		parent::__construct($app, $options);
		if ($this->template === null) {
			// TODO: template option is deprecated
			$this->template = $this->first_option('theme;template', $this->template);
		}
		$this->auto_render = $this->option_bool('auto_render', $this->auto_render);
	}

	/**
	 * Get/set auto render value
	 *
	 * @param string $set
	 * @return Controller_Template|Ambigous <boolean, string, mixed>
	 */
	public function auto_render($set = null) {
		if (is_bool($set)) {
			if ($set === false && $this->template) {
				$this->template->pop();
				$this->template = null;
			}
			$this->auto_render = $set;
			return $this;
		}
		return $this->auto_render;
	}

	/**
	 * (non-PHPdoc)
	 * @see Controller::json()
	 */
	public function json($mixed = null) {
		$this->auto_render(false);
		return parent::json($mixed);
	}

	/**
	 * (non-PHPdoc)
	 * @see Controller::error()
	 */
	public function error($code, $message = null) {
		$this->auto_render(false);
		parent::error($code, $message);
	}

	/**
	 * (non-PHPdoc)
	 * @see Controller::before()
	 */
	public function before() {
		if ($this->auto_render) {
			if ($this->template === null) {
				$this->template = $this->hook('template');
				if ($this->template === null) {
					$this->template = zesk::class_get($this, "template", path(zesk::theme_path_default(), 'body/default.tpl'));
				}
			}
			if ($this->template !== null) {
				$this->template($this->template);
			}
		}
	}

	/**
	 *
	 * @param string $template
	 * @return Template
	 */
	public function template($template = null) {
		if ($template === null) {
			return $this->template;
		}
		if (is_string($template)) {
			// TODO: Template suffix .tpl removal is deprecated
			$template = str::unsuffix($template, ".tpl") . ".tpl";
			if ($this->template instanceof Template) {
				$this->template->path($template);
			} else {
				$this->template = new Template($this->application, $template, $this->variables());
				$this->template->push();
			}
		} else {
			if ($this->template instanceof Template) {
				$this->template->pop();
			}
			if ($template instanceof Template) {
				$this->template = $template;
			} else {
				$this->template = new Template($this->application, $template, $this->variables());
			}
			$this->template->push();
		}

		return $this->template;
	}

	/**
	 *
	 * @param Exception $e
	 */
	public function exception(Exception $e) {
		if ($this->auto_render && $this->template instanceof Template) {
			$this->template->pop();
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see Controller::after()
	 */
	public function after($result = null, $output = null) {
		if ($this->auto_render && $this->template) {
			if ($this->response->json()) {
				$this->auto_render(false);
			} else {
				if (is_string($result)) {
					$this->template->content = $result;
				} else if (is_string($output) && !empty($output)) {
					$this->template->content = $output;
				}
				$this->response->content = $this->template->render();
				if ($this->template->has('content_type')) {
					$this->response->content_type($this->template->content_type);
				}
				// TODO: Why is this here? "frame" set to null? in the router?
				$this->request->router->set_option('frame', null);
				$this->template->pop();
			}
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see Controller::variables()
	 */
	public function variables() {
		return array(
			'template' => $this->template
		) + parent::variables() + $this->variables;
	}

	/**
	 *
	 * @param Control $control
	 * @param Model $object
	 * @param array $options
	 */
	protected function control(Control $control, Model $object = null, array $options = array()) {
		$content = $control->execute($object);
		$this->hook(avalue($options, "hook_execute", "control_execute"), $control, $object, $options);
		$title = $control->option('title', avalue($options, 'title'));
		$this->response->title($title, false); // Do not overwrite existing values
		if ($this->response->json()) {
			return;
		}
		$ajax = $this->request->is_ajax();
		if ($ajax) {
			$this->json(array(
				'title' => $title,
				'status' => $status = $control->status(),
				'result-deprecated' => $status,  // Deprecated
				'result' => $status,  // Deprecated
				'content' => $content,
				'message' => array_values($control->children_errors())
			) + $this->response->to_json());
		} else if ($this->response->content_type === "text/html") {
			$this->template->content = $content;
		} else {
			$this->auto_render(false);
			$this->response->content = $content;
		}
	}
}
