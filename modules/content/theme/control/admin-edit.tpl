<?php
$object = $this->object;

if ($this->user && $this->user->can($object, "edit")) {
	$url = $this->request->get_route('edit', $object);
	if ($url) {
		echo html::div(".admin-edit", html::tag('a', array(
			'href' => $this->request->get_route('edit', $object)
		), html::cdn_img('/share/images/actions/edit.gif', __("Edit \"{0}\"", $object->Title))));
	} else {
		echo html::div(".admin-edit", html::tag('a', array(
			'href' => '#'
		), html::cdn_img('/share/images/actions/owl.gif', __("Editing is misconfigured \"{0}\"", $object->Title))));
	}
}
