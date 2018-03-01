<?php
/**
 *
 */
use zesk\JSON;
use zesk\Lists;

/**
 *
 * @author kent
 *
 */
class Module_Image_Picker extends zesk\Module_JSLib {
	/**
	 *
	 * @var array
	 */
	protected $css_paths = array(
		'/share/image_picker/css/image_picker.css'
	);
	
	/**
	 *
	 * @var array
	 */
	protected $javascript_paths = array(
		'/share/image_picker/js/image_picker.js'
	);
	
	/**
	 *
	 * @param zesk\Router $router
	 */
	public function hook_routes(zesk\Router $router) {
		$router->add_route('imagepicker', array(
			'controller' => 'Image_Picker'
		));
		$router->add_route('imagepicker/image/{zesk\Content_Image image}/delete', array(
			'controller' => 'Image_Picker',
			'action' => 'image_delete',
			'arguments' => array(
				2
			)
		));
	}
	
	/**
	 *
	 */
	protected function hook_configured() {
		try {
			$module = $this->application->modules->object('tinymce');
			/* @var $module Module_TinyMCE */
			$image_picker_button = array(
				'title' => $this->application->locale->__('Upload Image'),
				'image' => '/share/image_picker/image/image-upload.png',
				'*onclick' => 'function() { $.image_picker(editor); }'
			);
			$module->add_setup('editor.addButton("image_picker",' . JSON::encode($image_picker_button) . ");\n");
			$toolbar = $module->tinymce_toolbar();
			$toolbar = lists::remove($toolbar, 'image', ' ');
			$module->tinymce_toolbar("$toolbar image_picker image");
		} catch (\zesk\Exception_NotFound $e) {
		}
	}
}
