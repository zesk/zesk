<?php
namespace zesk;

class Controller_PolyGlot extends Controller_Template_Login {
	
	/**
	 *
	 * @var Module_PolyGlot
	 */
	protected $module = null;
	public function initialize() {
		$this->module = $this->application->modules->object("polyglot");
		parent::initialize();
	}
	public function before() {
		if (!Command::running()) {
			$action = "zesk\\Module_PolyGlot::translate";
			if (!$this->user instanceof User || !$this->user->can($action)) {
				throw new Exception_Permission($this->user, $action);
			}
		}
		parent::before();
	}
	public function action_index() {
		if (!$this->user->can("zesk\\Module_PolyGlot::translate")) {
			throw new Exception_Permission($this->user, "Module_PolyGlot::index", null, array(
				"message" => __("Not allowed to do translations.")
			));
		}
		$this->template->content = $this->application->theme('polyglot/translate', array(
			"locale_options" => $this->module->locale_options()
		));
	}
	
	/**
	 * Load a locale for translation
	 *
	 * @param string $locale
	 */
	public function action_load($locale) {
		$__ = array(
			"lang_name" => Language::lang_name($locale),
			"locale" => $locale
		);
		if ($this->user->can("zesk\\Module_PolyGlot::load", null, array(
			"locale" => $locale
		))) {
			return $this->json(array(
				"status" => true,
				"message" => __("Loaded locale {lang_name}.", $__),
				"items" => $this->module->load_locale($locale)
			));
		}
		return $this->json(array(
			"status" => false,
			"message" => __("You do not have permission to do translations.")
		));
	}
	
	/**
	 * Update a locale on all servers with current translations
	 *
	 * @param string $locale
	 */
	public function action_update($locale) {
		$__ = array(
			"lang_name" => Language::lang_name($locale),
			"locale" => $locale
		);
		if (!$this->user->can("zesk\\Module_PolyGlot::update", null, array(
			"locale" => $locale
		))) {
			return $this->json(array(
				"status" => false,
				"message" => __("You do not have permission to update the {lang_name} locale.")
			));
		}
		if (PolyGlot_Update::register_update($locale, $this->user)) {
			return $this->json(array(
				"status" => true,
				"message" => __("Locale {lang_name} will be updated in the new few minutes.", $__)
			));
		}
		$this->json(array(
			"status" => false,
			"message" => __("Locale {lang_name} was not updated.", $__)
		));
	}
	
	/**
	 * Save a token
	 * @param unknown $locale
	 * @return Ambigous <Response, boolean>
	 */
	public function action_token($locale) {
		$__ = array(
			"lang_name" => Language::lang_name($locale),
			"locale" => $locale
		);
		if (!$this->user->can("zesk\\Module_PolyGlot::translate", null, array(
			"locale" => $locale
		))) {
			return $this->json(array(
				"status" => false,
				"message" => __("No permission to update {lang_name}.", $__)
			));
		}
		$id = $this->request->geti("id");
		$fields = array();
		foreach (to_list("original;translation;status") as $k) {
			$fields[$k] = $this->request->get($k);
		}
		$fields['translation'] = $fields['translation'];
		$fields["dialect"] = Locale::dialect($locale);
		$fields["language"] = $language = Locale::language($locale);
		if (empty($language)) {
			return $this->json(array(
				"status" => false,
				"message" => __("No language? {locale} ({language})", compact("locale", "language"))
			));
		}
		if ($id) {
			$object = $this->application->object_factory("zesk\\PolyGlot_Token", $id)->fetch();
		} else {
			$object = $this->application->object_factory("zesk\\PolyGlot_Token");
		}
		$object->set_member($fields);
		$errors = ($fields['status'] === 'delete') ? array() : $object->validate();
		if (count($errors)) {
			return $this->json(array(
				"status" => false,
				"message" => $errors
			));
		}
		$result = $object->store();
		return $this->json(array(
			"status" => $result,
			"id" => $object->id(),
			"message" => $result ? null : $object->store_errors()
		));
	}
}
