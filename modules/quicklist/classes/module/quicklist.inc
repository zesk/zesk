<?php
class Module_QuickList extends zesk\Module_JSLib /* implements zesk\Interface_Module_Head, zesk\Interface_Module_Foot, zesk\Interface_Module_Routes */
{
	protected $javascript_paths = array(
		"/share/quicklist/js/jquery.quicklist.js" => array(
			"share" => true
		)
	);
/**
 * List of associated classes
 *
 * @var array
 */
	/*
	 protected $classes = array();
	 */

/**
 * Override in subclasses - called upon Application::classes
 */
	/*
	 public function classes() {
	 return $this->classes;
	 }
	 */
	
	/*
	 public function initialize() {
	 parent::initialize();
	 }
	 */
	
	/*
	 protected function hook_foot(zesk\Request $request, zesk\Response_Text_HTML $response, zesk\Template $template) {
	 
	 }
	 */
	
	/*
	 protected function hook_head(zesk\Request $request, zesk\Response_Text_HTML $response, zesk\Template $template) {
	 
	 }
	 */
	
	/*
	 public function hook_routes(zesk\Router $router) {
	 
	 }
	 */
}
