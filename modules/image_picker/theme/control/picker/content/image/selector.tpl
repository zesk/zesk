<?php
/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \User */
$id = 'control-picker-' . $this->column . '-q';
?>
<div class="form-group control-text">
	<input class="input-lg required form-control" id="<?php echo $id ?>"
		name="q" type="text"
		placeholder="<?php echo __($this->label_search) ?>"
		data-picker-empty-search="1" />
</div>
<div class="dropfile image-picker-dropfile"
	data-dropfile-url="/image_picker/upload"><?php
	if ($this->request->user_agent_is('mobile')) {
		echo __('Tap here to upload a photo');
	} else {
		echo __('Drag images here to upload (or click)');
	}
	?></div>
<div
	class="control-picker-results class-<?php echo $this->object_class_css_class; ?>"><?php
	$query = $application->orm_registry('zesk\\User_Content_Image')
		->query_select()
		->link('zesk\\Content_Image', array(
		'alias' => 'ucimage',
	))
		->what_object('zesk\\Content_Image')
		->where('X.user', $this->current_user);
	//echo $query->__toString();
	$iterator = $query->orm_iterator('zesk\Content_Image');
	foreach ($iterator as $image) {
		echo $this->theme('control/picker/content/image/item', array(
			'object' => $image,
		));
	}
	?></div>
<?php
$response->response_data(array(
	'context_class' => 'modal-lg',
), true);

$response->jquery("\$('#$id').image_picker();");
