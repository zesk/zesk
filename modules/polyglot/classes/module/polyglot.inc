<?php

/**
 *
 */
namespace zesk;

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
	protected $object_classes = array(
		"zesk\\PolyGlot_Token",
		"zesk\\PolyGlot_Update"
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
		$locales = $this->application->query_select("zesk\\Language")
			->what(array(
			"code" => "code",
			"dialect" => "dialect",
			"name" => "name"
		))
			->where("code", $list)
			->to_array();
		$results = array();
		foreach ($locales as $locale) {
			$code = $dialect = $name = null;
			extract($locale, EXTR_IF_EXISTS);
			if ($dialect) {
				$code .= "_$dialect";
			}
			$results[$code] = $name;
		}
		return __($results);
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
		$router->add_route("polyglot", array(
			"controller" => "zesk\\Controller_PolyGlot"
		));
		$router->add_route("polyglot/update/{dialect}", array(
			"controller" => "zesk\\Controller_PolyGlot",
			"action" => "update",
			"arguments" => array(
				2
			)
		));
		$router->add_route("polyglot/load/{dialect}", array(
			"controller" => "zesk\\Controller_PolyGlot",
			"action" => "load",
			"arguments" => array(
				2
			)
		));
		$router->add_route("polyglot/token/{dialect}", array(
			"controller" => "zesk\\Controller_PolyGlot",
			"action" => "token",
			"arguments" => array(
				2
			)
		));
	}
	
	/**
	 *
	 * @param string $locale
	 * @throws Exception_File_NotFound
	 * @throws Exception_File_Format
	 * @return array
	 */
	public function load_locale($locale = null) {
		$user = $this->application->user();
		$source_files = $this->option_list("source_files");
		$table = array();
		foreach ($source_files as $source_file) {
			if (!is_file($source_file)) {
				continue;
			}
			$tt = $this->application->load($source_file);
			if (!is_array($tt)) {
				throw new Exception_File_Format($source_file, "Source file {basename} does not return the correct format", array(
					"basename" => basename($source_file)
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
		$existing = Locale::load($locale);
		$table = $existing + $table;
		$language = Locale::language($locale);
		$dialect = Locale::dialect($locale);
		$tokens = PolyGlot_Token::fetch_all($this->application, $language, $dialect);
		
		foreach ($table as $original => $translation) {
			if (array_key_exists($original, $tokens)) {
				$object = $tokens[$original];
				$table[$original] = $object;
			} else {
				$table[$original] = PolyGlot_Token::create($language, $dialect, $original, $translation, array_key_exists($original, $existing) ? PolyGlot_Token::status_done : PolyGlot_Token::status_todo);
			}
		}
		return array_values($table);
	}
}

