<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage theme
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \zesk\User */
$response->title($title = $locale('Search results for &ldquo;{query}&rdquo;', [
	"query" => $this->query,
]));
echo HTML::tag('h1', $title);
echo HTML::div_open('.search-results');
echo $this->theme($this->get('theme_search_form', 'search/form'));

$none_found = [];
foreach ($this->results as $name => $result) {
	$name = strtr(strtolower($name), '_', '-');
	$total = $shown = $more_href = $noun = $more_text = $title = $content = null;
	extract($result, EXTR_IF_EXISTS);
	$__ = [
		'nouns' => $locale->plural($noun, $total),
		'shown' => $shown,
		'total' => $total,
	];
	if ($total > 0) {
		echo HTML::tag('h2', [
			'class' => 'found',
		], map($title . " ({total})", $__) . HTML::tag('a', [
			'name' => $name,
		], ""));
		if ($total === $shown) {
			echo HTML::tag('p', '.small', $locale('Showing {shown} matching {nouns}', $__));
			echo $content;
		} else {
			echo HTML::tag('p', '.small', $locale('Showing first {shown} of {total} matching {nouns}', $__));
			echo $content;
			echo HTML::tag('a', [
				'href' => $more_href,
				'class' => 'show-all',
			], $locale('Show all {total} matching {nouns}', $__));
		}
	} else {
		$none_found[] = HTML::tag('h2', '.not-found', $locale('No {nouns} found.', $__));
	}
}
echo implode("\n", $none_found);
echo HTML::div_close();
