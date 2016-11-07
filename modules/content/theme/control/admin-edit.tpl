<?php
/**
 * 
 */
namespace zesk;

$object = $this->object;

if ($this->user && $this->user->can($object, "edit")) {
	$url = $this->router->get_route('edit', $object);
	if ($url) {
		echo HTML::div(".admin-edit", HTML::tag('a', array(
			'href' => $this->request->get_route('edit', $object)
		), HTML::cdn_img('/share/images/actions/edit.gif', __("Edit \"{0}\"", $object->Title))));
	} else {
		echo HTML::div(".admin-edit", HTML::tag('a', array(
			'href' => '#'
		), HTML::cdn_img('/share/images/actions/owl.gif', __("Editing is misconfigured \"{0}\"", $object->Title))));
	}
}
