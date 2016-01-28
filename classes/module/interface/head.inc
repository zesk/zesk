<?php

interface Module_Interface_Head {
	public function hook_head(Request $request, Response_HTML $response, Template $template);
}