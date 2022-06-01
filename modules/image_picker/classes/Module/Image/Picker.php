<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Module_Image_Picker extends Module_JSLib implements Interface_Module_Routes {
	/**
	 *
	 * @var array
	 */
	protected $css_paths = [
		'/share/image_picker/css/image_picker.css',
	];

	/**
	 *
	 * @var array
	 */
	protected $javascript_paths = [
		'/share/image_picker/js/image_picker.js',
	];

	/**
	 *
	 * @param zesk\Router $router
	 */
	public function hook_routes(Router $router): void {
		$router->add_route('imagepicker', [
			'controller' => Controller_Image_Picker::class,
		]);
		$router->add_route('imagepicker/image/{zesk\Content_Image image}/delete', [
			'controller' => Controller_Image_Picker::class,
			'action' => 'image_delete',
			'arguments' => [
				2,
			],
		]);
		$router->add_route('image_picker/{option action}', [
			'controller' => Controller_Image_Picker::class,
		]);
	}

	/**
	 *
	 */
	protected function hook_configured(): void {
		try {
			$module = $this->application->modules->object('tinymce');
			/* @var $module Module_TinyMCE */
			$image_picker_button = [
				'title' => $this->application->locale->__('Upload Image'),
				'image' => '/share/image_picker/image/image-upload.png',
				'*onclick' => 'function() { $.image_picker(editor); }',
			];
			$module->add_setup('editor.addButton("image_picker",' . JSON::encodex($image_picker_button) . ");\n");
			$toolbar = $module->tinymce_toolbar();
			$toolbar = Lists::keysRemove($toolbar, 'image', ' ');
			$module->tinymce_toolbar("$toolbar image_picker image");
		} catch (\zesk\Exception_NotFound $e) {
		}
	}
}
