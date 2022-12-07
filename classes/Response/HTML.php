<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Response
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk\Response;

use zesk\Exception_Directory_Create;
use zesk\Exception_Directory_Permission;
use zesk\Exception_File_NotFound;
use zesk\Exception_Key;
use zesk\Exception_Redirect;
use zesk\URL;
use zesk\Response;
use zesk\MIME;
use zesk\ArrayTools;
use zesk\Exception_Semantics;
use zesk\Directory;
use zesk\Exception_NotFound;
use zesk\StringTools;
use zesk\Controller_Share;
use zesk\HTML as HTMLTools;
use zesk\JSON as JSONTools;

/**
 *
 * @author kent
 *
 */
class HTML extends Type {
	/**
	 * Page title
	 *
	 * @var string
	 */
	protected string $title = '';

	/**
	 * head <link tags>
	 *
	 * @var array
	 */
	private array $links = [];

	/**
	 * head <link tags> as [rel] => array(path1,path2)
	 *
	 * @var array
	 */
	private array $links_by_rel = [];

	/**
	 * Links sorted
	 *
	 * @var array
	 */
	private array $links_sorted = [];

	/**
	 * <script> tags
	 *
	 * @var array
	 */
	private array $scripts = [];

	/**
	 * Whether the scripts array has been sorted by weight
	 *
	 * @var boolean
	 */
	private bool $scripts_sorted = false;

	/**
	 * Globals to set on the page
	 *
	 * @var array
	 */
	private array $script_settings = [];

	/**
	 * Head meta tags
	 *
	 * @var array
	 */
	private array $meta = [];

	/**
	 * Head stylesheets
	 *
	 * @var array
	 */
	private array $styles = [];

	/**
	 * <html> tag attributes
	 *
	 * @var array
	 */
	private array $html_attributes = [];

	/**
	 * <body> tag attributes
	 *
	 * @var array
	 */
	private array $body_attributes = [];

	/**
	 * @var bool
	 */
	private bool $_jquery_added = false;

	/**
	 * jquery ready functions
	 *
	 * @var array
	 */
	private array $jquery = [];

	/**
	 *
	 * @var array|string
	 */
	protected array|string $page_theme = 'page';

	/**
	 *
	 */
	public function initialize(): void {
		$application = $this->application;
		$response = $this->parent;
		$this->script_settings = [
			'zesk' => [
				'inited' => $application->initializationTime(),
			],
		];
		$this->html_attributes = $response->optionArray('html_attributes');
		$this->body_attributes = $response->optionArray('body_attributes');

		$this->page_theme = $response->option('page_theme', $this->page_theme);
	}

	/**
	 * Get page title
	 *
	 * @return string
	 */
	public function title(): string {
		return $this->title;
	}

	public function setTitle(string $set): self {
		$this->title = $set;
		$this->application->logger->debug('Set title to "{title}"', ['title' => $set]);
		return $this;
	}

	final public function bodyAttributes(): array {
		return $this->body_attributes;
	}

	final public function setBodyAttributes(array $attributes): Response {
		$this->body_attributes = $attributes;
		return $this->parent;
	}

	final public function addBodyAttributes(array $attributes): Response {
		$this->body_attributes = $attributes + $this->body_attributes;
		return $this->parent;
	}

	/**
	 *
	 * @param array|string $classes
	 * @return Response
	 */
	final public function bodyAddClass(array|string $classes): Response {
		$this->body_attributes = HTMLTools::addClass($this->body_attributes, $classes);
		return $this->parent;
	}

	/**
	 * Get HTML attributes
	 *
	 * @return array
	 */
	public function attributes(): array {
		return $this->html_attributes;
	}

	/**
	 * Set HTML attributes
	 *
	 * @param array $attributes
	 * @param bool $merge
	 * @return Response
	 */
	public function setAttributes(array $attributes, bool $merge = false): Response {
		$this->html_attributes = $merge ? $attributes + $this->html_attributes : $attributes;
		return $this->parent;
	}

	/**
	 * Get/set meta keywords
	 *
	 * @return array
	 */
	public function metaKeywords(): array {
		return $this->meta('keywords');
	}

	/**
	 * Get/set meta keywords
	 *
	 * @param string $content
	 * @return Response
	 */
	public function setMetaKeywords(string $content): Response {
		return $this->setMeta('keywords', $content);
	}

	/**
	 * Get/set meta description text
	 *
	 * @return string
	 */
	public function metaDescription(): string {
		return $this->metaContent('description');
	}

