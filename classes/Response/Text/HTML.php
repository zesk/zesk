<?php

/**
 * $URL$
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
 */
namespace zesk;

/**
 * Encapsulate a HTML response
 *
 * @author kent
 * @see Response
 */
class Response_Text_HTML extends Response_Text {
	/**
	 * Content-Type header
	 *
	 * @var string
	 */
	public $content_type = "text/html";

	/**
	 * ID counter for rendering things on the page which should have unique IDs
	 *
	 * @var integer
	 */
	private $id_counter = 0;

	/**
	 *
	 * @var string
	 */
	private $head_prefix = "";

	/**
	 *
	 * @var string
	 */
	private $head_suffix = "";
	/**
	 * head <link tags>
	 *
	 * @var array
	 */
	private $links = array();

	/**
	 * head <link tags> as [rel] => array(path1,path2)
	 *
	 * @var array
	 */
	private $links_by_rel = array();

	/**
	 * Links sorted?
	 *
	 * @var boolean
	 */
	private $links_sorted = null;

	/**
	 * <script> tags
	 *
	 * @var array
	 */
	private $scripts = array();

	/**
	 * Whether the scripts array has been sorted by weight
	 *
	 * @var boolean
	 */
	private $scripts_sorted = false;

	/**
	 * State for inline script capturing.
	 * When non-null, we're between begin/end script calls.
	 *
	 * @var string
	 */
	private $script_begin = null;

	/**
	 * Globals to set on the page
	 *
	 * @var array
	 */
	private $script_settings = array();

	/**
	 * Page title
	 *
	 * @var string
	 */
	private $title = "";

	/**
	 * Head meta tags
	 *
	 * @var array
	 */
	private $meta = array();

	/**
	 * Head stylesheets
	 *
	 * @var array
	 */
	private $styles = array();

	/**
	 * <html> tag attributes
	 *
	 * @var array
	 */
	private $html_attributes = array();

	/**
	 * <body> tag attributes
	 *
	 * @var array
	 */
	private $body_attributes = array();

	/**
	 * jquery ready functions
	 *
	 * @var array
	 */
	private $jquery = null;

	/**
	 * Change the doctype
	 *
	 * @var string
	 */
	public $doctype = null;

	/**
	 * Change the document language
	 *
	 * @var string
	 */
	public $language = "en_US";

	/**
	 *
	 * @var string
	 */
	protected $page_theme = "page";

	/**
	 *
	 * @param Application $application
	 * @param array $options
	 */
	public function __construct(Application $application, $options = null) {
		parent::__construct($application, $options);
		$this->script_settings = array(
			'zesk' => array(
				'inited' => $application->initialization_time()
			)
		);
		$this->page_theme = $this->option("page_theme", $this->page_theme);
	}

	/**
	 * Output head attributes for a page.
	 *
	 * Allows registering head tags in one place, and have the page do the right thing.
	 */
	function head() {
		return $this->head_prefix . HTML::tag('title', $this->title()) . $this->metas() . $this->links() . $this->inline_styles() . $this->head_suffix;
	}

	/**
	 * Set arbitrary HTML to place at the beginning of the <head> tag
	 *
	 * @param string $set
	 * @param boolean $prepend
	 * @return string
	 */
	function head_prefix($set = null, $prepend = true) {
		if ($set !== null) {
			$this->head_prefix = $prepend ? $set . $this->head_prefix : $set;
		}
		return $this->head_prefix;
	}

	/**
	 * Set arbitrary HTML to place at the end of the <head> tag
	 *
	 * @param string $set
	 * @param boolean $append
	 * @return string
	 */
	function head_suffix($set = null, $append = true) {
		if ($set !== null) {
			$this->head_suffix = $append ? $this->head_suffix . $set : $set;
		}
		return $this->head_suffix;
	}

	/**
	 * Set/get page title
	 *
	 * @param string $set
	 * @param string $overwrite
	 * @return string
	 */
	function title($set = null, $overwrite = true) {
		if ($set !== null) {
			if ($overwrite || $this->title === "") {
				$this->title = (string) $set;
				$this->application->logger->debug("Set title to \"$set\"");
			} else {
				$this->application->logger->debug("Failed to set title to \"$set\"");
			}
		}
		return $this->title;
	}

	/**
	 * Output body open tag
	 *
	 * @return string
	 */
	function body_begin() {
		return "<body" . HTML::attributes($this->option_array('body_attributes', array()) + self::body_attributes()) . ">";
	}

	/**
	 * Output body close tag
	 *
	 * @return string
	 */
	function body_end() {
		return "</body>";
	}
	final public function body_add_class($add = null) {
		$this->body_attributes = HTML::add_class($this->body_attributes, $add);
		return $this;
	}
	/**
	 * Get/set body attributes
	 *
	 * @param string $add
	 * @param string $value
	 * @return Response_Text_HTML|string
	 */
	final public function body_attributes($add = null, $value = null) {
		if (is_array($add)) {
			$this->body_attributes = $add + $this->body_attributes;
			return $this;
		} else if (is_string($add)) {
			$this->body_attributes[$add] = $value;
			return $this;
		}
		return $this->body_attributes;
	}

