<?php

/* @var $response zesk\Response_Text_HTML */
if (false) {
	$response = $this->response;
}
$response->title($title = __('Search results for &ldquo;{query}&rdquo;', array(
	"query" => $this->query
)));
echo HTML::tag('h1', $title);
echo HTML::div_open('.search-results');
echo $this->theme($this->get('theme_search_form', 'search/form'));

$none_found = array();
foreach ($this->results as $name => $result) {
	$name = strtr(strtolower($name), '_', '-');
	$total = $shown = $more_href = $noun = $more_text = $title = $content = null;
	extract($result, EXTR_IF_EXISTS);
	$__ = array(
		'nouns' => zesk\Locale::plural($noun, $total),
		'shown' => $shown,
		'total' => $total
	);
	if ($total > 0) {
		echo HTML::tag('h2', array(
			'class' => 'found'
		), map($title . " ({total})", $__) . HTML::tag('a', array(
			'name' => $name
		), ""));
		if ($total === $shown) {
			echo HTML::tag('p', '.small', __('Showing {shown} matching {nouns}', $__));
			echo $content;
		} else {
			echo HTML::tag('p', '.small', __('Showing first {shown} of {total} matching {nouns}', $__));
			echo $content;
			echo HTML::tag('a', array(
				'href' => $more_href,
				'class' => 'show-all'
			), __('Show all {total} matching {nouns}', $__));
		}
	} else {
		$none_found[] = HTML::tag('h2', '.not-found', __('No {nouns} found.', $__));
	}
}
echo implode("\n", $none_found);
echo HTML::div_close();