	/**
	 * Get/set meta description text
	 *
	 * @param string $content
	 * @return Response
	 */
	public function setMetaDescription(string $content): Response {
		return $this->setMeta('description', $content);
	}

	/**
	 * Get meta tag(s)
	 *
	 * @param string $name
	 * @return array
	 * @throws Exception_Key
	 */
	public function meta(string $name): array {
		if (array_key_exists($name, $this->meta)) {
			return $this->meta[$name];
		}

		throw new Exception_Key('No meta tag with key "{key}"', ['key' => $name]);
	}

	/**
	 * Get meta tag(s)
	 *
	 * @param string $name
	 * @return string
	 * @throws Exception_Key
	 */
	public function metaContent(string $name): string {
		$meta = $this->meta($name);
		if (array_key_exists('content', $meta)) {
			return implode('', toList($meta['content']));
		}

		throw new Exception_Key('Meta tag "{key}" has no content', ['key' => $name]);
	}

	/**
	 * Get/set meta tags
	 *
	 * @param string $name
	 * @param string|array $content
	 * @return Response
	 */
	public function setMeta(string $name, string|array $content): Response {
		$this->meta[$name] = is_array($content) ? $content : [
			'name' => $name, 'content' => $content,
		];
		return $this->parent;
	}

	/**
	 * Get/Set shortcut icon
	 *
	 * @param ?string $path
	 * @return Response|string
	 * @throws Exception_Semantics
	 */
	public function shortcut_icon(string $path = null): string|Response {
		return $path === null ? $this->shortcutIcon() : $this->setShortcutIcon($path);
	}

	/**
	 * Get shortcut icon
	 *
	 * @return string
	 */
	public function shortcutIcon(): string {
		$result = $this->link('shortcut icon');
		return $result['href'] ?? '';
	}

	/**
	 * Set shortcut icon
	 *
	 * @param string $path
	 * @return Response
	 * @throws Exception_Semantics
	 */
	public function setShortcutIcon(string $path): Response {
		$attrs = [];
		$type = MIME::from_filename($path);
		$this->setLink('shortcut icon', $path, $type, $attrs);
		$this->setLink('icon', $path, $type, $attrs);
		return $this->parent;
	}

	/**
	 * @param string $rel
	 * @return array
	 */
	public function link(string $rel): array {
		return ArrayTools::filter($this->links, $this->links_by_rel[$rel] ?? []);
	}

	/**
	 * Get/set a link tag in the header
	 *
	 * @param string $rel
	 *            Link rel=""
	 * @param string $path
	 * @param string $type
	 * @param array $attrs
	 * @return Response
	 * @throws Exception_Semantics
	 */
	public function setLink(string $rel, string $path, string $type = '', array $attrs = []): Response {
		if (!array_key_exists('weight', $attrs)) {
			$attrs['weight'] = count($this->links) * 10;
		}
		$arr = [
			'rel' => $rel, 'href' => HTMLTools::href($this->application, $path),
		];
		if ($type) {
			$arr['type'] = $type;
		}
		$share = $attrs['share'] ?? false;
		if (!$share && $this->parent->optionBool('require_root_dir') && !array_key_exists('root_dir', $attrs)) {
			throw new Exception_Semantics('{path} requires a root_dir specified', compact('rel', 'path'));
		}
		ArrayTools::append($this->links_by_rel, $rel, $path);
		$this->links[$path] = $arr + $attrs;
		$this->links_sorted = [];
		return $this->parent;
	}

	/**
	 * Add a css to the page
	 *
	 * @param string $path
	 *            Path to css file
	 * @param array|string $options
	 *            Optional options:
	 *                media (defaults to all)
	 *                type (defults to text/css)
	 *                browser (may be ie, ie6, ie7)
	 *                root_dir for files which need to be found
	 *                share bool for share files
	 *
	 * @return self
	 * @throws Exception_Semantics
	 */
	public function css(string $path, array|string $options = []): self {
		if (is_string($options)) {
			$options = [
				'media' => $options,
			];
		}
		$options += [
			'type' => 'text/css', 'media' => 'all',
		];
		return $this->setLink('stylesheet', $path, $options['type'], $options);
	}