	/**
	 * Get/set HTML attributes
	 *
	 * @param string $add
	 * @param string $value
	 * @return Response_Text_HTML|string
	 */
	function html_attributes($add = null, $value = null) {
		if (is_array($add)) {
			$this->html_attributes = $add + $this->html_attributes;
			return $this;
		} else if (is_string($add)) {
			$this->html_attributes[$add] = $value;
			return $this;
		}
		return $this->html_attributes;
	}

	/**
	 * Get/set meta keywords
	 *
	 * @param string $content
	 * @return Response_Text_HTML|string
	 */
	function meta_keywords($content = null) {
		return $this->meta("keywords", $content);
	}

	/**
	 * Get/set meta description text
	 *
	 * @param string $content
	 * @return Response_Text_HTML|string
	 */
	function meta_description($content = null) {
		return $this->meta("description", $content);
	}

	/**
	 * Get/set meta tags
	 *
	 * @param string $name
	 * @param string $content
	 * @return Response_Text_HTML|this
	 */
	function meta($name = null, $content = null) {
		if (is_array($name)) {
			$this->meta[md5(serialize($name))] = $name;
			return $name;
		}
		if ($name === null) {
			return $this->meta;
		}
		if ($content === null) {
			return avalue(avalue($this->meta, $name, array()), 'content', null);
		}
		$this->meta[$name] = array(
			'name' => $name,
			'content' => $content
		);
		return $this;
	}

	/**
	 * Set Meta HTTP tag
	 *
	 * @param string $name
	 * @param string $content
	 * @return Response_Text_HTML
	 */
	function meta_http($name, $content) {
		$this->meta[$name] = array(
			'http-equiv' => $name,
			'content' => $content
		);
		return $this;
	}

	/**
	 * Add nocache meta tags
	 *
	 * @return Response_Text_HTML
	 */
	function meta_nocache() {
		$this->meta_http('pragma', 'no-cache');
		$this->meta_http('cache-control', 'no-cache');
		$this->meta_http('expires', '-1');
		return $this;
	}

	/**
	 * Get/Set shortcut icon
	 *
	 * @param string $path
	 * @return Response_Text_HTML|string
	 */
	function shortcut_icon($set = null) {
		return $this->_shortcut_icon($set);
	}

	/**
	 * Get/Set shortcut icon internally
	 *
	 * @param string $path
	 * @param string $cdn
	 */
	private function _shortcut_icon($path = null, $cdn = false) {
		if ($path === null) {
			return $this->link('shortcut icon');
		}
		$attrs = $cdn ? array(
			'cdn' => true
		) : array();
		$type = MIME::from_filename($path);
		$this->link('shortcut icon', $path, $type, $attrs);
		$this->link('icon', $path, $type, $attrs);
		return $this;
	}

	/**
	 * Get/set a link tag in the header
	 *
	 * @param string $rel
	 *        	Link rel=""
	 * @param string $path
	 * @param string $type
	 * @param array $attrs
	 * @throws Exception_Semantics
	 * @return Response_Text_HTML|array
	 */
	function link($rel, $path = null, $type = null, $attrs = array()) {
		if ($path === null) {
			return ArrayTools::filter($this->links, avalue($this->links_by_rel, $rel, array()));
		}
		$attrs = to_array($attrs, array());
		if (!array_key_exists('weight', $attrs)) {
			$attrs['weight'] = count($this->links) * 10;
		}
		$arr = array(
			'rel' => $rel,
			'href' => HTML::href($this->application, $path)
		);
		if ($type !== null) {
			$arr['type'] = $type;
		}
		$share = avalue($attrs, 'share', false);
		if (!$share && $this->option_bool("require_root_dir") && !array_key_exists("root_dir", $attrs)) {
			throw new Exception_Semantics("{path} requires a root_dir specified", compact('rel', 'path'));
		}
		ArrayTools::append($this->links_by_rel, $rel, $path);
		$this->links[$path] = $arr + $attrs;
		$this->links_sorted = null;
		return $this;
	}

	/**
	 * Add a css to the page
	 *
	 * @param string $path
	 *        	Path to css file
	 * @param array $options
	 *        	Optional options: media (defaults to all), type (defults to text/css), browser
	 *        	(may be ie,
	 *        	ie6, ie7), and cdn (boolean to prefix with cdn path)
	 * @return void
	 */
	function css($path, $mixed = null, $options = null) {
		if (is_string($mixed)) {
			if (is_string($options)) {
				$options = array(
					'media' => $options
				);
			}
			$options['root_dir'] = $mixed;
		} else if (is_array($mixed)) {
			$options = $mixed;
		}
		$options = is_array($options) ? $options : array();
		$options += array(
			'type' => 'text/css',
			'media' => 'all'
		);
		return $this->link('stylesheet', $path, $options['type'], $options);
	}

	/**
	 * Inline styles ouput
	 *
	 * @return string
	 */
	function inline_styles() {
		$html = array();
		foreach ($this->styles as $attributes) {
			$content = $attributes['content'];
			$html[] = HTML::tag("style", ArrayTools::filter($attributes, "type;id;media;dir;lang;title;xml:lang"), $content);
		}
		if (count($html) > 0) {
			$html[] = "";
		}
		return implode("\n", $html);
	}

