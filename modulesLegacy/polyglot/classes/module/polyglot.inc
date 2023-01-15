<?php declare(strict_types=1);

/**
 *
 */
namespace zesk;

/**
 *
 */

use Module;
use Reader;

/**
 *
 * @author kent
 *
 */
class Module_PolyGlot extends Module implements Interface_Module_Routes {
	/**
	 *
	 * @var array
	 */
	protected array $modelClasses = [
		PolyGlot_Token::class,
		PolyGlot_Update::class,
	];

	public function hook_configured(): void {
		$this->application->configuration->deprecated('Module_PolyGlot', __CLASS__);
	}

	/**
	 *
	 * @return array
	 */
	public function locale_options() {
		$list = $this->callHookArguments('locale_options', [], $this->optionArray('locale_options'));
		$options = [];
		$where = [];
		foreach ($list as $locale) {
			$language = Locale::parse_language($locale);
			$dialect = Locale::parse_dialect($locale);
			$w = [
				'code' => $language,
			];
			if ($dialect) {
				$w['dialect'] = $dialect;
			}
			$where[] = $w;
		}
		$query = $this->application->ormRegistry(Language::class)
			->querySelect()
			->addWhat([
				'code' => 'code',
				'dialect' => 'dialect',
				'name' => 'name',
			])
			->appendWhere($where ? [
				$where,
			] : []);
		$locales = $query->to_array();
		$results = [];
		foreach ($locales as $locale) {
			$code = $dialect = $name = null;
			extract($locale, EXTR_IF_EXISTS);
			if ($dialect) {
				$code .= "_$dialect";
			}
			$results[$code] = $name;
		}
		return $this->application->locale->__($results);
	}

	/**
	 *
	 * @see \zesk\zesk\Cron\Module::hook_routes
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Module_Routes::hook_routes()
	 */
	public function hook_routes(Router $router): void {
		$base = [
			'controller' => Controller_PolyGlot::class,
		];
		$router->addRoute('polyglot', $base);
		$router->addRoute('polyglot/load/{dialect}', $base + [
			'action' => 'load',
			'arguments' => [
				2,
			],
		]);
		$router->addRoute('polyglot/token/{dialect}', $base + [
			'action' => 'token',
			'arguments' => [
				2,
			],
		]);
		$router->addRoute('polyglot/update/{dialect}', $base + [
			'action' => 'update',
			'arguments' => [
				2,
			],
		]);
	}

	/**
	 *
	 * @param string $locale
	 * @return PolyGlot_Token[]
	 * @throws Exception_File_Format|Exception_Directory_NotFound
	 * @throws Exception_File_NotFound
	 */
	public function load_locale($locale = null) {
		$source_files = $this->optionIterable('source_files');
		$table = [];
		foreach ($source_files as $source_file) {
			$source_file = $this->application->paths->expand($source_file);
			if (!is_file($source_file)) {
				continue;
			}
			$tt = $this->application->load($source_file);
			if (!is_array($tt)) {
				throw new Exception_File_Format($source_file, 'Source file {basename} does not return the correct format', [
					'basename' => basename($source_file),
				]);
			}
			$table += $tt;
		}
		if (count($table) === 0) {
			foreach ($source_files as $source_file) {
				if (!is_file($source_file)) {
					throw new Exception_File_NotFound($source_file);
				}
			}
		}
		$existing = Reader::factory($this->application->localePath(), $locale)->execute();
		$table = $existing + $table;
		$language = Locale::parse_language($locale);
		$dialect = Locale::parse_dialect($locale);
		$tokens = PolyGlot_Token::fetch_all($this->application, $language, $dialect);

		foreach ($table as $original => $translation) {
			if (array_key_exists($original, $tokens)) {
				$object = $tokens[$original];
				$table[$original] = $object;
			} else {
				$table[$original] = PolyGlot_Token::create($this->application, $language, $dialect, $original, $translation, array_key_exists($original, $existing) ? PolyGlot_Token::status_done : PolyGlot_Token::status_todo);
			}
		}
		return array_values($table);
	}
}
