<?php
declare(strict_types=1);
/**
 * @author kent
 * @package zesk/modules
 * @subpackage Polyglot
 * @copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\Polyglot;

use zesk\Exception\PermissionDenied;
use zesk\Exception\SemanticsException;
use zesk\Locale\Locale;
use zesk\ORM\Controller_Authenticated;
use zesk\ORM\User;
use zesk\Redirect;
use zesk\Request;
use zesk\Response;

/**
 *
 * @author kent
 *
 */
class Controller extends Controller_Authenticated
{
	/**
	 *
	 * @var Module
	 */
	protected Module $module;

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Controller_Authenticated::initialize()
	 */
	public function initialize(): void
	{
		$module = $this->application->modules->object('Polyglot');
		assert($module instanceof Module);
		$this->module = $module;
		parent::initialize();
	}

	/**
	 *
	 * @see Controller_Authenticated::before()
	 */
	public function before(Request $request, Response $response): void
	{
		parent::before($request, $response);

		try {
			/* If we are running a command, then continue all clear */
			$this->application->command();
			return;
		} catch (SemanticsException) {
		}
		$action = Module::class . '::translate';
		if (!$this->user instanceof User || !$this->user->can($action)) {
			throw new PermissionDenied($this->user, $action);
		}
	}

	/**
	 *
	 * @return string
	 *@throws PermissionDenied|Redirect
	 */
	public function action_index()
	{
		if (!$this->user->can('zesk\\Module_PolyGlot::translate')) {
			$app_locale = $this->application->locale;

			throw new PermissionDenied($this->user, 'Module_PolyGlot::index', null, [
				'message' => $app_locale->__('Not allowed to do translations.'),
			]);
		}
		return $this->application->themes->theme('polyglot/translate', [
			'localeOptions' => $this->module->localeOptions(),
		]);
	}

	/**
	 * Load a locale for translation
	 *
	 * @param string $locale
	 */
	public function action_load($locale_string)
	{
		$app_locale = $this->application->locale;
		$__ = [
			'lang_name' => Language::lang_name($this->application, $locale_string, $app_locale),
			'locale' => $locale_string,
		];
		if ($this->user->can('zesk\\Module_PolyGlot::load', null, [
			'locale' => $app_locale,
		])) {
			return $this->json([
				'status' => true,
				'message' => $app_locale->__('Loaded locale {lang_name}.', $__),
				'items' => $this->module->loadLocale($locale_string),
			]);
		}
		return $this->json([
			'status' => false,
			'message' => $app_locale->__('You do not have permission to do translations.'),
		]);
	}

	/**
	 * Update a locale on all servers with current translations
	 *
	 * @param string $locale_string
	 */
	public function action_update(string $locale_string)
	{
		$locale = $this->application->locale;
		$locale_string = Locale::normalize($locale_string);
		$__ = [
			'lang_name' => Language::lang_name($this->application, $locale_string),
			'locale' => $locale_string,
		];
		if (!$this->user->can('zesk\\Module_PolyGlot::update', null, [
			'locale' => $locale_string,
		])) {
			return $this->json([
				'status' => false,
				'message' => $locale->__('You do not have permission to update the {lang_name} locale.'),
			]);
		}
		if (Polyglot_Update::registerUpdate($this->application, $locale_string, $this->user)) {
			return $this->json([
				'status' => true,
				'message' => $locale->__('Locale {lang_name} will be updated in the next few minutes.', $__),
			]);
		}
		return $this->json([
			'status' => false,
			'message' => $locale->__('Locale {lang_name} was not updated.', $__),
		]);
	}

	/**
	 * Save a token
	 *
	 * @param unknown $locale
	 * @return Ambigous <Response, boolean>
	 */
	public function action_token(Request $request, Response $response, string $locale): Response
	{
		$__ = [
			'lang_name' => Language::lang_name($this->application, $locale),
			'locale' => $locale,
		];
		if (!$this->user->can('zesk\\Module_PolyGlot::translate', null, [
			'locale' => $locale,
		])) {
			return $this->json([
				'status' => false,
				'message' => $this->application->locale->__('No permission to update {lang_name}.', $__),
			]);
		}
		$id = $request->getInt('id');
		$fields = [];
		foreach (['original', 'translation', 'status'] as $k) {
			$fields[$k] = $request->get($k);
		}
		$fields['dialect'] = Locale::parseDialect($locale);
		$fields['language'] = $language = Locale::parseLanguage($locale);
		if (empty($language)) {
			return $this->json([
				'status' => false,
				'message' => $this->application->locale->__('No language? {locale} ({language})', compact('locale', 'language')),
			]);
		}
		if ($id) {
			$object = $this->application->ormFactory('zesk\\PolyGlot_Token', $id)->fetch();
		} else {
			$object = $this->application->ormFactory('zesk\\PolyGlot_Token');
		}
		$object->set_member($fields);
		$errors = ($fields['status'] === 'delete') ? [] : $object->validate();
		if (count($errors)) {
			return $this->json([
				'status' => false,
				'message' => $errors,
			]);
		}
		$result = $object->store();
		return $response->json()->setData([
			'status' => $result,
			'id' => $object->id(),
			'message' => $result ? null : $object->storeErrors(),
		]);
	}
}