	/**
	 * Add a css to the page
	 *
	 * @param string $path
	 *        	Path to css file
	 * @param array $options
	 *        	Optional options: media (defaults to screen), type (defults to text/css), browser
	 *        	(may be
	 *        	ie, ie6, ie7), and cdn (boolean to prefix with cdn path)
	 * @return void
	 */
	function css_inline($styles, $options = null) {
		if (is_string($options)) {
			$options = array(
				'media' => $options
			);
		}
		$options = is_array($options) ? ArrayTools::trim_clean($options) : array();
		$options += array(
			'type' => 'text/css'
		);
		$options['content'] = $styles;
		$this->styles[md5($styles)] = $options;
	}

	/**
	 * Output meta tags
	 *
	 * @return string
	 */
	function metas() {
		$result = array();
		foreach ($this->meta as $attrs) {
			$result[] = HTML::tag('meta', $attrs);
		}
		return implode("\n", $result);
	}

	/**
	 * Output links
	 *
	 * @param array $options
	 * @return string
	 */
	function links(array $options = array()) {
		$result = $this->link_tags($options);
		$output = array();
		foreach ($result as $tag) {
			$name = $attributes = $content = $prefix = $suffix = null;
			extract($tag, EXTR_IF_EXISTS);
			if (empty($content)) {
				$content = null;
			}
			$output[] = $prefix . HTML::tag($name, $attributes, $content) . $suffix;
		}
		return implode("\n", $output);
	}

	/**
	 * Retrieve link tags in unrendered form for output via JSON or other mechanism
	 *
	 * @param array $options
	 * @return array
	 */
	function link_tags(array $options = array()) {
		$result = array();
		$stylesheets_inline = to_bool(avalue($options, 'stylesheets_inline'));
		if (!$this->links_sorted) {
			$this->links_sorted = $this->links;
			usort($this->links_sorted, "zesk_sort_weight_array");
		}
		$cache_links = $this->option_bool("cache_links", false);
		$cached_media = array();
		$this->links_sorted = $this->call_hook_arguments("links_preprocess", array(
			$this->links_sorted
		), $this->links_sorted);
		foreach ($this->links_sorted as $attrs) {
			$tag = self::browser_conditionals(avalue($attrs, 'browser'));

			$root_dir = avalue($attrs, 'root_dir');
			$cdn = avalue($attrs, 'cdn');
			$share = avalue($attrs, 'share');
			$rel = avalue($attrs, 'rel');
			if ($stylesheets_inline && $rel === 'stylesheet') {
				$dest = self::resource_path($attrs['href'], $attrs);
				if (!is_file($dest)) {
					$this->application->logger->error("Inline stylesheet path {dest} not found: {attributes}", array(
						"dest" => $dest,
						"attributes" => serialize($attrs)
					));
				} else {
					$tag['name'] = 'style';
					$tag['attributes'] = array(
						'type' => 'text/css'
					) + ArrayTools::filter($attrs, 'media');
					$tag['content'] = file_get_contents($dest);
					$result[] = $tag;
				}
			} else {
				assert(array_key_exists('href', $attrs));
				$media = avalue($attrs, 'media', 'screen');
				list($href, $file_path) = $this->resource_date($attrs['href'], $attrs);
				if ($href) {
					$attrs['file_path'] = $file_path;
					$attrs['href_original'] = $attrs['href'];
					$attrs['href'] = $href;
					$attrs = $this->call_hook_arguments("link_process", array(
						$attrs
					), $attrs);
					// Only cache and group stylesheets, for now.
					if ($rel === 'stylesheet' && $cache_links && $file_path && !avalue($attrs, 'nocache')) {
						$cached_media[$media][$href] = $file_path;
						if ($cache_links) {
							continue;
						}
					}
				} else {
					$this->application->logger->error("Unable to find {href} in {root_dir}", $attrs);
					continue;
				}
				$tag = array(
					'name' => 'link',
					'attributes' => ArrayTools::filter($attrs, "rel;href;type;media"),
					'content' => ''
				);
				$result[] = $tag;
			}
		}
		if ($cache_links && count($cached_media) > 0) {
			foreach ($cached_media as $media => $cached) {
				array_unshift($result, self::resource_cache_css($cached, $media));
			}
		}
		return $result;
	}

	/**
	 *
	 * @param string $resource_path
	 * @param unknown $route_expire
	 */
	private function resource_path_route($resource_path, $route_expire) {
		$path = $this->application->cache_path(array(
			"resources",
			$resource_path
		));
		if (file_exists($path)) {
			if (!$route_expire) {
				return $path;
			}
			$mtime = filemtime($path);
			if ($mtime + $route_expire > time()) {
				return $path;
			}
		}
		Directory::depend(dirname($path), 0770);
		try {
			$content = $this->application->content($resource_path);
			file_put_contents($path, $content);
		} catch (Exception_NotFound $e) {
			return null;
		}
		return $path;
	}
	/**
	 * Given a path, retrieve the actual resource path with a timestamp for cachebusting
	 *
	 * @param string $_path
	 * @param array $attributes
	 * @return string
	 */
	protected function resource_path($_path, array $attributes) {
		$debug = false;
		$root_dir = $cdn = $share = $is_route = null;
		$route_expire = $this->option('resource_path_route_expire', 600); // 10 minutes
		extract($attributes, EXTR_IF_EXISTS);
		if ($root_dir) {
			if ($debug) {
				$this->application->logger->debug("rootdir (" . json_encode($root_dir) . ") check $_path");
			}
			return HTML::href($this->application, path($root_dir, $_path));
		} else if ($share) {
			if ($debug) {
				$this->application->logger->debug("share check $_path");
			}
			return Controller_Share::realpath($this->application, $_path);
		} else if ($is_route) {
			if ($debug) {
				$this->application->logger->debug("route check $_path");
			}
			return $this->resource_path_route($_path, $route_expire);
		} else {
			return null;
		}
	}

