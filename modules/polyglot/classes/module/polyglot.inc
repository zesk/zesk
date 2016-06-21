<?php
/**
 * 
 */
use zesk\Locale as Locale;

/**
 * 
 * @author kent
 *
 */
class Module_PolyGlot extends Module implements Module_Interface_Routes {
	
	/**
	 * 
	 * @var array
	 */
	protected $classes = array(
		"PolyGlot_Token",
		"PolyGlot_Update"
	);
	
	/**
	 * @return array
	 */
	public function locale_options() {
		$list = $this->hook_array("locale_options", array(), $this->option_array("locale_options"));
		$options = array();
		$locales = Object::class_query("Language")->what(array(
			"code" => "code",
			"dialect" => "dialect",
			"name" => "name"
		))->where("code", $list)->to_array();
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
	 * @see Module::hook_routes
	 * 
	 * {@inheritDoc}
	 * @see Module_Interface_Routes::hook_routes()
	 */
	public function hook_routes(Router $router) {
		$router->add_route("polyglot/update/{dialect}", array(
			"controller" => "PolyGlot",
			"action" => "update",
			"arguments" => array(
				2
			)
		));
		$router->add_route("polyglot/load/{dialect}", array(
			"controller" => "PolyGlot",
			"action" => "load",
			"arguments" => array(
				2
			)
		));
		$router->add_route("polyglot/token/{dialect}", array(
			"controller" => "PolyGlot",
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
		$user = User::instance();
		$source_files = $this->option_list("source_files");
		$table = array();
		foreach ($source_files as $source_file) {
			if (!is_file($source_file)) {
				continue;
			}
			$tt = zesk::load($source_file);
			if (!is_array($tt)) {
				throw new Exception_File_Format("Source file {basename} does not return the correct format", array(
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
		$existing = zesk\Locale::load($locale);
		$table = $existing + $table;
		$language = zesk\Locale::language($locale);
		$dialect = zesk\Locale::dialect($locale);
		$tokens = PolyGlot_Token::fetch_all($language, $dialect);
		
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