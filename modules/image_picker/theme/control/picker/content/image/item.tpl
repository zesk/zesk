<?php
$object = $this->object;
/* @var $object Content_Image */
$title = $object->title;
if (!$title) {
	$title = basename($object->path);
}
/* @var $request Request */
$request = $this->request;

$delete_url = $request->get_route('delete', $object);
echo html::div('.item', html::div('.image-picker-item', $this->object->render('view', array(
	'width' => 175,
	'height' => 175
))) . html::tag('label', $title) . html::tag('a', array(
	'class' => 'glyphicon glyphicon-remove action-delete',
	'href' => '/imagepicker/image/' . $object->id() . '/delete',
	'data-ajax' => true
), ''));