	/**
	 * Utility function to determine the date of a file
	 *
	 * @param string $path
	 * @param array $attributes
	 *        	Passed to resource_path
	 * @return array First item is the URI, 2nd is the full path to the file
	 */
	protected function resource_date($path, array $attributes) {
		$query = array();
		$file = $this->resource_path($path, $attributes);
		if (!$file || !is_file($file)) {
			$this->application->logger->warning("Resource {path} not found at {file}", array(
				"path" => $path,
				"file" => $file
			));
			return array(
				$path,
				null
			);
		}
		$query['_ver'] = date('YmdHis', filemtime($file));
		return array(
			URL::query_format($path, $query),
			$file
		);
	}

	/**
	 * Internal function to process CSS and cache it
	 *
	 * @param string $src
	 * @param string $file
	 * @param string $dest
	 * @param string $contents
	 * @return string
	 */
	protected function hook_process_cached_css($src, $file, $dest, $contents) {
		$matches = null;
		$contents = preg_replace('|/\*.+?\*/|m', '', $contents);
		$contents = preg_replace('|\s+|', ' ', $contents);
		$contents = strtr($contents, array(
			': ' => ':',
			'; ' => ';'
		));

		if (preg_match_all('|@import\s*([^;]+)\s*;|', $contents, $matches, PREG_SET_ORDER)) {
			$map = array();
			foreach ($matches as $match) {
				$import = trim($match[1]);
				$imatch = null;
				if (preg_match('|^"[^"]+"$|', $import) || preg_match("|^'[^']+'$|", $import)) {
					$import = unquote($import);
				} else if (preg_match('|^url\(([^)]+)\)$|', $import, $imatch)) {
					$import = unquote($imatch[1]);
					if (URL::valid($import) || $import[0] === '/') {
						continue;
					}
				} else {
					$this->application->logger->debug("Unknown @import syntax in {file}: {import}", array(
						"file" => $file,
						"import" => $match[0]
					));
					continue;
				}
				$import_src = path(dirname($src), $import);
				$import_file = path(dirname($file), $import);
				$import_contents = file_get_contents($import_file);
				$import_replace = $this->hook_process_cached_css($import_src, $import_file, $dest, $import_contents);
				$map[$match[0]] = $import_replace;
			}
			$contents = strtr($contents, $map);
		}
		if (!preg_match_all('|url\(([^)]+)\)|', $contents, $matches, PREG_SET_ORDER)) {
			return $contents;
		}
		$map = array();
		foreach ($matches as $match) {
			list($full_match, $rel_image) = $match;
			$rel_image = unquote($rel_image);
			if (URL::valid($rel_image) || StringTools::begins($rel_image, array(
				"/",
				"data:"
			))) {
				continue;
			}
			$rel_image = explode("/", $rel_image);
			$src_dir = explode("/", dirname($src));
			while ($rel_image[0] === "..") {
				array_shift($rel_image);
				array_pop($src_dir);
			}
			$src_dir = implode("/", $src_dir);
			$rel_image = implode("/", $rel_image);
			$new_href = path($src_dir, $rel_image);
			$this->application->logger->debug("hook_process_cached_css: $file: $rel_image => $new_href");
			$map[$full_match] = strtr($full_match, array(
				$match[1] => '"' . $new_href . '"'
			));
		}
		return strtr($contents, $map);
	}

	/**
	 * Internal function to process cached datatypes (CSS/JavaScript)
	 *
	 * @param string $src
	 * @param string $file
	 * @param string $dest
	 * @param string $extension
	 * @return string
	 */
	private function process_cached_type($src, $file, $dest, $extension) {
		$contents = file_get_contents($file);
		$contents = $this->call_hook_arguments("process_cached_$extension", array(
			$src,
			$file,
			$dest,
			$contents
		), $contents);
		return $contents;
	}

	/**
	 * Cache files in the resource paths tend to grow, particularly
	 *
	 * @param string $extension
	 */
	public function clean_resource_caches($extension = null) {
		$path = $this->resource_cache_path($extension);
		$files = Directory::list_recursive($path, array(
			"rules_directory_walk" => array(
				'#/cache/js#' => true,
				'#/cache/css#' => true,
				false
			),
			"add_path" => true
		));
		$expire_seconds = abs($this->option_integer("resource_cache_lifetime_seconds", 3600)); // 1 hour
		$now = time();
		$modified_after = $now - $expire_seconds;
		$deleted = array();
		if ($files) {
			foreach ($files as $file) {
				if (!is_file($file)) {
					continue;
				}
				$filemtime = filemtime($file);
				if ($filemtime < $modified_after) {
					$this->application->logger->debug("Deleting old file {file} modified on {when}, more than {delta} seconds ago", array(
						"file" => $file,
						"when" => date("Y-m-d H:i:s"),
						"delta" => $now - $filemtime
					));
					@unlink($file);
					$deleted[] = $file;
				}
			}
		}
		$this->application->logger->notice("Deleted {deleted} files from cache directory at {path} (Expire after {expire_seconds} seconds)", array(
			"deleted" => count($deleted),
			"path" => $path,
			"expire_seconds" => $expire_seconds
		));
		return $deleted;
	}

