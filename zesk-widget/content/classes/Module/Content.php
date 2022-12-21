<?php declare(strict_types=1);
/**
 * Content module
 *
 * @version $URL$
 * @package zesk
 * @subpackage content
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

use aws\classes\Module;

class Module_Content extends Module implements Interface_Module_Head {
	private static array $all_classes = [
		'zesk\Content_Article' => 'article',
		'zesk\Content_Data' => 'data',
		'zesk\Content_File' => 'file',
		'zesk\Content_Group' => 'group',
		'zesk\Content_Image' => 'image',
		'zesk\User_Content_Image' => 'image',
		'zesk\Content_Link' => 'link',
		'zesk\Content_Menu' => 'menu',
		'zesk\Content_Video' => 'video',
	];

	public array $modelClasses = [];

	/**
	 *
	 * {@inheritDoc}
	 * @see Module::initialize()
	 */
	public function initialize(): void {
		if ($this->hasOption('content_classes')) {
			$types = ArrayTools::valuesFlipAppend(self::$all_classes);
			$this->modelClasses = array_merge($this->modelClasses, $types['data']);
			foreach ($this->optionIterable('content_classes') as $type) {
				if (!array_key_exists($type, $types)) {
					$this->application->logger->warning('{method} Unknown content class type {type}', [
						'method' => __METHOD__,
						'type' => $type,
					]);

					continue;
				}
				$this->modelClasses = array_merge($this->modelClasses, $types[$type]);
			}
			$this->modelClasses = array_unique($this->modelClasses);
		} else {
			$this->modelClasses = array_keys(self::$all_classes);
		}
	}

	/**
	 *
	 * @param Request $request
	 * @param Response $response
	 * @param Template $template
	 */
	public function hook_head(Request $request, Response $response, Template $template): void {
		$response->css('/share/content/css/content.css', [
			'share' => true,
		]);
	}

	/**
	 * Register hooks
	 */
	public static function hooks(Application $zesk): void {
		$zesk->hooks->add(Content_Image::class . '::stored', Controller_Content_Cache::class . '::image_changed');
	}
}
