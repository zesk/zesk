<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/forgot/classes/control/forgot.inc $
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2016, Market Acumen, Inc.
 * Created on Tue Jul 15 16:31:01 EDT 2008
 */

/**
 * 
 * @author kent
 *
 */
class Control_Forgot extends Control_Edit {
	/**
	 * 
	 * @var string
	 */
	protected $class = "Forgot";
	
	/**
	 * Header theme
	 *
	 * @var string
	 */
	protected $theme_prefix = null; //"control/forgot/prefix";
	

	/**
	 * Header theme
	 *
	 * @var string
	 */
	protected $theme_header = null; //"control/forgot/header";
	

	/**
	 * Footer theme
	 *
	 * @var string
	 */
	protected $theme_footer = null; //"control/forgot/footer";
	/**
	 * Suffix theme
	 *
	 * @var string
	 */
	protected $theme_suffix = null; //"control/forgot/suffix";
	

	/**
	 * 
	 * @var array
	 */
	protected $options = array(
		'title' => 'Forgotten password'
	);
	/**
	 *
	 * @var Forgot
	 */
	protected $object = null;
	
	/**
	 *
	 * @var User
	 */
	private $auth_user = null;
	
	/**
	 *
	 * @see Control_Object::model()
	 */
	function model() {
		return new Forgot();
	}
	function hook_widgets() {
		$this->form_name("forgot_form");
		
		$ww = array();
		
		$ww[] = $w = $this->widget_factory($this->option("widget_login", 'Control_Text'))->names('login', __($this->option("label_login", "Login")));
		$w->required(true);
		$w->default_value($this->request->get('login'));
		
		$ww[] = $w = $this->widget_factory('Control_Password')->names('login_password', $this->option("label_password", __("New Password")));
		$w->set_option('encrypted_column', 'new_password');
		$w->set_option('confirm', true);
		$w->required(true);
		
		$ww[] = $w = $this->widget_factory('Control_Button')
			->names('forgot', $this->option("label_button", __("Send me an email")))
			->add_class('btn-primary btn-block')
			->nolabel(true);
		
		return $ww;
	}
	public function submitted() {
		return $this->request->get("forgot", "") !== "";
	}
	function validate() {
		if (!parent::validate()) {
			return false;
		}
		/* @var $user User */
		$this->auth_user = $this->object_factory("User")->login($this->object->login)->find();
		$this->auth_user = $this->call_hook_arguments("find_user", array(
			$this->auth_user
		), $this->auth_user);
		if ($this->option_bool('not_found_error', true) && !$this->auth_user) {
			$this->error(__("Control_Forgot:=Not able to find that user."), 'login');
			return false;
		}
		return true;
	}
	function submit() {
		if ($this->auth_user) {
			$object = $this->object;
			$object->user = $this->auth_user;
			$object->session = $session = $this->session();
			$object->code = md5(microtime() . mt_rand(0, mt_getrandmax()));
			$object->store();
			
			$object->notify($this->request);
			
			$session->forgot = $object->id();
			$this->response->redirect('/forgot/sent');
		} else {
			$this->response->redirect('/forgot/unknown');
		}
		return false;
	}
}