	/**
	 * Add a css to the page
	 *
	 * @param string $styles CSS code
	 * @param array|string $options Media type or tag options
	 *            Optional options: media (defaults to screen), type (defults to text/css), browser
	 *            (may be ie, ie6, ie7), and cdn (boolean to prefix with cdn path)
	 * @return Response
	 */
	public function cssInline(string $styles, array|string $options = []): Response {
		if (is_string($options)) {
			$options = [
				'media' => $options,
			];
		}
		$options = is_array($options) ? ArrayTools::listTrimClean($options) : [];
		$options += [
			'type' => 'text/css',
		];
		$options['content'] = $styles;
		$this->styles[md5($styles)] = $options;
		return $this->parent;
	}

	/**
	 * Set the page theme to use to render the final HTML output
	 *
	 * @return string
	 */
	public function pageTheme(): string {
		return $this->page_theme;
	}

	/**
	 * Set the page theme to use to render the final HTML output
	 *
	 * @param string $set
	 * @return Response
	 */
	public function setPageTheme(string $set): Response {
		$this->page_theme = $set;
		return $this->parent;
	}

	/**
	 *
	 * @return array
	 */
	public function themeVariables(): array {
		return [
			'page_theme' => $this->page_theme, 'request' => $this->parent->request, 'response' => $this->parent,
		];
	}

	/**
	 *
	 */
	public function scripts(): array {
		return $this->scriptTags();
	}

	/**
	 *
	 */
	public function links(): array {
		return $this->linkTags($this->linkOptions());
	}

	/**
	 *
	 * @return array
	 */
	public function metas(): array {
		return $this->meta;
	}

	/**
	 *
	 * @return array[]
	 */
	public function styles(): array {
		return $this->styles;
	}

	/**
	 * Retrieve link tags in unrendered form for output via JSON or other mechanism
	 *
	 * @param array $options
	 * @return array
	 */
	private function linkTags(array $options = []): array {
		$result = [];
		$stylesheets_inline = toBool(avalue($options, 'stylesheets_inline'));
		if (count($this->links_sorted) !== count($this->links)) {
			$this->links_sorted = $this->links;
			usort($this->links_sorted, 'zesk_sort_weight_array');
		}
		$cache_links = $this->parent->optionBool('cache_links', false);
		$cached_media = [];
		$this->links_sorted = $this->parent->callHookArguments('links_preprocess', [
			$this->links_sorted,
		], $this->links_sorted);
		foreach ($this->links_sorted as $attrs) {
			$tag = $this->browserConditionals(strval($attrs['browser'] ?? ''));

			$rel = $attrs ['rel'] ?? '';
			if ($stylesheets_inline && $rel === 'stylesheet') {
				$dest = $this->resourcePath($attrs['href'], $attrs);
				if (empty($dest) || !is_file($dest)) {
					$this->application->logger->error('Inline stylesheet path {dest} not found: {attributes}', [
						'dest' => $dest, 'attributes' => serialize($attrs),
					]);
				} else {
					$tag['name'] = 'style';
					$tag['attributes'] = [
						'type' => 'text/css',
					] + ArrayTools::filter($attrs, 'media');
					$tag['content'] = file_get_contents($dest);
					$result[] = $tag;
				}
			} else {
				assert(array_key_exists('href', $attrs));
				$media = strval($attrs ['media'] ?? 'screen');
				[$href, $file_path] = $this->resourceDate($attrs['href'], $attrs);
				if ($href) {
					$attrs['file_path'] = $file_path;
					$attrs['href_original'] = $attrs['href'];
					$attrs['href'] = $href;
					$attrs = $this->parent->callHookArguments('link_process', [
						$attrs,
					], $attrs);
					// Only cache and group stylesheets, for now.
					if ($rel === 'stylesheet' && $cache_links && $file_path && !avalue($attrs, 'nocache')) {
						$cached_media[$media][$href] = $file_path;
						continue;
					}
				} else {
					$this->application->logger->error('Unable to find {href} in {root_dir}', $attrs);

					continue;
				}
				$tag += [
					'name' => 'link',
					'attributes' => ArrayTools::filter($attrs, 'rel;href;type;media;sizes;crossorigin;hrefland;rev'),
					'content' => '',
				];
				$result[] = $tag;
			}
		}
		if ($cache_links && count($cached_media) > 0) {
			foreach ($cached_media as $media => $cached) {
				array_unshift($result, $this->resourceCacheCSS($cached, $media));
			}
		}
		return $result;
	}

	/**
	 *
	 * @return array
	 */
	private function linkOptions(): array {
		return $this->parent->options([
			'stylesheets_inline',
		]);
	}

