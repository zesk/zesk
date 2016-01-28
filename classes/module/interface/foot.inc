<?php

interface Module_Interface_Foot {
	public function hook_foot(Request $request, Response_HTML $response, Template $template);
}