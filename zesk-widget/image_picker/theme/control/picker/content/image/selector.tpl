<?php declare(strict_types=1);
use zesk\User_Content_Image;
use zesk\Content_Image;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \User */

$application->image_picker_module()->callHook('image_selector_before', $this);

$id = 'control-picker-' . $this->column . '-q';
?>
<div class="form-group control-text">
	<input class="input-lg required form-control" id="<?php
	echo $id;
?>"
		name="q" type="text"
		placeholder="<?php
	echo $locale->__($this->label_search)?>"
		data-picker-empty-search="1" />
</div>
<div class="dropfile image-picker-dropfile"
	data-dropfile-url="/image_picker/upload"><?php
if ($this->request->user_agent_is('mobile')) {
	echo $locale->__('Tap here to upload a photo');
} else {
	echo $locale->__('Drag images here to upload (or click)');
}
?></div>
<div
	class="control-picker-results class-<?php

echo $this->object_class_css_class;
?>"><?php
$query = $application->ormRegistry(User_Content_Image::class)
	->querySelect()
	->link(Content_Image::class, [
		'alias' => 'ucimage',
	])
	->ormWhat(Content_Image::class)
	->addWhere('X.user', $this->current_user);
//echo $query->__toString();
$iterator = $query->ormIterator(Content_Image::class);
foreach ($iterator as $image) {
	echo $this->theme('control/picker/content/image/item', [
		'object' => $image,
	]);
}
?></div>
<?php
$response->response_data([
	'context_class' => 'modal-lg',
], true);

$response->jquery("\$('#$id').image_picker();");

$application->image_picker_module()->callHook('image_selector_after', $this);
