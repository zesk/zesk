<?php
namespace zesk\Tag;

use zesk\HTML;
use zesk\ORM\JSONWalker;
use zesk\JSON;

/* @var $this \zesk\Template */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \zesk\User */

/* @var $selection_type \zesk\Selection_Type */
/* Get our query to determine what tags are assigned */
$intermediate_class = "Tag_User";

$debug[] = str_repeat("\n", 20);

$debug[] = $this->tags_query;

$debug[] = "";
$debug[] = __FILE__;
$debug[] = _dump($this->labels_used);

$walker = new JSONWalker();
$json_labels = [];
foreach ($this->labels as $label) {
	/* @var $label zesk\Tag\Label */
	$id = $label->id();
	$struct = $label->json($walker);
	$struct['total'] = avalue($this->labels_used, $id, 0);
	$json_labels[] = $struct;
}

$divid = "tagwidget-" . $response->id_counter();
echo HTML::tag("div", [
	'id' => $divid,
	'class' => 'row tags-control',
], "<pre>$(\"#$divid\")</pre>");
$options['name'] = $this->name;
$options['value'] = $this->value;
$options['labels'] = $json_labels;
$options['badge']['separator'] = " " . HTML::tag("span", ".glyphicon .glyphicon-arrow-right", null) . " ";
$options['columns']['add']['button'] = HTML::tag("span", ".glyphicon .glyphicon-plus", null);
$options['columns']['remove']['button'] = HTML::tag("span", ".glyphicon .glyphicon-minus", null);
$options['total'] = $selection_type->count();

$response->html()->jquery("\$(\"#$divid\").tagsWidget(" . JSON::encode($options) . ")");

// $vars = array_keys($this->variables);
// sort($vars);
// $debug[] = "variables=\n\t" . implode("\n\t", $vars) . "\n";

$debug = [];
echo HTML::etag("pre", implode("\n", $debug));
