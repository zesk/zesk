<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/content/classes/Content/Menu.php $
 * @package zesk
 * @subpackage default
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace zesk;

/**
 * @see Class_Content_Menu
 * @author kent
 *
 */
class Content_Menu extends ORM {
	function store() {
		$this->cleanCodeName();
		if ($this->Parent === 0 || $this->Parent === "0" || $this->Parent === "") {
			$this->Parent = null;
		}
		if ($this->CodeName === 'home') {
			$this->IsHome = 'true';
		}
		$is_home = $this->member_boolean('IsHome');
		$result = parent::store();
		if (!$result) {
			return $result;
		}
		if ($is_home) {
			$this->query_update()
				->value("IsHome", false)
				->where("IsHome", true)
				->where("ID|!=", $this->id())
				->execute();
		}
		$cc = $this->ContentObjects;
		$result = $this->register_content();
		if (!$result) {
			return null;
		}
		if ($cc === $this->ContentObjects) {
			return $this;
		}
		return parent::store();
	}
	/**
	 *
	 * @var array
	 */
	private static $menus = null;
	/**
	 *
	 * @var array
	 */
	private static $menus_content = null;

	/**
	 *
	 * @return array
	 */
	private function _menus() {
		if (is_array(self::$menus)) {
			return self::$menus;
		}

		$first = true;
		$parent_to_code = array();
		$menus = $this->query_select()->what("ID,Name,CodeName,Parent,ContentObjects,ContentTemplate,ContentLayout,IsActive,IsHome");
		$menus->order_by("Parent,OrderIndex");

		$result = array();
		$old_parent = -1;
		$menus_content = array();
		//$menus_content = array();
		foreach ($menus->iterator("ID") as $id => $menu) {
			$menu['IsActive'] = to_bool($menu['IsActive']);
			$menu['IsHome'] = $is_home = to_bool($menu['IsHome']);
			$parent = intval($menu['Parent']);
			$name = $menu['Name'];
			$menu_key = empty($menu['CodeName']) ? self::cleanName($name) : $menu['CodeName'];
			if ($old_parent !== $parent) {
				$first = true;
			}
			if ($parent === 0) {
				$k = $is_home ? "" : $menu_key;
				$result[0]["/$k"] = $menu;
				$parent_to_code[$id] = $k;
				$first = false;
				//$menus_content["/$k"] = $menu;
				if (!empty($menu['ContentObjects'])) {
					$menus_content["/$k"] = $menu;
				}
			} else {
				$k = ($first) ? "" : $menu_key;
				$suffix = ($first) ? "" : "/$k";
				$pkey = "/" . $parent_to_code[$parent];
				$result[$pkey][$k] = $menu;
				$first = false;
				$menus_content["$pkey$suffix"] = $menu;
			}
			$old_parent = $parent;
		}
		self::$menus = $result;
		self::$menus_content = $menus_content;
		return $result;
	}

	/**
	 * @return array
	 */
	private function _menus_content() {
		$this->_menus();
		return self::$menus_content;
	}

	/**
	 * Return first menu
	 *
	 * @return array
	 */
	function menu() {
		$menus = $this->_menus();
		return $menus[0];
	}

	/**
	 *
	 * @param mixed $x
	 * @return array
	 */
	function menu_children($x) {
		$menus = $this->_menus();
		return avalue($menus, $x);
	}

	/**
	 *
	 * @param Request $request
	 * @param unknown $x
	 * @return boolean
	 */
	static function menu_selected(Request $request, $x) {
		$uri = $request->path();
		if ($x === "/") {
			return ($uri === "/");
		}
		return StringTools::begins($uri, $x);
	}

	/**
	 *
	 * @param Request $request
	 * @param unknown $top
	 * @param unknown $sub
	 * @return boolean
	 */
	static function menu_child_selected(Request $request, $top, $sub) {
		$uri = $request->path();
		$uri = trim($uri, "/");
		if (!is_string($top)) {
			backtrace();
		}
		$top = trim($top, "/");
		$sub = trim($sub, "/");
		//
		// echo "[$top] [$sub] [$uri]<br />\n";
		//
		if ($top === "") {
			if ($sub === "") {
				return $uri === "";
			} else {
				return $uri === "/$sub";
			}
		} else {
			if ($sub === "") {
				return $uri === "$top";
			} else {
				return StringTools::begins("$uri/", "$top/$sub/");
			}
		}
	}

	/**
	 *
	 * @param unknown $uri
	 * @return mixed|array|NULL|unknown
	 */
	function menu_find($uri) {
		$content = $this->_menus_content();
		$uri = trim($uri, "/");
		$uri_parts = explode("/", $uri);
		if (count($uri_parts) === 1) {
			return avalue($content, "/$uri");
		}
		$uri = "/$uri/";
		$max_len = -1;
		$max_menu = null;
		foreach ($content as $k => $menu) {
			if (StringTools::begins($uri, "$k/")) {
				if ($max_len < 0 || strlen($k) > $max_len) {
					$max_len = strlen($k);
					$max_menu = $menu;
					$max_menu['MenuRemain'] = substr(rtrim($uri, '/'), strlen($k) + 1);
					$max_menu['URI'] = $k;
				}
			}
		}
		return $max_menu;
	}

	/**
	 *
	 * @param unknown $uri
	 * @param unknown $default
	 * @return string
	 */
	function menu_content($uri, $default = null) {
		$menu = $this->menu_find($uri);
		if (!is_array($menu)) {
			return $default;
		}
		return $this->layout($menu);
	}

	/**
	 *
	 * @param array $menu
	 * @param array $options
	 * @return string
	 */
	function layout(array $menu, array $options = array()) {
		$objects_string = $menu['ContentObjects'];
		$template = aevalue($menu, 'ContentTemplate', 'default');
		$layout_string = avalue($menu, 'ContentLayout');

		$layout = HTML::parse_attributes($layout_string);

		$factory = $this->content_factory();
		$objects_ids = $factory->objects_parse($objects_string);
		$objects = $factory->content_instantiate($objects_ids);

		$options['object_ids'] = $objects_ids;
		$options['objects'] = $objects;
		$options['layout'] = $layout;
		$options['menu'] = $menu;

		$content = $factory->content_layout($options, $template);

		return $content;
	}

	/**
	 *
	 * @return boolean
	 */
	function register_content() {
		$code = $this->ContentObjects;
		if (empty($code)) {
			return true;
		}
		$this->ContentObjects = $this->content_factory()->content_register($this, $code);

		return true;
	}
}

