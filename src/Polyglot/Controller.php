<?php
declare(strict_types=1);
/**
 * @author     kent
 * @package    zesk/modules
 * @subpackage Polyglot
 * @copyright  &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Polyglot;

use zesk\Doctrine\AuthenticatedController;
use zesk\Exception\AuthenticationException;
use zesk\Exception\ClassNotFound;
use zesk\Exception\KeyNotFound;
use zesk\Exception\PermissionDenied;
use zesk\Exception\Redirect;
use zesk\Exception\SemanticsException;
use zesk\Locale\Locale;
use zesk\Doctrine\User;
use zesk\Request;
use zesk\Response;
use zesk\World\Language;

/**
 *
 * @author kent
 *
 */
class Controller extends AuthenticatedController {
	
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
		$this->module = $this->application->polyglotModule();
		parent::initialize();
	}
	
	/**
	 *
	 * @see Controller_Authenticated::before()
	 */
	/**
	 * @param Request  $request
	 * @param Response $response
	 * @return void
	 * @throws PermissionDenied
	 * @throws SemanticsException
	 * @throws AuthenticationException
	 * @throws ClassNotFound
	 * @throws KeyNotFound
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
	 * @throws PermissionDenied|Redirect
	 */
	public function action_index(): string
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
			'locale'    => $locale_string,
		];
		if ($this->user->can('zesk\\Module_PolyGlot::load', null, [
			'locale' => $app_locale,
		])) {
			return $this->json([
				'status'  => true,
				'message' => $app_locale->__('Loaded locale {lang_name}.', $__),
				'items'   => $this->module->loadLocale($locale_string),
			]);
		}
		return $this->json([
			'status'  => false,
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
			'locale'    => $locale_string,
		];
		if (!$this->user->can($this->module::class . 'update', null, [
			'locale' => $locale_string,
		])) {
			return $this->json([
				'status'  => false,
				'message' => $locale->__('You do not have permission to update the {lang_name} locale.'),
			]);
		}
		if (Update::factory($this->application, $locale_string, $this->user)) {
			return $this->json([
				'status'  => true,
				'message' => $locale->__('Locale {lang_name} will be updated in the next few minutes.', $__),
			]);
		}
		return $this->json([
			'status'  => false,
			'message' => $locale->__('Locale {lang_name} was not updated.', $__),
		]);
	}
	
	/**
	 * Save a token
	 *
	 * @param Request  $request
	 * @param Response $response
	 * @param string   $locale
	 * @return Response
	 */
	public function action_token(Request $request, Response $response, string $locale): Response
	{
		$__ = [
			'lang_name' => Locale::parseLanguage($locale),
			'locale'    => $locale,
		];
		if (!$this->user->can(Module::class . '::translate', null, [
			'locale' => $locale,
		])) {
			return $this->json([
				'status'  => false,
				'message' => $this->application->locale->__('No permission to update {lang_name}.', $__),
			]);
		}
		$id = $request->getInt('id');
		$fields = [];
		foreach ([
					 'original',
					 'translation',
					 'status',
				 ] as $k) {
			$fields[$k] = $request->get($k);
		}
		$fields['dialect'] = Locale::parseDialect($locale);
		$fields['language'] = $language = Locale::parseLanguage($locale);
		if (empty($language)) {
			return $this->json([
				'status'  => false,
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
				'status'  => false,
				'message' => $errors,
			]);
		}
		$result = $object->store();
		return $response->json()->setData([
			'status'  => $result,
			'id'      => $object->id(),
			'message' => $result ? null : $object->storeErrors(),
		]);
	}
}