	/**
	 * Return the href for our resource cache
	 *
	 * @param string $extension
	 * @param string $filename
	 * @return string
	 */
	private function resource_cache_href($extension = null, $filename = null) {
		$segments[] = "/cache";
		if ($extension) {
			$segments[] = $extension;
			if ($filename) {
				$segments[] = $filename;
			}
		}
		return path($segments);
	}

	/**
	 * Return the absolute path of our resource cache
	 *
	 * @param string $extension
	 * @param string $filename
	 * @return string
	 */
	private function resource_cache_path($extension = null, $filename = null) {
		$href = $this->resource_cache_href($extension, $filename);
		$cache_path = path($this->application->document_root(), $href);
		return $cache_path;
	}

	/**
	 * Run JavaScript/CSS concatenation
	 *
	 * @param array $cached
	 * @param string $extension
	 * @param string $hook
	 * @return string
	 */
	private function resource_cache(array $cached, $extension, $hook, &$debug) {
		$debug = array();
		$hash = array();
		foreach ($cached as $key => $value) {
			if (is_numeric($key)) {
				$hash[] = $value;
			} else {
				$hash[] = $key;
				$debug[] = $key;
			}
		}
		if ($this->option_bool("debug_resource_cache")) {
			file_put_contents($this->application->path("/resource_cache-" . date("Y-m-d-H-i-s") . ".txt"), implode("\n", $hash));
		}
		$hash = md5(implode("|", $hash));
		$cache_path = $this->resource_cache_path($extension);
		$href = $this->resource_cache_href($extension, "$hash.$extension");
		$cache_path = $this->resource_cache_path($extension, "$hash.$extension");
		if (!file_exists($cache_path)) {
			Directory::depend(dirname($cache_path), 0770);
			$content = "";
			$srcs = array();
			foreach ($cached as $src => $mixed) {
				if (is_numeric($src)) {
					// $mixed is JavaScript code/insertion string
					$content .= "$mixed\n";
				} else {
					// $mixed is filename
					$content .= "/* Source: $src */\n" . $this->process_cached_type($src, $mixed, $href, $extension) . "\n";
					$srcs[] = $src;
				}
			}
			$content = $this->call_hook($hook, $content);
			file_put_contents($cache_path, $content);
		}
		$debug = implode("\n", $debug);
		return $href;
	}

	/**
	 * Run CSS caching
	 *
	 * @param array $cached
	 * @param string $media
	 * @return array
	 */
	private function resource_cache_css(array $cached, $media = "screen") {
		$debug = "";
		$href = self::resource_cache($cached, "css", "compress_css", $debug);
		return array(
			'name' => 'link',
			'attributes' => array(
				'href' => $href,
				'rel' => 'stylesheet',
				"media" => $media
			),
			'content' => "",
			'suffix' => $this->application->development() ? "<!--\n$debug\n-->" : ""
		);
	}

	/**
	 * Run Script caching
	 *
	 * @param array $cached
	 * @return multitype:string multitype:string
	 */
	private function resource_cache_scripts(array $cached) {
		$debug = "";
		$href = self::resource_cache($cached, "js", "compress_script", $debug);
		return array(
			'name' => 'script',
			'attributes' => array(
				'src' => $href,
				'type' => 'text/javascript'
			),
			'content' => "",
			'suffix' => $this->application->development() ? "<!--\n$debug\n-->" : ""
		);
	}

	/**
	 * Output all scripts
	 *
	 * @return string
	 */
	function scripts() {
		$script_tags = $this->script_tags();
		$result = array();
		foreach ($script_tags as $script_tag) {
			$name = $attributes = $content = $prefix = $suffix = null;
			extract($script_tag, EXTR_IF_EXISTS);
			if (empty($content)) {
				$content = "";
			}
			$result[] = $prefix . HTML::tag($name, $attributes, $content) . $suffix;
		}
		$jquery_ready = $this->jquery_ready();
		if (count($jquery_ready)) {
			$result[] = HTML::tag('script', array(
				'type' => 'text/javascript'
			), "\n\$(document).ready(function() {\n" . implode("\n", $jquery_ready) . "\n});\n");
		}
		return implode("\n", $result);
	}

	/**
	 * Output jQuery ready tags
	 *
	 * @return string
	 */
	public function jquery_ready() {
		if (!$this->jquery) {
			return array();
		}
		$result = array();
		ksort($this->jquery, SORT_NUMERIC);
		foreach ($this->jquery as $weight => $hash_scripts) {
			$result = array_merge($result, array_values($hash_scripts));
		}
		return $result;
	}

