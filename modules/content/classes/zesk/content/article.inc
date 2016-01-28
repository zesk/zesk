<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/content/classes/zesk/content/article.inc $
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2014, Market Acumen, Inc.
 */

/**
 * Base class
 *
 * @see Class_Zesk_Content_Article
 * @author kent
 */
class Zesk_Content_Article extends Object {

	function configuration_options() {
		return array(
			"summary_maximum_length" => array(
				"Type" => "int",
				"Default" => 200,
				"Description" => "Maximum length of summary"
			),
			"summary_maximum_length_from_body" => array(
				"Type" => "int",
				"Default" => 200,
				"Description" => "Maximum length of summary when generated from body."
			)
		);
	}

	function store() {
		if (empty($this->parent)) {
			$this->parent = null;
		}
		if ($this->member_is_empty('slug')) {
			$this->set_member("slug", self::clean_code_name($this->member("title")));
		}
		return parent::store();
	}

	public function reindex($force = false, $clear = true) {
		if (!zesk::getb("Article::search_active")) {
			return true;
		}

		//		require_once ZESK_ROOT . "code/modules/search/CSearchScope.inc";
		//		require_once ZESK_ROOT . "code/modules/search/CSearchIndex.inc";
		//
		//		if(!$this->AutoKeywords && !$force) {
		//			return true;
		//		}
		//		if($clear) {
		//			CSearchIndex::removeContent($this);
		//		}
		//		$content[] = "<html><head>";
		//		if(!$this->member_is_empty("Keywords")) {
		//			$content[] = '<meta name="keywords" content="' . htmlspecialchars($this->Keywords) . '" />';
		//		}
		//		if(!$this->member_is_empty("Summary")) {
		//			$content[] = '<meta name="description" content="' . CString::stripHTML($this->Summary) . '" />';
		//		}
		//		$content[] = "<title>".$this->title()."</title>";
		//		$content[] = "</head><body>";
		//		$content[] = $this->Body;
		//		$content[] = "</body><html>";
		//		$content = remove_vars_recurse(implode("\n",$content), "[^}]+");
		//
		//		$scope = CSearchScope::registerModelType($this->modelType());
		//		$result = CSearchIndex::registerContent($scope, $this, $this->Language, $content);
		//		if(!is_error($result, true)) {
		//			$this->AutoKeywords = true;
		//			parent::store();
		//		}
		//		return $result;
		return true;
	}

	/*====================================================================================*\
	* Article Parsing
	\*------------------------------------------------------------------------------------*/
	private static function extractMeta(&$contents) {
		$tags = html::extract_tags("meta", $contents);
		$meta = array();
		$fields = array();
		if (is_array($tags)) {
			foreach ($tags as $tag) {
				$name = strtolower($tag->option("Name", '-'));
				if (isset($meta[$name])) {
					$meta[$name] .= " " . $tag->option("Content");
				} else {
					$meta[$name] = $tag->option("Content");
				}
			}
			$contents = html::remove_tags("meta", $contents);
		}
		$temp = avalue($meta, "language");
		if ($temp) {
			$fields["Language"] = $temp;
		} else {
			$fields["Language"] = null; //$locale->languageObject();
		}
		if (count($meta) == 0)
			return $fields;
		$kw = avalue($meta, "keywords");
		if ($kw) {
			$fields["AutoKeywords"] = false;
			$fields["Keywords"] = $kw;
		}
		$description = avalue($meta, "description");
		if (!$description)
			return $fields;
		$fields["Summary"] = $description;
		return $fields;
	}

	private static function extractTitle(&$contents, $default = null) {
		$title = html::extract_tag_contents("title", $contents);
		if ($title) {
			$contents = html::remove_tags("title", $contents);
		} else {
			$title = html::extract_tag_contents("h1", $contents);
			if ($title) {
				$contents = html::remove_tags("h1", $contents);
			} else {
				$title = $default;
			}
		}
		return $title;
	}

