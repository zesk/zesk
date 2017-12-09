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
 */
abstract class Controller_Theme extends Controller {
	
	/**
	 * @deprecated 2017-11 Is here so shows up in child classes
	 * @var unknown
	 */
	protected $template = null;
	
	/**
	 * 
	 * @var string
	 */
	protected $theme = null;
	/**
	 * 
	 * @var string
	 */
	const DEFAULT_THEME = 'body/default';
	/**
	 * 
	 * @var boolean
	 */
	private $auto_render = true;
	
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
	public function __construct(Application $app, array $options = array()) {
		parent::__construct($app, $options);
		if ($this->has_option("template")) {
			zesk()->deprecated("{class} is using option template - should not @deprecated 2017-11", array(
				"class" => get_class($this)
			));
		}
		if ($this->theme === null) {
			$this->theme = $this->option('theme');
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
			if ($set === false && $this->theme) {
				$this->theme = null;
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
			if ($this->route->option("json")) {
				$this->response->content_type(Response::content_type_json);
			}
			if ($this->theme === null) {
				$this->theme = $this->call_hook('theme');
				if ($this->theme === null) {
					$this->theme = $this->option("theme", self::DEFAULT_THEME);
				}
			}
		}
	}
	
	/**
	 *
	 * @param Exception $e
	 */
	public function exception(\Exception $e) {
		if ($this->auto_render && $this->theme) {
			$this->application->logger->error("Exception in controller {this-class} {class}: {message}", array(
				"this-class" => get_class($this)
			) + Exception::exception_variables($e));
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Controller::after()
	 */
	public function after($result = null, $output = null) {
		if ($this->auto_render && $this->theme) {
			if ($this->response->json()) {
				$this->auto_render(false);
			} else {
				$content = null;
				if (is_string($result)) {
					$content = $result;
				} else if (is_string($output) && !empty($output)) {
					$content = $output;
				}
				$this->response->content = $this->theme($this->theme, array(
					"content" => $content
				) + $this->variables(), $this->option_array("theme_options"));
			}
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Controller::variables()
	 */
	public function variables() {
		return array(
			'theme' => $this->theme
		) + parent::variables() + $this->variables;
	}
	
	/**
	 * TODO Clean this up
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
			return null;
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
			return null;
		} else if ($this->response->content_type === "text/html") {
			return $content;
		} else {
			$this->auto_render(false);
			return $content;
		}
	}
}
