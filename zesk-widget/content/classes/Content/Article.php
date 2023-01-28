<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk;

/**
 * Base class
 *
 * @see Class_Content_Article
 * @author kent
 */
class Content_Article extends ORMBase {
	/**
	 *
	 * @return string[][]|number[][]
	 */
	public function configuration_options() {
		return [
			'summary_maximum_length' => [
				'Type' => 'int',
				'Default' => 200,
				'Description' => 'Maximum length of summary',
			],
			'summary_maximum_length_from_body' => [
				'Type' => 'int',
				'Default' => 200,
				'Description' => 'Maximum length of summary when generated from body.',
			],
		];
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see ORMBase::store()
	 */
	public function store(): self {
		if (empty($this->parent)) {
			$this->parent = null;
		}
		if ($this->memberIsEmpty('slug')) {
			$this->setMember('slug', self::clean_code_name($this->member('title')));
		}
		return parent::store();
	}

	/**
	 *
	 * @param string $force
	 * @param string $clear
	 * @throws Exception_Unimplemented
	 * @return boolean
	 */
	public function reindex($force = false, $clear = true) {
		if (!$this->optionBool('search_active')) {
			return true;
		}

		throw new Exception_Unimplemented(__METHOD__);
	}

	/**
	 *
	 * @param unknown $contents
	 * @return array
	 */
	private static function extract_meta(&$contents) {
		$tags = HTML::extract_tags('meta', $contents);
		$meta = [];
		$fields = [];
		if (is_array($tags)) {
			foreach ($tags as $tag) {
				$name = strtolower($tag->option('Name', '-'));
				if (isset($meta[$name])) {
					$meta[$name] .= ' ' . $tag->option('Content');
				} else {
					$meta[$name] = $tag->option('Content');
				}
			}
			$contents = HTML::remove_tags('meta', $contents);
		}
		$temp = $meta['language'] ?? null;
		if ($temp) {
			$fields['Language'] = $temp;
		} else {
			$fields['Language'] = null;
		}
		if (count($meta) == 0) {
			return $fields;
		}
		$kw = $meta['keywords'] ?? null;
		if ($kw) {
			$fields['AutoKeywords'] = false;
			$fields['Keywords'] = $kw;
		}
		$description = $meta['description'] ?? null;
		if (!$description) {
			return $fields;
		}
		$fields['Summary'] = $description;
		return $fields;
	}

	private static function extract_title(&$contents, $default = null) {
		$title = HTML::extract_tag_contents('title', $contents);
		if ($title) {
			$contents = HTML::remove_tags('title', $contents);
		} else {
			$title = HTML::extract_tag_contents('h1', $contents);
			if ($title) {
				$contents = HTML::remove_tags('h1', $contents);
			} else {
				$title = $default;
			}
		}
		return $title;
	}

	public function register_content($name, $contents, $parent, $update = false) {
		$title = self::extract_title($contents, $name);
		$fields = self::extract_meta($contents);
		$body = HTML::extract_tag_contents('body', $contents);
		if (!$body) {
			$body = $contents;
		}
		$fields['Name'] = $title;
		$fields['CodeName'] = $name;
		$fields['Body'] = $body;
		if ($parent) {
			$fields['Categories'] = $parent;
		}
		$fields['CodeName'] = $name;
		$this->initialize($fields);
		return $this->register();
	}

	public function meta_keywords() {
		return $this->Keywords;
	}

	/**
	 *
	 * @param string $where
	 * @return string
	 */
	public static function where_publish_start_end($where = false) {
		$where['*PublishStart|<='] = [
			null,
			'NOW()',
		];
		$where['*PublishEnd|>='] = [
			null,
			'NOW()',
		];
		$where['IsActive'] = 'true';

		return $where;
	}

	/**
	 *
	 * @param string $where
	 * @return string
	 */
	public static function where_publish_start_end_NOT($where = false) {
		$where['*PublishEnd|<'] = 'NOW()';

		return $where;
	}

	/**
	 *
	 * @return string|void|mixed|string
	 */
	public function displayDate() {
		$dd = $this->DisplayDate;
		if (empty($dd)) {
			return '';
		}
		return $this->application->theme('view/date', [
			'value' => $dd,
			'format' => '{mmm} {ddd} ({delta})',
			'relative_min_unit' => 'day',
			'zero_string' => 'Today',
		]);
	}

	/**
	 *
	 * @return string
	 */
	public function homeTitle() {
		return $this->membere('Headline', $this->Title);
	}

	/**
	 *
	 */
	public function body() {
		return $this->Body;
	}

	/**
	 *
	 * @return unknown|string
	 */
	public function summary() {
		$result = $this->membere('Summary', '');
		if (!empty($result)) {
			return HTML::ellipsis($result, $this->optionInt('summary_maximum_length', -1));
		}
		$result = HTML::ellipsis($this->body(), $this->optionInt('summary_maximum_length_from_body', 200));
		return $result;
	}

	/**
	 *
	 * @param number $image_index
	 * @param string $options
	 * @return string
	 */
	public function articleImage($image_index = 0, $options = false) {
		return $this->image($image_index, $options);
	}

	/**
	 *
	 * @param unknown $global_prefix
	 * @param unknown $default_value
	 * @return number
	 */
	private function _compute_image_width($global_prefix, $default_value) {
		return $this->_compute_image_size('width', $global_prefix, $default_value);
	}

	/**
	 *
	 * @param unknown $global_prefix
	 * @param unknown $default_value
	 * @return number
	 */
	private function _compute_image_height($global_prefix, $default_value) {
		return $this->_compute_image_size('height', $global_prefix, $default_value);
	}

	/**
	 *
	 * @param unknown $name
	 * @param unknown $global_prefix
	 * @param unknown $default_value
	 * @return number
	 */
	private function _compute_image_size($name, $global_prefix, $default_value) {
		return $this->optionInt("image_${global_prefix}${name}_default", $this->application->configuration->getPath("Image::image_${global_prefix}${name}_default", $default_value));
	}

	/**
	 *
	 * @param number $image_index
	 * @param array $options
	 * @return string
	 */
	public function image($image_index = 0, $options = []) {
		$options['image_path'] = '/data/article';

		$member_prefix = ($options['is_thumb'] ?? null) ? 'Thumb' : '';
		$global_prefix = ($options['is_thumb'] ?? null) ? 'thumb_' : '';
		$default_value = ($options['is_thumb'] ?? null) ? 150 : 300;

		if (!array_key_exists('image_width', $options)) {
			$options['image_width'] = $this->memberInteger("Photo${member_prefix}Width$image_index", $this->_compute_image_width($global_prefix, $default_value));
		}
		if (!array_key_exists('image_height', $options)) {
			$options['image_height'] = $this->memberInteger("Photo${member_prefix}Height$image_index", $this->_compute_image_height($global_prefix, $default_value));
		}
		if (!array_key_exists('show_label', $options)) {
			$options['show_label'] = true;
		}
		$options['image_field'] = "Photo$image_index";
		$options['image_caption_field'] = "PhotoCaption$image_index";
		$options['is_relative'] = false;
		$options['root_directory'] = $this->application->documentRoot();

		return $this->theme('image/image-caption', $options);
	}
}
