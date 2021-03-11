<?php

/**
 *
 */
namespace zesk;

/**
 *
 */
use zesk\Locale\Reader;

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
	protected $model_classes = array(
		PolyGlot_Token::class,
		PolyGlot_Update::class,
	);

	public function hook_configured() {
		$this->application->configuration->deprecated("Module_PolyGlot", __CLASS__);
	}

	/**
	 *
	 * @return array
	 */
	public function locale_options() {
		$list = $this->call_hook_arguments("locale_options", array(), $this->option_array("locale_options"));
		$options = array();
		$where = array();
		foreach ($list as $locale) {
			$language = Locale::parse_language($locale);
			$dialect = Locale::parse_dialect($locale);
			$w = array(
				'code' => $language,
			);
			if ($dialect) {
				$w['dialect'] = $dialect;
			}
			$where[] = $w;
		}
		$query = $this->application->orm_registry(Language::class)
			->query_select()
			->what(array(
			"code" => "code",
			"dialect" => "dialect",
			"name" => "name",
		))
			->where($where ? array(
			$where,
		) : array());
		$locales = $query->to_array();
		$results = array();
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
	 * @see Module::hook_routes
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Module_Routes::hook_routes()
	 */
	public function hook_routes(Router $router) {
		$base = array(
			"controller" => Controller_PolyGlot::class,
		);
		$router->add_route("polyglot", $base);
		$router->add_route("polyglot/load/{dialect}", $base + array(
			"action" => "load",
			"arguments" => array(
				2,
			),
		));
		$router->add_route("polyglot/token/{dialect}", $base + array(
			"action" => "token",
			"arguments" => array(
				2,
			),
		));
		$router->add_route("polyglot/update/{dialect}", $base + array(
			"action" => "update",
			"arguments" => array(
				2,
			),
		));
	}

	/**
	 *
	 * @param string $locale
	 * @return PolyGlot_Token[]
	 * @throws Exception_File_Format|Exception_Directory_NotFound
	 * @throws Exception_File_NotFound
	 */
	public function load_locale($locale = null) {
		$source_files = $this->option_list("source_files");
		$table = array();
		foreach ($source_files as $source_file) {
			$source_file = $this->application->paths->expand($source_file);
			if (!is_file($source_file)) {
				continue;
			}
			$tt = $this->application->load($source_file);
			if (!is_array($tt)) {
				throw new Exception_File_Format($source_file, "Source file {basename} does not return the correct format", array(
					"basename" => basename($source_file),
				));
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
		$existing = Reader::factory($this->application->locale_path(), $locale)->execute();
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
