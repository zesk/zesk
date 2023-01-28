<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
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
	protected string $class = 'zesk\\Forgot'; // TODO fix to __NAMESPACE__ when TODO-PHP7 only

	/**
	 *
	 * @var array
	 */
	protected array $options = [
		'title' => 'Forgotten password',
	];

	/**
	 *
	 * @var Forgot
	 */
	protected Model $object;

	/**
	 *
	 * @var User
	 */
	private ?User $auth_user = null;

	/**
	 *
	 * @return \zesk\Widget[]|boolean[]
	 */
	public function hook_widgets() {
		$locale = $this->locale();

		$this->setFormName('forgot_form');

		$ww = [];

		$ww[] = $w = $this->widgetFactory($this->option('widget_login', Control_Text::class))->names('login', $locale->__($this->option('label_login', 'Login')));
		$w->setRequired(true);
		$w->default_value($this->request->get('login'));

		$ww[] = $w = $this->widgetFactory(Control_Button::class)
			->names('forgot', $this->option('label_button', $locale->__('Send me an email')))
			->addClass('btn-primary btn-block')
			->nolabel(true);

		$ww[] = $w = $this->widgetFactory(Control_Hidden::class)->names('tzid');

		return $ww;
	}

	public function submitted() {
		return $this->request->get('forgot', '') !== '';
	}

	public function validate(): bool {
		if (!parent::validate()) {
			return false;
		}
		$locale = $this->locale();
		/* @var $user User */
		$this->auth_user = $this->application->ormFactory(User::class)->login($this->object->login)->find();
		$this->auth_user = $this->callHookArguments('find_user', [
			$this->auth_user,
		], $this->auth_user);
		if ($this->optionBool('not_found_error', true) && !$this->auth_user) {
			$this->error($locale->__('Control_Forgot:=Not able to find that user.'), 'login');
			return false;
		}
		return true;
	}

	public function submit_store() {
		assert($this->auth_user instanceof Model);

		$object = $this->object;
		$object->user = $this->auth_user;
		$object->session = $session = $this->session();
		$object->code = md5(microtime() . random_int(0, mt_getrandmax()));
		$object->store();
		$object->fetch();

		$tzid = $object->member('tzid');
		if (is_numeric($tzid)) {
			$off = abs(intval($tzid / 60));
			// INTENTIALLY REVERSED. Etc/GMT+5 is EST
			// https://en.wikipedia.org/wiki/Tz_database#Area
			$sign = $tzid < 0 ? '+' : '-';
			date_default_timezone_set("Etc/GMT$sign$off");
		} elseif (is_string($tzid)) {
			date_default_timezone_set($tzid);
		}

		$object->notify($this->request);

		$session->forgot = $object->id();

		if (!$this->preferJSON()) {
			throw new Exception_Redirect('/forgot/sent');
		}
		$this->json([
			'redirect' => '/forgot/sent',
			'status' => true,
			'message' => $this->application->locale->__('An email has been sent with instructions to reset your password.'),
		]);
		return false;
	}
}