	public function registerContent($name, $contents, $parent, $update = false) {
		$title = self::extractTitle($contents, $name);
		$fields = self::extractMeta($contents);
		$body = html::extract_tag_contents("body", $contents);
		if (!$body) {
			$body = $contents;
		}
		$fields["Name"] = $title;
		$fields["CodeName"] = $name;
		$fields["Body"] = $body;
		if ($parent) {
			$fields["Categories"] = $parent;
		}
		$fields["CodeName"] = $name;
		$this->initialize($fields);
		return $this->register();
	}

	public function metaKeywords() {
		//		if(CSystem::hasFeature("Module","Search"))
		//		{
		//			if($this->member_is_empty("Keywords")) {
		//				require_once ZESK_ROOT . "code/modules/search/CSearchIndex.inc";
		//				$locale = CLocale::master();
		//				$keys = CSearchIndex::contentKeywords($locale->languageObject(), $this);
		//				if(!is_array($keys))
		//				return '';
		//				return implode(", ", $keys);
		//			}
		//		}
		return $this->Keywords;
	}

	static function where_publish_start_end($where = false) {
		$where['*PublishStart|<='] = array(
			null,
			"NOW()"
		);
		$where['*PublishEnd|>='] = array(
			null,
			"NOW()"
		);
		$where['IsActive'] = 'true';

		return $where;
	}

	static function where_publish_start_end_NOT($where = false) {
		$where['*PublishEnd|<'] = "NOW()";

		return $where;
	}

	static function factory_control_publish_start_end(&$spec) {
		$f = widgets::control_date("PublishStart", "Begin publishing on", false);
		$f->set_option("EmptyString", "Immediate");
		$spec[$f->column()] = $f;

		$f = widgets::control_date("PublishEnd", "End publishing on", false);
		$f->set_option("EmptyString", "Forever");
		$spec[$f->column()] = $f;
	}

	function displayDate() {
		$dd = $this->DisplayDate;
		if (empty($dd))
			return "";
		return View_Date::format($dd, "{mmm} {ddd} ({delta})", array(
			'relative_min_unit' => 'day',
			'zero_string' => 'Today'
		));
	}

	function homeTitle() {
		return $this->membere("Headline", $this->Title);
	}

	function body() {
		return $this->Body;
	}

	function summary() {
		$result = $this->membere("Summary", "");
		if (!empty($result)) {
			return html::ellipsis($result, $this->option_integer('summary_maximum_length', -1));
		}
		$result = html::ellipsis($this->body(), $this->option_integer('summary_maximum_length_from_body', 200));
		return $result;
	}

	function articleImage($image_index = 0, $options = false) {
		return $this->image($image_index, $options);
	}

	function image($image_index = 0, $options = false) {
		$options['image_path'] = "/data/article";

		$member_prefix = (avalue($options, 'is_thumb')) ? "Thumb" : "";
		$global_prefix = (avalue($options, 'is_thumb')) ? "thumb_" : "";
		$default_value = (avalue($options, 'is_thumb')) ? 150 : 300;

		if (!array_key_exists('image_width', $options)) {
			$options['image_width'] = $this->memberInteger("Photo${member_prefix}Width$image_index", zesk::geti("Article::image_${global_prefix}width_default", zesk::geti("Image::image_${global_prefix}width_default", $default_value)));
		}
		if (!array_key_exists('image_height', $options)) {
			$options['image_height'] = $this->memberInteger("Photo${member_prefix}Height$image_index", zesk::geti("Article::image_${global_prefix}height_default", zesk::geti("Image::image_${global_prefix}height_default", $default_value)));
		}
		if (!array_key_exists('show_label', $options)) {
			$options['show_label'] = true;
		}
		$options['image_field'] = "Photo$image_index";
		$options['image_caption_field'] = "PhotoCaption$image_index";
		$options['is_relative'] = false;
		$options['root_directory'] = zesk::web_root();

		return $this->output('image/image-caption.tpl', $options);
	}
}

