<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Controller/Template.php $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Fri Apr 02 21:15:05 EDT 2010 21:15:05
 */
namespace zesk;

/**
 * 
 * @author kent
 * @todo Convert to theme
 */
abstract class Controller_Template extends Controller {
	
	/**
	 * 
	 * @var string
	 */
	const DEFAULT_TEMPLATE = 'body/default.tpl';
	/**
	 * 
	 * @var boolean
	 */
	private $auto_render = true;
	
	/**
	 * 
	 * @var string
	 */
	protected $template_pushed = false;
	
	/**
	 * @var Template
	 */
	protected $template = null;
	
	/**
	 * zesk\Template variables to pass
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
		$this->template_pushed = false;
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
		$this->call_hook("json", $mixed);
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
				$this->template = $this->call_hook('template');
				if ($this->template === null) {
					$this->template = $this->option("template", self::DEFAULT_TEMPLATE);
				}
			}
			if ($this->template !== null) {
				$this->template($this->template);
			}
			$this->_push_template();
		}
	}
	
	/**
	 * Handle setting template and handling state
	 * 
	 * @param Template $template
	 */
	private function _set_template(Template $template) {
		if ($this->template_pushed) {
			$this->template->pop();
		}
		$this->template = $template;
		if ($this->template_pushed) {
			$this->template->push();
		}
	}
	private function _push_template() {
		if ($this->template instanceof Template) {
			$this->template->push();
			$this->template_pushed = true;
		}
	}
	private function _pop_template() {
		if ($this->template instanceof Template) {
			$this->template->pop();
			$this->template_pushed = false;
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
			// TODO: zesk\Template suffix .tpl removal is deprecated
			$template = str::unsuffix($template, ".tpl") . ".tpl";
			if ($this->template instanceof Template) {
				$this->template->path($template);
			} else {
				$this->_set_template(new Template($this->application, $template, $this->variables()));
			}
		} else {
			if (!$template instanceof Template) {
				$template = new Template($this->application, $template, $this->variables());
			}
			$this->_set_template($template);
		}
		
		return $this->template;
	}
	
	/**
	 *
	 * @param Exception $e
	 */
	public function exception(\Exception $e) {
		if ($this->auto_render && $this->template instanceof Template) {
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Controller::after()
	 */
	public function after($result = null, $output = null) {
		if ($this->auto_render && $this->template) {
			$this->_pop_template();
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
		$this->call_hook(avalue($options, "hook_execute", "control_execute"), $control, $object, $options);
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
				'result-deprecated' => $status, // Deprecated
				'result' => $status, // Deprecated
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
