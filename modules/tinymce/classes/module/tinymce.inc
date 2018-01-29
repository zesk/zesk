<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
use zesk\JSON;
use zesk\ArrayTools;
use zesk\Locale;
use zesk\JavaScript;
use zesk\PHP;
use zesk\Lists;
use zesk\Request;
use zesk\Response;
use zesk\Router;
use zesk\Template;
use zesk\Interface_Module_Routes;

/**
 *
 * @author kent
 *
 */
class Module_TinyMCE extends zesk\Module_JSLib implements Interface_Module_Routes {
	protected $javascript_paths = array(
		'/share/tinymce/tinymce.min.js' => array()
	);
	protected $tinymce_options = array(
		'script_url' => '/share/tinymce/tinymce.min.js',
		'menubar' => null,
		'relative_urls' => null,
		'selector' => 'textarea',
		'statusbar' => true,
		'toolbar' => null,
		'height' => null,
		'plugins' => array(
			"advlist",
			"autolink",
			"lists",
			"link",
			"image",
			"charmap",
			"print",
			"preview",
			"anchor",
			"searchreplace",
			"visualblocks",
			"code",
			"fullscreen",
			"insertdatetime",
			"media",
			"table",
			"contextmenu",
			"paste"
		)
	);

	/**
	 * Insertion menu
	 *
	 * @var array
	 */
	private $insertion_menus = array();

	/**
	 *
	 * @var string
	 */
	protected $tinymce_toolbar = "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image";

	/**
	 *
	 * @var array
	 */
	private $setup_js = array();

	/**
	 * Spelling dictionaries
	 *
	 * @var array of resource
	 */
	private $spellers = array();
	/**
	 * (non-PHPdoc)
	 *
	 * @see zesk\Module_JSLib::hook_head()
	 */
	public function hook_head(Request $request, Response $response, Template $template) {
		$this->update_options();
		return parent::hook_head($request, $response, $template);
	}
	public function hook_routes(Router $router) {
		$router->add_route('tinymce/spellchecker', array(
			'method' => array(
				$this,
				'action_spellchecker'
			),
			'arguments' => array(
				'{request}',
				'{response}'
			)
		));
	}
	public function action_spellchecker(Request $request, Response $response) {
		$params = $request->geta('params');
		if (!is_array($params)) {
			$response->json(array(
				'error' => __('Invalid server request. Please contact an administrator.')
			));
			return;
		}
		$lang = avalue($params, 'lang', $this->application->locale->language());
		$words = to_list(avalue($params, 'words'));
		if (!function_exists('pspell_new')) {
			$response->json(array(
				'error' => __('PSpell is not installed on the server. Please contact an administrator.')
			));
			return;
		}

		if (!array_key_exists($lang, $this->spellers)) {
			$this->spellers[$lang] = pspell_new($lang);
		}
		$pspell = $this->spellers[$lang];
		$result_words = array();
		foreach ($words as $word) {
			if (!pspell_check($word)) {
				$result_words[$word] = pspell_suggest($word);
			}
		}
		$response->json(array(
			'words' => array(
				"doog" => array(
					'dog',
					'god'
				)
			),
			'request' => $request->variables()
		));
	}
	/**
	 */
	protected function hook_configured() {
		$this->tinymce_options = $this->options_include(array_keys($this->tinymce_options)) + $this->tinymce_options;
	}

	/**
	 */
	public function hook_ready() {
		$this->jquery_ready['init'] = $this->jquery_ready();
	}
	public function tinymce_toolbar($set = null) {
		if ($set !== null) {
			$this->tinymce_toolbar = $set;
			return $this;
		}
		return $this->tinymce_toolbar;
	}
	public function tinymce_plugins($set = null, $append = true) {
		$plugins = avalue($this->tinymce_options, 'plugins', array());
		if ($set !== null) {
			if (!is_array($set)) {
				$set = explode(" ", $set);
			}
			if ($append) {
				foreach ($set as $plugin) {
					if (!in_array($plugin, $plugins)) {
						$plugins[] = $plugin;
					}
				}
			} else {
				$plugins = $set;
			}
			$this->tinymce_options['plugins'] = $plugins;
			return $this;
		}
		return $plugins;
	}
	/**
	 *
	 * @return string
	 */
	public function jquery_ready() {
		$json = JSON::encodex(ArrayTools::clean($this->tinymce_options, null));
		return $this->application->theme("tinymce/ready.js", array(
			"json" => $json,
			"base_url" => "/share/tinymce/"
		), array(
			"no_extension" => true
		));
	}

	/**
	 *
	 * @param array $set
	 * @param string $append
	 * @return Module_TinyMCE multitype:string NULL multitype:string
	 */
	public function tinymce_options(array $set = null, $append = true) {
		if ($set) {
			if ($append) {
				$this->tinymce_options = $set + $this->tinymce_options;
			} else {
				$this->tinymce_options = $set;
			}
			return $this;
		}
		return $this->tinymce_options;
	}
	public function add_insertion_menu($title, array $menu, array $options = array()) {
		$code = avalue($options, 'id', strtolower(PHP::clean_function($title)));
		$this->insertion_menus[$code] = array(
			'title' => $title,
			'menu' => $menu,
			'options' => $options
		);
		$this->tinymce_toolbar = Lists::remove($this->tinymce_toolbar, $code, " ");
		if (avalue($options, 'first')) {
			$this->tinymce_toolbar = "$code " . $this->tinymce_toolbar;
		} else {
			$this->tinymce_toolbar = $this->tinymce_toolbar . " $code";
		}
	}
	public function add_setup($js) {
		$this->setup_js[] = $js;
		return $this;
	}
	private function update_options() {
		$js = $this->setup_js;

		$js[] = "editor.on('change', function () { editor.save(); });";

		foreach ($this->insertion_menus as $code => $settings) {
			$title = $settings['title'];
			$menu = $settings['menu'];
			$mce_menu = array();
			foreach ($menu as $substitution => $name) {
				$mce_menu[] = array(
					'text' => $name,
					'*onclick' => 'function() { editor.insertContent(' . JSON::encode('{' . $substitution . '}') . '); }'
				);
			}
			$attrs = array(
				'type' => 'menubutton',
				'text' => $title,
				'icon' => false,
				'menu' => $mce_menu
			);
			$js[] = 'editor.addButton(' . JavaScript::arguments($code, $attrs) . ");\n";
		}
		if (count($js) === 0) {
			return;
		}
		$this->tinymce_options['*setup'] = 'function (editor) {' . implode("\n", $js) . "}";
		$this->tinymce_options['toolbar'] = $this->tinymce_toolbar;

		if ($this->has_option("language_url_path")) {
			// Language file is specified relative to document root, currently.
			$language_url_path = $this->option("language_url_path");
			$doc_root = $this->application->document_root();
			$full_path = path($doc_root, $language_url_path);
			if (!is_dir($full_path)) {
				$this->application->logger->error("Directory {class}::language_url_path specifies {full_path} which is not a directory", array(
					"class" => __CLASS__,
					"full_path" => $full_path
				));
			} else {
				$locale = $this->application->locale;
				$language = $locale->language();
				foreach (array(
					path($language_url_path, $locale->id() . ".js"),
					path($language_url_path, $locale->language() . ".js")
				) as $uri) {
					if (file_exists(path($doc_root, $uri))) {
						$this->tinymce_options['language_url'] = $uri;
						break;
					}
				}
			}
		}
		$this->hook_ready();
	}
}