	/**
	 * Output generated script tags.
	 * Returns array of arrays
	 *
	 * Each array consists of the following values:
	 * <code>
	 * array(
	 * 'name' => 'script',
	 * 'attributes' => array('type' => 'text/javascript', 'src' => '),
	 * </code>
	 *
	 * @return string
	 */
	public function script_tags($cache_scripts = null) {
		// Sort them by weight if they're not sorted
		if (!$this->scripts_sorted) {
			uasort($this->scripts, "zesk_sort_weight_array");
			$this->scripts_sorted = true;
		}
		if ($cache_scripts === null) {
			$cache_scripts = $this->option_bool("cache_scripts", false);
		}
		$cached = $cached_append = array();
		$result = array();
		/* Output scripts */
		$selected_attributes = to_list("src;type;async;defer;id");
		if ($this->option_bool("debug_weight")) {
			$selected_attributes[] = "weight";
		}
		foreach ($this->scripts as $attrs) {
			$script_attributes = ArrayTools::filter($attrs, $selected_attributes) + HTML::data_attributes($attrs);
			if (array_key_exists('callback', $attrs)) {
				$attrs['content'] = call_user_func($attrs['callback']);
			}
			$attrs += self::browser_conditionals(avalue($attrs, 'browser'));
			if (array_key_exists('content', $attrs)) {
				$script = array(
					'name' => 'script',
					'attributes' => $script_attributes,
					'content' => $attrs['content']
				);
			} else {
				assert(array_key_exists('src', $attrs));
				if (avalue($attrs, 'nocache')) {
					$resource_path = URL::query_append($attrs['src'], array(
						$this->option('scripts_nocache_variable', "_r") => md5(microtime())
					));
					$script_attributes['src'] = $resource_path;
				} else if (URL::valid($attrs['src'])) {
					$script_attributes['src'] = $attrs['src'];
				} else {
					list($resource_path, $file_path) = $this->resource_date($attrs['src'], $attrs);
					if ($resource_path) {
						$script_attributes['src'] = $resource_path;
						if ($cache_scripts && $file_path) {
							if (array_key_exists('javascript_before', $attrs)) {
								$cached[] = $attrs['javascript_before'];
							}
							$cached[$resource_path] = $file_path;
							$cached_append[] = "zesk.scripts_cached(" . json_encode($resource_path) . ");";
							if (array_key_exists('javascript_after', $attrs)) {
								$cached[] = $attrs['javascript_after'];
							}
							continue;
						}
					}
				}
				$script = array(
					'name' => 'script',
					'attributes' => $script_attributes,
					'content' => ''
				);
			}
			if (array_key_exists('javascript_before', $attrs)) {
				$result[] = array(
					"name" => 'script',
					"type" => "text/javascript",
					"content" => $attrs['javascript_before']
				);
			}
			$result[] = $script;
			if (array_key_exists('javascript_after', $attrs)) {
				$result[] = array(
					"name" => 'script',
					"type" => "text/javascript",
					"content" => $attrs['javascript_after']
				);
			}
		}
		if (count($cached) > 0) {
			$cached = array_merge($cached, $cached_append);
			$result = array_merge(array(
				self::resource_cache_scripts($cached)
			), $result);
		}
		return $result;
	}
	private function find_weight($pattern, $method, $default = null) {
		$weight = $default;
		foreach ($this->scripts as $path => $attributes) {
			if (strpos($path, $pattern) !== false) {
				$this_weight = $attributes['weight'];
				$weight = $weight === null ? $this_weight : $method($weight, $this_weight);
			}
		}
		return $weight;
	}
	/**
	 * Internal function to add script tag
	 *
	 * @param string $path
	 * @param array $options
	 * @throws Exception_Semantics
	 * @return Response_Text_HTML
	 */
	private function script_add($path, array $options) {
		if (array_key_exists($path, $this->scripts)) {
			return $this;
		}
		if (!array_key_exists('weight', $options)) {
			$last_weight = count($this->scripts) * 10;
			$before = null;
			if (array_key_exists('before', $options)) {
				if (($before = $this->find_weight($options['before'], "min")) !== null) {
					$options['weight'] = $before - 1;
				}
			}
			if (array_key_exists('after', $options)) {
				if (($after = $this->find_weight($options['after'], "max")) !== null) {
					if ($before !== null) {
						if ($after <= $before) {
							throw new Exception_Semantics("{path} has a computed {before} weight which is greater than the after weight {after}", compact('path', 'before', 'after'));
						} else {
							$options['weight'] = $before - 1;
						}
					} else {
						$options['weight'] = $after + 1;
					}
				}
			}
			if (!array_key_exists('weight', $options)) {
				$options['weight'] = count($this->scripts) * 10;
			}
		}
		$nocache = avalue($options, 'nocache', false);
		$share = avalue($options, 'share', false);
		$content = array_key_exists('content', $options);
		$is_route = avalue($options, 'is_route');
		$callback = array_key_exists('callback', $options);
		if (!$is_route && !$callback && !$content && !$share && !$nocache && $this->option_bool("require_root_dir") && !array_key_exists("root_dir", $options)) {
			throw new Exception_Semantics("{path} requires a root_dir specified", compact('path'));
		}
		$options += array(
			'type' => "text/javascript"
		);
		$this->scripts[$path] = $options;
		$this->scripts_sorted = false;
		return $this;
	}

