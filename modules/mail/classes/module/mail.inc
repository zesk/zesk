<?php
class Module_Mail extends Module {

	static function object_classes() {
		return array(
			'Mail_Message', 
			'Mail_Content', 
			'Mail_Header', 
			'Mail_Header_Type', 
			'Content_Data'
		);
	}
}