	/**
	 * Render the HTML page
	 *
	 * @param string $content Page body content
	 * @return string HTML page
	 * @throws Exception_Redirect
	 */
	public function render(string $content): string {
		return $this->application->theme($this->page_theme, [
			'content' => $content,
		] + $this->themeVariables());
	}

	/**
	 * @param string $content
	 * @return void
	 * @throws Exception_Redirect
	 */
	public function output(string $content): void {
		echo $this->render($content);
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
	public function toJSON(): array {
		$script_tags = $this->scriptTags(false);
		$scripts = [];
		$ready = $this->jqueryReady();
		foreach ($script_tags as $tag) {
			$prefix = $suffix = $name = $content = $attributes = null;
			extract($tag, EXTR_IF_EXISTS);
			if ($name !== 'script') {
				continue;
			}
			$attributes = toArray($attributes);
			if (array_key_exists('src', $attributes)) {
				$scripts[] = $attributes['src'];
			} elseif (!empty($content)) {
				$ready[] = $content;
			}
		}
		$link_tags = $this->linkTags();
		$stylesheets = [];
		$head_tags = [];
		foreach ($link_tags as $tag) {
			if ($tag['name'] === 'link' && apath($tag, 'attributes.rel') === 'stylesheet') {
				$stylesheets[] = $tag;
			} else {
				$head_tags[] = $tag;
			}
		}
		return ArrayTools::clean([
			'elapsed' => microtime(true) - $this->application->initializationTime(),
			'scripts' => count($scripts) ? $scripts : null, 'stylesheets' => count($stylesheets) ? $stylesheets : null,
			'head_tags' => count($head_tags) ? $head_tags : null, 'ready' => count($ready) ? $ready : null,
			'title' => $this->parent->setTitle(),
		], null);
	}

	/**
	 *
	 * @param string $resource_path
	 * @param int $route_expire
	 * @return string
	 */
	private function resourcePathRoute(string $resource_path, int $route_expire = 0): string {
		$path = $this->application->cachePath([
			'resources', $resource_path,
		]);
		if (file_exists($path)) {
			if (!$route_expire) {
				return $path;
			}
			$mtime = filemtime($path);
			if ($mtime + $route_expire > time()) {
				return $path;
			}
		}
		Directory::depend(dirname($path), 0o770);

		try {
			$content = $this->application->content($resource_path);
			file_put_contents($path, $content);
		} catch (Exception_NotFound $e) {
			return '';
		}
		return $path;
	}

	/**
	 * Given a path, retrieve the actual resource path with a timestamp for cache busting
	 *
	 * @param string $_path
	 * @param array $attributes
	 * @return string Empty string if something is awry
	 */
	protected function resourcePath(string $_path, array $attributes): string {
		$debug = toBool($options['debug'] ?? false);
		$share = toBool($options['share'] ?? false);
		$is_route = toBool($options['is_route'] ?? false);
		$root_dir = strval($options['root_dir'] ?? '');
		$defaultRouteExpire = $this->parent->option('resource_path_route_expire', 600);
		$route_expire = intval($attributes['route_expire'] ?? $defaultRouteExpire);
		if ($root_dir) {
			if ($debug) {
				$this->application->logger->debug('rootdir (' . JSONTools::encode($root_dir) . ") check $_path");
			}
			return HTMLTools::href($this->application, path($root_dir, $_path));
		} elseif ($share) {
			if ($debug) {
				$this->application->logger->debug("share check $_path");
			}
			return Controller_Share::realpath($this->application, $_path);
		} elseif ($is_route) {
			if ($debug) {
				$this->application->logger->debug("route check $_path");
			}
			return $this->resourcePathRoute($_path, $route_expire);
		} else {
			return '';
		}
	}

	/**
	 * Utility function to determine the date of a file
	 *
	 * @param string $path
	 * @param array $attributes
	 *            Passed to resource_path
	 * @return array First item is the URI, 2nd is the full path to the file
	 */
	protected function resourceDate(string $path, array $attributes): array {
		$query = [];
		$file = $this->resourcePath($path, $attributes);
		if (!$file || !is_file($file)) {
			$this->application->logger->warning('Resource {path} not found at {file}', [
				'path' => $path, 'file' => $file,
			]);
			return [
				$path, null,
			];
		}
		$query['_ver'] = date('YmdHis', filemtime($file));
		return [
			URL::queryFormat($path, $query), $file,
		];
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
	protected function processCachedCSS(string $src, string $file, string $dest, string $contents): string {
		$matches = null;
		$contents = preg_replace('|/\*.+?\*/|m', '', $contents);
		$contents = preg_replace('|\s+|', ' ', $contents);
		$contents = strtr($contents, [
			': ' => ':', '; ' => ';',
		]);

		if (preg_match_all('|@import\s*([^;]+)\s*;|', $contents, $matches, PREG_SET_ORDER)) {
			$map = [];
			foreach ($matches as $match) {
				$import = trim($match[1]);
				$imatch = null;
				if (preg_match('|^"[^"]+"$|', $import) || preg_match('|^\'[^\']+\'$|', $import)) {
					$import = unquote($import);
				} elseif (preg_match('|^url\(([^)]+)\)$|', $import, $imatch)) {
					$import = unquote($imatch[1]);
					if (URL::valid($import) || $import[0] === '/') {
						continue;
					}
				} else {
					$this->application->logger->debug('Unknown @import syntax in {file}: {import}', [
						'file' => $file, 'import' => $match[0],
					]);

					continue;
				}
				$import_src = path(dirname($src), $import);
				$import_file = path(dirname($file), $import);
				$import_contents = file_get_contents($import_file);
				$import_replace = $this->processCachedCSS($import_src, $import_file, $dest, $import_contents);
				$map[$match[0]] = $import_replace;
			}
			$contents = strtr($contents, $map);
		}
		if (!preg_match_all('|url\(([^)]+)\)|', $contents, $matches, PREG_SET_ORDER)) {
			return $contents;
		}
		$map = [];
		foreach ($matches as $match) {
			[$full_match, $rel_image] = $match;
			$rel_image = unquote($rel_image);
			if (URL::valid($rel_image) || StringTools::begins($rel_image, [
				'/', 'data:',
			])) {
				continue;
			}
			$rel_image = explode('/', $rel_image);
			$src_dir = explode('/', dirname($src));
			while ($rel_image[0] === '..') {
				array_shift($rel_image);
				array_pop($src_dir);
			}
			$src_dir = implode('/', $src_dir);
			$rel_image = implode('/', $rel_image);
			$new_href = path($src_dir, $rel_image);
			$this->application->logger->debug("process_cached_css: $file: $rel_image => $new_href");
			$map[$full_match] = strtr($full_match, [
				$match[1] => '"' . $new_href . '"',
			]);
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
	 * @see HTML::processCachedCSS
	 */
	private function process_cached_type(string $src, string $file, string $dest, string $extension): string {
		$method = "process_cached_$extension";
		$contents = file_get_contents($file);
		if (method_exists($this, $method)) {
			$contents = $this->$method($src, $file, $dest, $contents);
		}
		$contents = $this->parent->callHookArguments($method, [
			$src, $file, $dest, $contents,
		], $contents);
		return $contents;
	}

	/**
	 * Cache files in the resource paths tend to grow, particularly
	 * @param string $extension
	 * @todo does this depend on a certain structure which is enforced by this?
	 */
	public function clean_resource_caches(string $extension = '') {
		$path = $this->resourceCachePath($extension);
		$files = Directory::list_recursive($path, [
			'rules_directory_walk' => [
				'#/cache/js#' => true, '#/cache/css#' => true, false,
			], 'add_path' => true,
		]);
		$expire_seconds = abs($this->parent->optionInt('resource_cache_lifetime_seconds', 3600)); // 1 hour
		$now = time();
		$modified_after = $now - $expire_seconds;
		$deleted = [];

		foreach (File::deleteModifiedBefore($files, $modified_after) as $file => $result) {
			if (is_array($result) && array_key_exists('deleted', $result)) {
				$this->application->logger->debug('Deleting old file {file} modified on {when}, more than {delta} seconds ago', $result);
				$deleted[] = $file;
			}
		}

		$this->application->logger->notice('Deleted {deleted} files from cache directory at {path} (Expire after {expire_seconds} seconds)', [
			'deleted' => count($deleted), 'path' => $path, 'expire_seconds' => $expire_seconds,
		]);
		return $deleted;
	}

	/**
	 * Return the href for our resource cache
	 *
	 * @param string $extension
	 * @param string $filename
	 * @return string
	 */
	private function resourceCacheHREF(string $extension = '', string $filename = ''): string {
		$segments[] = '/cache';
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
	private function resourceCachePath(string $extension = '', string $filename = '') {
		$href = $this->resourceCacheHREF($extension, $filename);
		$cache_path = path($this->application->documentRoot(), $href);
		return $cache_path;
	}

	/**
	 * Run JavaScript/CSS concatenation
	 *
	 * @param array $cached
	 * @param string $extension
	 * @param string $hook
	 * @param string $debug
	 * @return string
	 * @throws Exception_Directory_Create
	 * @throws Exception_Directory_Permission
	 * @throws Exception_File_NotFound
	 */
	private function resourceCache(array $cached, string $extension, string $hook, string &$debug = ''): string {
		$debug = [];
		$hash = [];
		foreach ($cached as $key => $value) {
			if (is_numeric($key)) {
				$hash[] = $value;
			} else {
				$hash[] = $key;
				$debug[] = $key;
			}
		}
		if ($this->parent->optionBool('debug_resource_cache')) {
			file_put_contents($this->application->path('/resource_cache-' . date('Y-m-d-H-i-s') . '.txt'), implode("\n", $hash));
		}
		$hash = md5(implode('|', $hash));
		$href = $this->resourceCacheHREF($extension, "$hash.$extension");
		$cache_path = $this->resourceCachePath($extension, "$hash.$extension");
		if (!file_exists($cache_path)) {
			Directory::depend(dirname($cache_path), 0o770);
			$content = '';
			$sources = [];
			foreach ($cached as $src => $mixed) {
				if (is_numeric($src)) {
					// $mixed is JavaScript code/insertion string
					$content .= "$mixed\n";
				} else {
					// $mixed is filename
					$content .= "/* Source: $src */\n" . $this->process_cached_type($src, $mixed, $href, $extension) . "\n";
					$sources[] = $src;
				}
			}
			$content = $this->parent->callHook($hook, $content);
			$this->application->logger->info('Created {cache_path} from {sources}', [
				'cache_path' => $cache_path, 'sources' => $sources,
			]);
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
	 * @throws Exception_Directory_Create
	 * @throws Exception_Directory_Permission
	 * @throws Exception_File_NotFound
	 */
	private function resourceCacheCSS(array $cached, $media = 'screen'): array {
		$debug = '';
		$href = $this->resourceCache($cached, 'css', 'compress_css', $debug);
		return [
			'name' => 'link', 'attributes' => [
				'href' => $href, 'rel' => 'stylesheet', 'media' => $media,
			], 'content' => '', 'suffix' => $this->application->development() ? "<!--\n$debug\n-->" : '',
		];
	}

	/**
	 * Run Script caching
	 *
	 * @param array $cached
	 * @return array
	 */
	private function resourceCacheScripts(array $cached): array {
		$debug = '';
		$href = $this->resourceCache($cached, 'js', 'compress_script', $debug);
		return [
			'name' => 'script', 'attributes' => [
				'src' => $href, 'type' => 'text/javascript',
			], 'content' => '', 'suffix' => $this->application->development() ? "<!--\n$debug\n-->" : '',
		];
	}

	/**
	 * Output jQuery ready tags
	 *
	 * @return array
	 */
	public function jqueryReady(): array {
		if (count($this->jquery) === 0) {
			return [];
		}
		$result = [];
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
	 * @return array
	 */
	private function scriptTags(bool $cache_scripts = null): array {
		// Sort them by weight if they're not sorted
		if (!$this->scripts_sorted) {
			uasort($this->scripts, 'zesk_sort_weight_array');
			$this->scripts_sorted = true;
		}
		if ($cache_scripts === null) {
			$cache_scripts = $this->parent->optionBool('cache_scripts', false);
		}
		$cached = $cached_append = [];
		$result = [];
		/* Output scripts */
		$selected_attributes = ['src', 'type', 'async', 'defer', 'id'];
		if ($this->parent->optionBool('debug_weight')) {
			$selected_attributes[] = 'weight';
		}
		foreach ($this->scripts as $attrs) {
			$script_attributes = ArrayTools::filter($attrs, $selected_attributes) + HTMLTools::data_attributes($attrs);
			if (array_key_exists('callback', $attrs)) {
				$attrs['content'] = call_user_func($attrs['callback']);
			}
			$script = $this->browserConditionals(avalue($attrs, 'browser'));
			if (array_key_exists('content', $attrs)) {
				$script += [
					'name' => 'script', 'attributes' => $script_attributes, 'content' => $attrs['content'],
				];
			} else {
				assert(array_key_exists('src', $attrs));
				if ($attrs['nocache'] ?? false) {
					$resource_path = URL::queryAppend($attrs['src'], [
						$this->parent->option('scripts_nocache_variable', '_r') => md5(microtime()),
					]);
					$script_attributes['src'] = $resource_path;
				} elseif (URL::valid($attrs['src'])) {
					$script_attributes['src'] = $attrs['src'];
				} else {
					[$resource_path, $file_path] = $this->resourceDate($attrs['src'], $attrs);
					if ($resource_path) {
						$script_attributes['src'] = $resource_path;
						if ($cache_scripts && $file_path) {
							if (array_key_exists('javascript_before', $attrs)) {
								$cached[] = $attrs['javascript_before'];
							}
							$cached[$resource_path] = $file_path;
							$cached_append[] = 'zesk.scripts_cached(' . JSONTools::encode($resource_path) . ');';
							if (array_key_exists('javascript_after', $attrs)) {
								$cached[] = $attrs['javascript_after'];
							}

							continue;
						}
					}
				}
				$script += [
					'name' => 'script', 'attributes' => $script_attributes, 'content' => '',
				];
			}
			if (array_key_exists('javascript_before', $attrs)) {
				$result[] = [
					'name' => 'script', 'type' => 'text/javascript', 'content' => $attrs['javascript_before'],
				];
			}
			$result[] = $script;
			if (array_key_exists('javascript_after', $attrs)) {
				$result[] = [
					'name' => 'script', 'type' => 'text/javascript', 'content' => $attrs['javascript_after'],
				];
			}
		}
		if (count($cached) > 0) {
			$cached = array_merge($cached, $cached_append);
			$result = array_merge([
				$this->resourceCacheScripts($cached),
			], $result);
		}
		return $result;
	}

	/**
	 * @param string $pattern
	 * @param callable $method
	 * @return float|null
	 */
	private function findWeight(string $pattern, callable $method): ?float {
		$weight = null;
		foreach ($this->scripts as $path => $attributes) {
			if (str_contains($path, $pattern)) {
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
	 * @return Response
	 * @throws Exception_Semantics
	 */
	private function scriptAdd(string $path, array $options): Response {
		if (array_key_exists($path, $this->scripts)) {
			return $this->parent;
		}
		if (!array_key_exists('weight', $options)) {
			$before = null;
			if (array_key_exists('before', $options)) {
				if (($before = $this->findWeight($options['before'], 'min')) !== null) {
					$options['weight'] = $before - 1;
				}
			}
			if (array_key_exists('after', $options)) {
				if (($after = $this->findWeight($options['after'], 'max')) !== null) {
					if ($before !== null) {
						if ($after <= $before) {
							throw new Exception_Semantics('{path} has a computed {before} weight which is greater than the after weight {after}', [
								'path' => $path, 'before' => $before, 'after' => $after,
							]);
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
		$nocache = $options ['nocache'] ?? false;
		$share = $options['share'] ?? false;
		$content = array_key_exists('content', $options);
		$is_route = $options['is_route'] ?? false;
		$callback = array_key_exists('callback', $options);
		if (!$is_route && !$callback && !$content && !$share && !$nocache && $this->parent->optionBool('require_root_dir') && !array_key_exists('root_dir', $options)) {
			throw new Exception_Semantics('{path} requires a root_dir specified', compact('path'));
		}
		$options += [
			'type' => 'text/javascript',
		];
		$this->scripts[$path] = $options;
		$this->scripts_sorted = false;
		return $this->parent;
	}

	/**
	 * Add to JavaScript script settings
	 *
	 * @param array $settings
	 * @return $this
	 */
	final public function addJavascriptSettings(array $settings): self {
		$this->script_settings = ArrayTools::merge($this->script_settings, $settings);
		return $this->parent;
	}

	/**
	 * Add to JavaScript script settings
	 *
	 * @return array
	 */
	final public function javascriptSettings(): array {
		return $this->script_settings;
	}

	/**
	 * Return JavaScript code to load JavaScript settings
	 */
	public function _javascript_settings(): string {
		return '$.extend(true, window.zesk.settings, ' . JSONTools::encode($this->javascriptSettings()) . ');';
	}

	/**
	 * Register a javascript to be put on the page
	 *
	 * @param string|array $paths File path(s) to serve for the javascript
	 * @param array $options Optional settings: type (defaults to text/javascript), browser (defaults to all
	 *            browsers), cdn (defaults to false)
	 * @return Response
	 * @throws Exception_Semantics
	 */
	public function javascript(string|array $paths, array $options = []): Response {
		if (is_array($paths)) {
			foreach ($paths as $path) {
				$this->javascript($path, $options);
			}
			return $this->parent;
		}
		$options['src'] = $paths;
		return $this->scriptAdd($paths, $options);
	}

	/**
	 * Include JavaScript to be included inline in the page
	 *
	 * @param string $script
	 * @param string $options
	 * @return Response
	 * @throws Exception_Semantics
	 */
	public function inlineJavaScript(string $script, array $options = null): Response {
		$multiple = toBool($options['multiple'] ?? false);
		$id = array_key_exists('id', $options) ? $options['id'] : md5($script);
		if ($multiple) {
			$id = $id . '-' . count($this->scripts);
		}
		return $this->scriptAdd($id, [
			'content' => $script, 'browser' => $options['browser'] ?? null,
		] + $options);
	}

	/**
	 * Add jQuery to page and ensure it's initialized
	 */
	private function _jquery(): void {
		if ($this->_jquery_added) {
			return;
		}
		$this->_jquery_added = true;

		try {
			$this->javascript('/share/jquery/jquery.js', [
				'weight' => 'zesk-first', 'share' => true,
			]);
			$this->javascript('/share/zesk/js/zesk.js', [
				'weight' => 'first', 'share' => true,
			]);
			$this->scriptAdd('settings', [
				'callback' => [
					$this, '_javascript_settings',
				], 'weight' => 'last',
			]);
		} catch (Exception_Semantics $e) {
			PHP::log($e);
			$this->application->logger->critical($e);
		}
		$this->jquery = [];
	}

	/**
	 * Require jQuery on the page, and optionally add a ready script
	 *
	 * @param string|array $add_ready_script
	 * @param int $weight
	 * @return Response
	 */
	public function jquery(string|array $add_ready_script = '', int $weight = 0): Response {
		$this->_jquery();
		if (is_array($add_ready_script)) {
			foreach ($add_ready_script as $add) {
				$this->jquery($add, $weight);
			}
		} elseif (is_string($add_ready_script)) {
			$hash = md5($add_ready_script);
			$this->jquery[$weight][$hash] = $add_ready_script;
		}
		return $this->parent;
	}

	/**
	 * Internal function to conditionally wrap script tags with browser conditions.
	 *
	 * Notice how some companies *need* to be special in this way.
	 *
	 * Avoid this like the plague, if possible. Unfortunately, crappy browser software may require
	 * it.
	 *
	 * @param string $browser Browser code
	 * @return array($prefix, $suffix)
	 */
	private function browserConditionals(string $browser = ''): array {
		if (!$browser) {
			return [];
		}
		switch (strtolower($browser)) {
			case 'ie':
				$prefix = '<!--[if IE]>';
				$suffix = '<![endif]-->';

				break;
			case 'ie6':
				$prefix = '<!--[if lte IE 6]>';
				$suffix = '<![endif]-->';

				break;
			case 'ie7':
				$prefix = '<!--[if IE 7]>';
				$suffix = '<![endif]-->';

				break;
			case 'ie8':
				$prefix = '<!--[if IE 8]>';
				$suffix = '<![endif]-->';

				break;
			case 'ie9':
				$prefix = '<!--[if IE 9]>';
				$suffix = '<![endif]-->';

				break;
			default:
				return [];
		}
		return [
			'nocache' => true, 'prefix' => $prefix, 'suffix' => $suffix,
		];
	}

	/**
	 * Get/set body attributes
	 *
	 * @param string|array|null $add
	 * @param mixed $value
	 * @return array|Response
	 * @deprecated 2022-11
	 */
	final public function body_attributes(array|string $add = null, mixed $value = null): array|Response {
		$this->application->deprecated(__METHOD__);
		if (is_array($add)) {
			return $this->setBodyAttributes($add, true);
		} elseif (is_string($add)) {
			return $this->setBodyAttributes([$add => $value], true);
		}
		return $this->bodyAttributes();
	}

	/**
	 * Add to JavaScript script settings
	 * @param array $settings
	 * @deprecated 2022-12
	 */
	final public function javascript_settings(array $settings = null): self {
		$this->application->deprecated(__METHOD__);
		if ($settings === null) {
			return $this->script_settings;
		}
		$this->script_settings = ArrayTools::merge($this->script_settings, $settings);
		return $this->parent;
	}
}
