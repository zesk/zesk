<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/control/object.inc $
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 *            Created on Wed Aug 27 22:03:53 EDT 2008 22:03:53
 */

/**
 * @deprecated 2014-06-13
 * @author kent
 */
class Control_Object extends Control_Widgets {

	/**
	 * The class name of the object to create (override in child classes)
	 * @var string
	 */
	protected $class = null;

	/**
	 * Initialize the object with this options (optional), override in child classes
	 * @var mixed
	 */
	protected $object_options = null;

	/**
	 * For AJAX responses, store until render
	 *
	 * @var array
	 */
	protected $ajax_response = null;

	/**
	 *
	 * @see Widget::model()
	 */
	protected function model() {
		if (!$this->user_can($this->class . "::create")) {
			throw new Exception_Permission($this->class . "::create");
		}
		return Object::factory($this->class, null, $this->object_options);
	}

	/**
	 * Generate final submit code
	 * @param string $url
	 * @param string $message
	 * @deprecated 2014-06-13
	 */
	protected function _finish($url, $message) {
		$object = $this->object;
		$message = map($message, $object->words());
		if ($this->request->getb('ajax')) {
			$this->ajax_response = array(
				'message' => $message,
				'url' => $url,
				'result' => true,
				'result-deprecated' => true,
				'status' => true,
				'object' => $object->json()
			);
		} else if ($this->has_option("ajax_id", true)) {
			$ajax_id = $this->option("ajax_id");
			$r = "";
			if ($this->request->getb("iframe_upload")) {
				$message = addslashes($message);
				$r .= "<script type=\"text/javascript\">\n";
				$r .= "window.parent.ajax_form_finish('$ajax_id', '$message');\n";
				$r .= "</script>\n";
			} else {
				$r .= html::tag("div", ".message", $message);
				$r .= "<!-- *success* -->";
			}
			ob_end_clean_all();
			echo $r;
			exit();
		} else if ($this->option_bool("no_http_redirect")) {
			$this->set_option("http_redirect_message", $message);
		} else {
			$ref = $this->request->get("ref");
			if ($ref) {
				$this->request->set("ref", $object->apply_map($ref));
			}
			$this->response->redirect_default($url, $message);
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see Widget::load($object)
	 */
	protected function load() {
		if (!$this->name()) {
			$this->name("form_id");
			$this->column("form_id");
			$this->default = md5(microtime());
		}
		parent::load();
	}

	/**
	 * (non-PHPdoc)
	 * @see Widget::render()
	 * @deprecated 2014-06-13
	 */
	public function render() {
		zesk::deprecated();
		if ($this->request->getb('ajax') && $this->has_errors()) {
			$this->response->json(array(
				"status" => false,
				"result-deprecated" => false,
				"result" => false,
				"object" => $this->object->json(),
				"errors" => $this->errors()
			));
			return null;
		}
		if ($this->ajax_response) {
			$this->response->json($this->ajax_response);
			return null;
		}
		return parent::render();
	}
}
