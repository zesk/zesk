<?php
namespace zesk;

/* @var $response Response_Text_HTML */
$response->title($title = $locale('Search results for &ldquo;{query}&rdquo;', array(
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
		'nouns' => $locale->plural($noun, $total),
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
			echo HTML::tag('p', '.small', $locale('Showing {shown} matching {nouns}', $__));
			echo $content;
		} else {
			echo HTML::tag('p', '.small', $locale('Showing first {shown} of {total} matching {nouns}', $__));
			echo $content;
			echo HTML::tag('a', array(
				'href' => $more_href,
				'class' => 'show-all'
			), $locale('Show all {total} matching {nouns}', $__));
		}
	} else {
		$none_found[] = HTML::tag('h2', '.not-found', $locale('No {nouns} found.', $__));
	}
}
echo implode("\n", $none_found);
echo HTML::div_close();
