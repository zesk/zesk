<?php
$description = _W(__($this->request->user_agent_is('mobile') ? "[Tap] to upload {noun}" : "[Drag and drop] {noun} to upload, or [click] to browse files.", array(
	"noun" => $this->get('noun', __('a file'))
)), '<strong>[]</strong>', '<strong>[]</strong>');
$description = $this->get("description", $description);

echo html::tag('div', '.dropfile-overlay', $description . html::span('.glyphicon .glyphicon-upload .big', '') . $this->theme('actions', array(
	'content' => $this->actions
)));