	/**
	 * Add to JavaScript script settings
	 *
	 * @param array $settings
	 */
	public final function javascript_settings(array $settings = null) {
		if ($settings === null) {
			return $this->script_settings;
		}
		$this->script_settings = ArrayTools::merge($this->script_settings, $settings);
		return $this;
	}

	/**
	 * Return JavaScript code to load JavaScript settings
	 */
	public function _javascript_settings() {
		return '$.extend(true, window.zesk.settings, ' . JSON::encode($this->script_settings) . ');';
	}

	/**
	 * Register a javascript to be put on the page
	 *
	 * @param string $path
	 *        	File path to serve for the javascript
	 * @param array $options
	 *        	Optional settings: type (defaults to text/javascript), browser (defaults to all
	 *        	browsers),
	 *        	cdn (defaults to false)
	 * @return Response_Text_HTML
	 */
	function javascript($path, array $options = null) {
		if (empty($path)) {
			return $this;
		}
		if (is_array($path)) {
			$result = array();
			foreach ($path as $index => $path) {
				$this->javascript($path, $options);
			}
			return $this;
		}
		$options['src'] = $path;
		return $this->script_add($path, $options);
	}

	/**
	 * Include JavaScript to be included inline in the page
	 *
	 * @param string $script
	 * @param string $options
	 * @return Response_Text_HTML
	 */
	function javascript_inline($script, $options = null) {
		$options = to_array($options, array());
		$multiple = to_bool(avalue($options, "multiple", false));
		$id = array_key_exists("id", $options) ? $options['id'] : md5($script);
		if ($multiple) {
			$id = $id . '-' . count($this->scripts);
		}
		return $this->script_add($id, array(
			'content' => $script,
			'browser' => avalue($options, 'browser')
		) + $options);
	}

	/**
	 * Utility to capture output for inline script.
	 * Bracket your code with begin/end, like so:
	 *
	 * <code>
	 * $this->response->script_begin();
	 * ?>
	 * if (run_cool_tool()) { alert('worked'); }
	 * <?php
	 * $this->response->script_end();
	 * </code>
	 *
	 * @param string $options
	 *        	Options for the script tag
	 * @throws Exception_Semantics
	 */
	function script_begin($options = false) {
		if ($this->script_begin !== null) {
			throw new Exception_Semantics("self::script_begin called twice");
		}
		ob_start();
		$this->script_begin = $options;
	}

	/**
	 * Finish capturing output and add script to page's inline javascript
	 *
	 * @throws Exception_Semantics
	 */
	function script_end() {
		if ($this->script_begin === null) {
			throw new Exception_Semantics("{class}::script_begin was not called", array(
				"class" => __CLASS__
			));
		}
		$options = $this->script_begin;
		$content = ob_get_clean();
		$was_wrapped = HTML::extract_tag_contents("script", $content);
		if ($was_wrapped) {
			$content = $was_wrapped;
		}
		$this->javascript_inline($content, $options);
		$this->script_begin = null;
		return $this;
	}

	/**
	 * Add jQuery to page and ensure it's initialized
	 */
	private function _jquery() {
		if ($this->jquery !== null) {
			return;
		}
		$this->javascript("/share/jquery/jquery.js", array(
			'weight' => 'zesk-first',
			'share' => true
		));
		$this->javascript("/share/zesk/js/zesk.js", array(
			'weight' => 'first',
			'share' => true
		));
		$this->script_add('settings', array(
			'callback' => array(
				$this,
				'_javascript_settings'
			),
			'weight' => 'last'
		));
		$this->jquery = array();
	}

	/**
	 * Require jQuery on the page, and optionally add a ready script
	 *
	 * @param string $add_ready_script
	 * @param string $weight
	 */
	function jquery($add_ready_script = null, $weight = null) {
		$weight = intval($weight);
		$this->_jquery();
		if (is_array($add_ready_script)) {
			foreach ($add_ready_script as $add) {
				$this->jquery($add, $weight);
			}
			return $this;
		}
		if ($add_ready_script !== null) {
			$hash = md5($add_ready_script);
			$this->jquery[$weight][$hash] = $add_ready_script;
		}
		return $this;
	}

	/**
	 * Internal function to conditionally wrap script tags with browser conditions.
	 *
	 * Notice how some companies *need* to be special in this way.
	 *
	 * Avoid this like the plague, if possible. Unfortunately, crappy browser software may require
	 * it.
	 *
	 * @param string $browser
	 *        	Browser code
	 *
	 * @return array($prefix, $suffix)
	 */
	private function browser_conditionals($browser) {
		$prefix = "";
		$suffix = "";
		switch (strtolower($browser)) {
			case "ie":
				$prefix = "<!--[if IE]>";
				$suffix = "<![endif]-->";
				break;
			case "ie6":
				$prefix = "<!--[if lte IE 6]>";
				$suffix = "<![endif]-->";
				break;
			case "ie7":
				$prefix = "<!--[if IE 7]>";
				$suffix = "<![endif]-->";
				break;
			case "ie8":
				$prefix = "<!--[if IE 8]>";
				$suffix = "<![endif]-->";
				break;
			case "ie9":
				$prefix = "<!--[if IE 9]>";
				$suffix = "<![endif]-->";
				break;
			default :
				return array();
		}
		return array(
			'nocache' => true,
			'prefix' => $prefix,
			'suffix' => $suffix
		);
	}

