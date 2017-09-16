<?php
namespace zesk;

class Module_Mail extends Module {
	protected $object_classes = array(
		'zesk\\Mail_Message',
		'zesk\\Mail_Content',
		'zesk\\Mail_Header',
		'zesk\\Mail_Header_Type',
		'zesk\\Content_Data',
		'zesk\\Content_File'
	);
}
