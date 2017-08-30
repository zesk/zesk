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
namespace zesk;

class Module_Content extends Module implements Interface_Module_Head {
	private static $all_classes = array(
		'zesk\Content_Article' => 'article',
		'zesk\Content_Data' => 'data',
		'zesk\Content_File' => 'file',
		'zesk\Content_Group' => 'group',
		'zesk\Content_Image' => 'image',
		'zesk\User_Content_Image' => 'image',
		'zesk\Content_Link' => 'link',
		'zesk\Content_Menu' => 'menu',
		'zesk\Content_Video' => 'video'
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
					$this->application->logger->warning("{method} Unknown content class type {type}", array(
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
	 * @param Response_Text_HTML $response
	 */
	public function hook_head(Request $request, Response_Text_HTML $response, Template $template) {
		$response->css("/share/content/css/content.css", array(
			"share" => true
		));
	}
	
	/**
	 * Register hooks
	 */
	public static function hooks(Kernel $zesk) {
		$zesk->hooks->add('zesk\Content_Image::stored', "zesk\Controller_Content_Cache::image_changed");
	}
}
