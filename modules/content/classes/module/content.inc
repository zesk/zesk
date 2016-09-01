<?php
/**
 * Content module
 *
 * @version $URL$
 * @package zesk
 * @subpackage content
 * @author kent
 * @copyright &copy; 2014 Market Acumen, Inc.
 */
class Module_Content extends Module implements Module_Interface_Head {
	private static $all_classes = array(
		'Content_Article' => 'article',
		'Content_Data' => 'data',
		'Content_File' => 'file',
		'Content_Group' => 'group',
		'Content_Image' => 'image',
		'User_Content_Image' => 'image',
		'Content_Link' => 'link',
		'Content_Menu' => 'menu',
		'Content_Video' => 'video'
	);
	public $classes = array();
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Module::initialize()
	 */
	public function initialize() {
		if ($this->has_option("content_classes")) {
			$types = arr::flip_multiple(self::$all_classes);
			$this->classes = array_merge($this->classes, $types['data']);
			foreach ($this->option_list("content_classes") as $type) {
				if (!array_key_exists($type, $types)) {
					log::warning("{method} Unknown content class type {type}", array(
						"method" => __METHOD__,
						"type" => $type
					));
					continue;
				}
				$this->classes = array_merge($this->classes, $types[$type]);
			}
			$this->classes = array_unique($this->classes);
		} else {
			$this->classes = array_keys(self::$all_classes);
		}
	}
	
	/**
	 * 
	 * @param Request $request
	 * @param Response_HTML $response
	 */
	public function hook_head(Request $request, Response_HTML $response, Template $template) {
		$response->cdn_css("/share/content/css/content.css", array(
			"share" => true
		));
	}
	
	/**
	 * Register hooks
	 */
	public static function hooks(zesk\Kernel $zesk) {
		$zesk->hooks->add('Content_Image::stored', "Controller_Content_Cache::image_changed");
	}
}