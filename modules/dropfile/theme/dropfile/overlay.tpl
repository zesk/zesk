<?php declare(strict_types=1);
namespace zesk;

$description = HTML::wrap(__($this->request->user_agent_is('mobile') ? "[Tap] to upload {noun}" : "[Drag and drop] {noun} to upload, or [click] to browse files.", [
	"noun" => $this->get('noun', __('a file')),
]), '<strong>[]</strong>', '<strong>[]</strong>');
$description = $this->get("description", $description);

echo HTML::tag('div', '.dropfile-overlay', $description . HTML::span('.glyphicon .glyphicon-upload .big', '') . $this->theme('actions', [
	'content' => $this->actions,
]));
