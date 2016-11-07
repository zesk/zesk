<?php
/**
 * 
 */
namespace zesk;

/**
 * Add this to modules to enforce correct hook syntax for hook_foot
 */
interface Interface_Module_Foot {
	public function hook_foot(Request $request, Response_Text_HTML $response, Template $template);
}
