<?php
/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2016, Market Acumen, Inc.
 * Created on Tue Jul 15 16:31:01 EDT 2008
 */
namespace zesk;

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
	protected $class = "zesk\\Forgot"; // TODO fix to __NAMESPACE__ when TODO-PHP7 only

	/**
	 * Header theme
	 *
	 * @var string
	 */
	protected $theme_prefix = null; //"zesk/control/forgot/prefix";

	/**
	 * Header theme
	 *
	 * @var string
	 */
	protected $theme_header = null; //"zesk/control/forgot/header";

	/**
	 * Footer theme
	 *
	 * @var string
	 */
	protected $theme_footer = null; //"zesk/control/forgot/footer";

	/**
	 * Suffix theme
	 *
	 * @var string
	 */
	protected $theme_suffix = null; //"zesk/control/forgot/suffix";

	/**
	 *
	 * @var array
	 */
	protected $options = array(
		'title' => 'Forgotten password',
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
	 * @return \zesk\Widget[]|boolean[]
	 */
	public function hook_widgets() {
		$locale = $this->locale();

		$this->form_name("forgot_form");

		$ww = array();

		$ww[] = $w = $this->widget_factory($this->option("widget_login", Control_Text::class))->names('login', $locale->__($this->option("label_login", "Login")));
		$w->required(true);
		$w->default_value($this->request->get('login'));

		$ww[] = $w = $this->widget_factory(Control_Button::class)
			->names('forgot', $this->option("label_button", $locale->__("Send me an email")))
			->add_class('btn-primary btn-block')
			->nolabel(true);

		$ww[] = $w = $this->widget_factory(Control_Hidden::class)->names("tzid");

		return $ww;
	}

	public function submitted() {
		return $this->request->get("forgot", "") !== "";
	}

	public function validate() {
		if (!parent::validate()) {
			return false;
		}
		$locale = $this->locale();
		/* @var $user User */
		$this->auth_user = $this->application->orm_factory(User::class)->login($this->object->login)->find();
		$this->auth_user = $this->call_hook_arguments("find_user", array(
			$this->auth_user,
		), $this->auth_user);
		if ($this->option_bool('not_found_error', true) && !$this->auth_user) {
			$this->error($locale->__("Control_Forgot:=Not able to find that user."), 'login');
			return false;
		}
		return true;
	}

	public function submit_store() {
		assert($this->auth_user instanceof Model);

		$object = $this->object;
		$object->user = $this->auth_user;
		$object->session = $session = $this->session();
		$object->code = md5(microtime() . mt_rand(0, mt_getrandmax()));
		$object->store();
		$object->fetch();

		$tzid = $object->member("tzid");
		if (is_numeric($tzid)) {
			$off = abs(intval($tzid / 60));
			// INTENTIALLY REVERSED. Etc/GMT+5 is EST
			// https://en.wikipedia.org/wiki/Tz_database#Area
			$sign = $tzid < 0 ? "+" : "-";
			date_default_timezone_set("Etc/GMT$sign$off");
		} elseif (is_string($tzid)) {
			date_default_timezone_set($tzid);
		}

		$object->notify($this->request);

		$session->forgot = $object->id();

		if (!$this->prefer_json()) {
			throw new Exception_Redirect('/forgot/sent');
		}
		$this->json(array(
			"redirect" => "/forgot/sent",
			"status" => true,
			"message" => $this->application->locale->__("An email has been sent with instructions to reset your password."),
		));
		return false;
	}
}
