<?php declare(strict_types=1);
/**
 * @copyright &copy; 2017 Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Controller_PolyGlot extends Controller_Authenticated {
	/**
	 *
	 * @var Module_PolyGlot
	 */
	protected $module = null;

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Controller_Authenticated::initialize()
	 */
	public function initialize(): void {
		$this->module = $this->application->modules->object("polyglot");
		parent::initialize();
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Controller_Authenticated::before()
	 */
	public function before(): void {
		if (!Command::running()) {
			$action = "zesk\\Module_PolyGlot::translate";
			if (!$this->user instanceof User || !$this->user->can($action)) {
				throw new Exception_Permission($this->user, $action);
			}
		}
		parent::before();
	}

	/**
	 *
	 * @throws Exception_Permission
	 * @return string
	 */
	public function action_index() {
		if (!$this->user->can("zesk\\Module_PolyGlot::translate")) {
			$app_locale = $this->application->locale;

			throw new Exception_Permission($this->user, "Module_PolyGlot::index", null, [
				"message" => $app_locale->__("Not allowed to do translations."),
			]);
		}
		return $this->application->theme('polyglot/translate', [
			"locale_options" => $this->module->locale_options(),
		]);
	}

	/**
	 * Load a locale for translation
	 *
	 * @param string $locale
	 */
	public function action_load($locale_string) {
		$app_locale = $this->application->locale;
		$__ = [
			"lang_name" => Language::lang_name($this->application, $locale_string, $app_locale),
			"locale" => $locale_string,
		];
		if ($this->user->can("zesk\\Module_PolyGlot::load", null, [
			"locale" => $app_locale,
		])) {
			return $this->json([
				"status" => true,
				"message" => $app_locale->__("Loaded locale {lang_name}.", $__),
				"items" => $this->module->load_locale($locale_string),
			]);
		}
		return $this->json([
			"status" => false,
			"message" => $app_locale->__("You do not have permission to do translations."),
		]);
	}

	/**
	 * Update a locale on all servers with current translations
	 *
	 * @param string $locale_string
	 */
	public function action_update($locale_string) {
		$locale = $this->application->locale;
		$locale_string = Locale::normalize($locale_string);
		$__ = [
			"lang_name" => Language::lang_name($this->application, $locale_string),
			"locale" => $locale_string,
		];
		if (!$this->user->can("zesk\\Module_PolyGlot::update", null, [
			"locale" => $locale_string,
		])) {
			return $this->json([
				"status" => false,
				"message" => $locale->__("You do not have permission to update the {lang_name} locale."),
			]);
		}
		if (PolyGlot_Update::register_update($this->application, $locale_string, $this->user)) {
			return $this->json([
				"status" => true,
				"message" => $locale->__("Locale {lang_name} will be updated in the next few minutes.", $__),
			]);
		}
		return $this->json([
			"status" => false,
			"message" => $locale->__("Locale {lang_name} was not updated.", $__),
		]);
	}

	/**
	 * Save a token
	 *
	 * @param unknown $locale
	 * @return Ambigous <Response, boolean>
	 */
	public function action_token($locale) {
		$__ = [
			"lang_name" => Language::lang_name($this->application, $locale),
			"locale" => $locale,
		];
		if (!$this->user->can("zesk\\Module_PolyGlot::translate", null, [
			"locale" => $locale,
		])) {
			return $this->json([
				"status" => false,
				"message" => $this->application->locale->__("No permission to update {lang_name}.", $__),
			]);
		}
		$id = $this->request->geti("id");
		$fields = [];
		foreach (to_list("original;translation;status") as $k) {
			$fields[$k] = $this->request->get($k);
		}
		$fields['translation'] = $fields['translation'];
		$fields["dialect"] = Locale::parse_dialect($locale);
		$fields["language"] = $language = Locale::parse_language($locale);
		if (empty($language)) {
			return $this->json([
				"status" => false,
				"message" => $this->application->locale->__("No language? {locale} ({language})", compact("locale", "language")),
			]);
		}
		if ($id) {
			$object = $this->application->orm_factory("zesk\\PolyGlot_Token", $id)->fetch();
		} else {
			$object = $this->application->orm_factory("zesk\\PolyGlot_Token");
		}
		$object->set_member($fields);
		$errors = ($fields['status'] === 'delete') ? [] : $object->validate();
		if (count($errors)) {
			return $this->json([
				"status" => false,
				"message" => $errors,
			]);
		}
		$result = $object->store();
		return $this->json([
			"status" => $result,
			"id" => $object->id(),
			"message" => $result ? null : $object->store_errors(),
		]);
	}
}
