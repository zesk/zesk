<?php
class Module_Image_Picker extends Module_JSLib {
	protected $css_paths = array(
		'/share/image_picker/css/image_picker.css'
	);
	protected $javascript_paths = array(
		'/share/image_picker/js/image_picker.js'
	);
	public function hook_routes(Router $router) {
		$router->add_route('imagepicker', array(
			'controller' => 'Image_Picker'
		));
		$router->add_route('imagepicker/image/{Content_Image image}/delete', array(
			'controller' => 'Image_Picker',
			'action' => 'image_delete',
			'arguments' => array(
				2
			)
		));
	}
	
	/**
	 */
	protected function hook_configured() {
		$module = Module::object('tinymce');
		if ($module) {
			/* @var $module Module_TinyMCE */
			$image_picker_button = array(
				'title' => __('Upload Image'),
				'image' => '/share/image_picker/image/image-upload.png',
				'*onclick' => 'function() { $.image_picker(editor); }'
			);
			$module->add_setup('editor.addButton("image_picker",' . json::encode($image_picker_button) . ");\n");
			$toolbar = $module->tinymce_toolbar();
			$toolbar = lists::remove($toolbar, 'image', ' ');
			$module->tinymce_toolbar("$toolbar image_picker image");
		}
	}
}