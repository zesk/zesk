<?php
/**
 *
 */
namespace zesk;

/**
 * Add this to modules to enforce correct hook syntax for hook_head
 */
interface Interface_Module_Head {
	public function hook_head(Request $request, Response_Text_HTML $response, Template $template);
}

