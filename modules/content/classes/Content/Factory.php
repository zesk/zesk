<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/content/classes/zesk/content/factory.inc $
 * @package zesk
 * @subpackage objects
 * @author kent
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 *            Created on Thu Sep 04 22:06:17 EDT 2008 22:06:17
 */
namespace zesk;

class Content_Factory {

	public static final function objects_parse($objects_string) {
		return HTML::parse_attributes($objects_string);
	}

	public final function content_instantiate($objects_mixed) {
		if (is_string($objects_mixed)) {
			$objects_ids = self::objects_parse($objects_mixed);
		} else if (is_array($objects_mixed)) {
			$objects_ids = $objects_mixed;
		} else {
			die("Incorrect type passed to instantateContent");
		}
		$objects = array();
		foreach ($objects_ids as $type => $mixed) {
			$object = $this->content_object($type, $mixed);
			if ($object !== null) {
				if ($object instanceof Object) {
					if (is_numeric($mixed)) {
						$object->initialize($mixed);
						if (!$object->fetch()) {
							$object = null;
						}
					} else if (is_string($mixed)) {
						if (!$object->fetch_by_key($mixed, $object->codeName())) {
							$object = null;
						}
					} else {
						$object = null;
					}
					$objects[$type] = $object;
				} else {
					$objects[$type] = $object;
				}
				if ($object) {
					$object->set("menu_type", $type);
					$object->set("menu_id", $mixed);
					$object->set("menu_object_id", "$type=$mixed");
				}
			}
		}
		return $objects;
	}

	public final function content_register(Content_Menu $menu, $objects_string) {
		$objects_strings = HTML::parse_attributes($objects_string);
		$new_code_name = array();
		foreach ($objects_strings as $k => $name) {
			$object = $this->content_object($k, $name);
			if ($object === null || !$object instanceof Object) {
				$new_code_name[$k] = $name;
				continue;
			}
			
			$name_name = $object->nameField();
			if (is_numeric($name)) {
				$object->initialize($name);
				if ($object->fetch()) {
					$new_code_name[$k] = $name;
					continue;
				}
				$name = $menu->Name;
				$objects_stringname = $menu->CodeName;
			} else if ($name === true) {
				$name = $menu->Name;
				$objects_stringname = $menu->CodeName;
			} else {
				$objects_stringname = $name;
			}
			$data = array(
				$name_name => $name, 
				"CodeName" => $objects_stringname
			);
			$object->initialize($data);
			$id = $object->register();
			if (empty($id)) {
				echo "$name $objects_stringname\n";
				dump($object);
				backtrace();
			}
			$new_code_name[$k] = $id;
		}
		return trim(HTML::attributes($new_code_name));
	}

	private static function _map_legacy_content_types($type) {
		static $map = array(
			"link" => "Content_Link", 
			"linkgroup" => "Content_Group_Link", 
			"article" => "Content_Article", 
			"articlegroup" => "Content_Group_Article", 
			"video" => "Content_Video", 
			"videogroup" => "Content_Group_Video"
		);
		return avalue($map, $type, "Content_" . $type);
	}

	/**
	 * Return an object
	 * @param string $type
	 * @return Object
	 */
	public function content_object($type, $value = null) {
		switch ($type) {
			case "template":
				$object = new Template($this->template_file($value));
				break;
			default:
				try {
					$try_type = self::_map_legacy_content_types($type);
					$object = $this->object_factory($try_type);
					return $object;
				} catch (Exception_Class_NotFound $e) {
					zesk()->logger->error("Unknown attribute code: [$type]");
					// backtrace();
					return null;
				}
				break;
		}
		return $object;
	}

	static function template_file($template) {
		return path('content/menu/layout', $template . ".tpl");
	}

	public function content_layout(array $options, $template) {
		$template_file = $this->template_file($template);
		return Template::instance($template_file, $options);
	}
}

