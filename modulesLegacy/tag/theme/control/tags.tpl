<?php declare(strict_types=1);
namespace zesk\Tag;

use zesk\HTML;
use zesk\JSON;
use zesk\ORM\JSONWalker;

/* @var $this \zesk\Template */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */

/* @var $selection_type \zesk\Selection_Type */
/* Get our query to determine what tags are assigned */
$intermediate_class = 'Tag_User';

$debug[] = str_repeat("\n", 20);

$debug[] = $this->tags_query;

$debug[] = '';
$debug[] = __FILE__;
$debug[] = _dump($this->labels_used);

$walker = new JSONWalker();
$json_labels = [];
foreach ($this->labels as $label) {
	/* @var $label Label */
	$id = $label->id();
	$struct = $label->json($walker);
	$struct['total'] = $this->labels_used[$id] ?? 0;
	$json_labels[] = $struct;
}

$divid = 'tagwidget-' . $response->id();
echo HTML::tag('div', [
	'id' => $divid,
	'class' => 'row tags-control',
], "<pre>$(\"#$divid\")</pre>");
$options['name'] = $this->action_form_element_name;
$options['value'] = $this->value;
$options['labels'] = $json_labels;
$options['badge']['separator'] = ' ' . HTML::tag('span', '.glyphicon .glyphicon-arrow-right', null) . ' ';
$options['columns']['add']['button'] = HTML::tag('span', '.glyphicon .glyphicon-plus', null);
$options['columns']['remove']['button'] = HTML::tag('span', '.glyphicon .glyphicon-minus', null);
$options['total'] = $selection_type->count();

$response->html()->jquery("\$(\"#$divid\").tagsWidget(" . JSON::encode($options) . ')');

// $vars = array_keys($this->variables);
// sort($vars);
// $debug[] = "variables=\n\t" . implode("\n\t", $vars) . "\n";

$debug = [];
echo HTML::etag('pre', implode("\n", $debug));