	/**
	 * Page ID counter - always returns a unique ID PER PAGE
	 *
	 * @return number
	 */
	public function id_counter() {
		return $this->id_counter++;
	}

	/**
	 * Set/Get the doctype type
	 *
	 * @todo move to theme files
	 * Valid values are:
	 *
	 * xhtml1-strict
	 * xhtml11-strict
	 * html-transitional
	 * xhtml-transitional
	 *
	 * @param string $set
	 * @return Response_Text_HTML|string
	 */
	final function doctype($set = null) {
		if ($set !== null) {
			$this->doctype = $set;
			return $this;
		}
		$language = $this->application->locale->language();
		$this->html_attributes('xml:lang', $language);
		$result = "";
		switch ($this->doctype) {
			case "xhtml1-strict":
				$this->html_attributes('xmlns', 'http://www.w3.org/1999/xhtml');
				$result .= '<?xml version="1.0" encoding="ISO-8859-1" ?>';
				$result .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml1.dtd">';
				break;
			case 'xhtml11-strict':
				$this->html_attributes('xmlns', 'http://www.w3.org/1999/xhtml');
				$result .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">';
				break;
			case "html-transitional":
				$result .= '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
				break;
			case 'xhtml-transitional':
				$this->html_attributes('lang', $language);
				$result .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
				break;
			default :
				$this->html_attributes('lang', $language);
				$result .= '<!DOCTYPE html>';
				break;
		}

		return $result . "\n";
	}

	/**
	 * Convert Response to a JSON object for client-side rendering
	 *
	 * Returns an array containing:
	 *
	 * "scripts" array of script tags to load
	 * "stylesheets" array of stylesheets to load
	 * "head_tags" array of head tags to add
	 * "ready" array of JavaScript code to evaluate
	 *
	 * @return array
	 */
	public function to_json() {
		$script_tags = $this->script_tags(false);
		$scripts = array();
		$ready = $this->jquery_ready();
		foreach ($script_tags as $tag) {
			$prefix = $suffix = $name = $content = $attributes = null;
			extract($tag, EXTR_IF_EXISTS);
			if ($name !== "script") {
				continue;
			}
			$attributes = to_array($attributes);
			if (array_key_exists('src', $attributes)) {
				$scripts[] = $attributes['src'];
			} else if (!empty($content)) {
				$ready[] = $content;
			}
		}
		$link_tags = $this->link_tags();
		$stylesheets = array();
		$head_tags = array();
		foreach ($link_tags as $tag) {
			if ($tag['name'] === 'link' && apath($tag, 'attributes.rel') === 'stylesheet') {
				$stylesheets[] = $tag;
			} else {
				$head_tags[] = $tag;
			}
		}
		return ArrayTools::clean(parent::to_json() + array(
			'elapsed' => microtime(true) - $this->application->initialization_time(),
			'scripts' => count($scripts) ? $scripts : null,
			'stylesheets' => count($stylesheets) ? $stylesheets : null,
			'head_tags' => count($head_tags) ? $head_tags : null,
			'ready' => count($ready) ? $ready : null,
			'title' => $this->title()
		), null);
	}

	/**
	 * Set the page theme to use to render the final HTML output
	 *
	 * @param null|string $set
	 * @return \zesk\Response_Text_HTML|string
	 */
	public function page_theme($set = false) {
		if ($set !== false) {
			$this->page_theme = $set;
			return $this;
		}
		return $this->page_theme;
	}
	/**
	 * Removes all external links (CSS, JavaScript) from this response
	 *
	 * Useful for rendering emails on the page
	 */
	public function output(array $options = array()) {
		if ($this->content_type === "text/html" && $this->page_theme) {
			$this->content = $this->application->theme($this->page_theme, array(
				'content' => $this->content,
				'page_theme' => $this->page_theme
			));
		}
		$this->content = $this->call_hook("content_postprocess", $this->content);
		return parent::output($options);
	}

	/**
	 * Get/Set shortcut icon hosted on CDN
	 *
	 * @deprecated 2017-08
	 * @param string $path
	 * @return Response_Text_HTML|string
	 */
	function cdn_shortcut_icon($set = null) {
		zesk()->deprecated();
		return $this->_shortcut_icon($set, true);
	}

	/**
	 * Add a CSS to the page
	 *
	 * @deprecated 2017-08
	 * @param string $path
	 * @param array $options
	 * @return Response_Text_HTML|string
	 */
	function cdn_css($path = null, $options = null) {
		zesk()->deprecated();
		if (is_string($options)) {
			$options = array(
				'media' => $options
			);
		}
		$options['cdn'] = true;
		return $this->css($path, null, $options);
	}

	/**
	 * Register a javascript to be put on the page
	 *
	 * @param string $path
	 *        	File path to serve for the javascript
	 * @param array $options
	 *        	Optional settings: type (defaults to text/javascript), browser (defaults to all
	 *        	browsers),
	 *        	cdn (defaults to false)
	 * @return void
	 * @deprecated 2017-08
	 */
	function cdn_javascript($path, $options = null) {
		zesk()->deprecated();
		$options['cdn'] = true;
		return $this->javascript($path, $options);
	}
}
