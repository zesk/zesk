<?php declare(strict_types=1);
/**
 * @author kent
 * @package zesk/modules
 * @subpackage Polyglot
 * @copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\Polyglot;

/**
 *
 */

use zesk\Exception_File_Format;
use zesk\Exception_File_NotFound;
use zesk\Interface_Module_Routes;
use zesk\Locale;
use zesk\Locale\Reader;
use zesk\Module as BaseModule;
use zesk\ORM\Exception_ORMNotFound;
use zesk\Router;
use World\Language;

/**
 *
 * @author kent
 *
 */
class Module extends BaseModule implements Interface_Module_Routes {
	/**
	 *
	 * @var array
	 */
	protected array $modelClasses = [
		Token::class,
		Update::class,
	];

	/**
	 *
	 * @return array
	 */
	public function localeOptions(): array {
		$list = $this->callHookArguments('localeOptions', [], $this->optionArray('localeOptions'));
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
			->appendWhat([
				'code' => 'code',
				'dialect' => 'dialect',
				'name' => 'name',
			])
			->appendWhere($where ? [
				$where,
			] : []);
		$locales = $query->toArray();
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
	 * @see Interface_Module_Routes::hook_routes()
	 */
	public function hook_routes(Router $router): void {
		$base = [
			'controller' => Controller::class,
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
	 * @return Token[]
	 * @throws Exception_File_Format
	 * @throws Exception_File_NotFound|Exception_ORMNotFound
	 */
	public function loadLocale(string $locale = ''): array {
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
		$tokens = Token::fetchAll($this->application, $language, $dialect);

		foreach ($table as $original => $translation) {
			if (array_key_exists($original, $tokens)) {
				$object = $tokens[$original];
				$table[$original] = $object;
			} else {
				$table[$original] = Token::create($this->application, $language, $dialect, $original, $translation, array_key_exists($original, $existing) ? Token::STATUS_DONE : Token::STATUS_TODO);
			}
		}
		return array_values($table);
	}
}
