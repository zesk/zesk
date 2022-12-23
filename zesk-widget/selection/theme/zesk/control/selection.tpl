<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $session \zesk\Session */
/* @var $request \zesk\Router */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \User */
$this->response->javascript('/share/selection/selection.js');

$name = $this->name;
$session = $this->session;
/* @var $session Interface_Session */

$plural_noun = $locale->plural($this->label, 2);
$singular_noun = $this->label;

$format = 'Control_Selection::selection_menu_title:={n} {nouns} selected';
$zero_format = 'Control_Selection::selection_menu_title_zero:=No {nouns} selected';

echo HTML::tag_open('div', [
	'class' => 'control-selection-widget form-group',
	'data-limit' => $this->limit,
	'data-total' => $this->total,
	'data-name' => $this->name,
	'data-format' => $locale($format),
	'data-zero-format' => $locale($zero_format),
	'data-noun' => $this->label,
	'data-container' => $this->container,
	'data-target' => $this->target,
	'data-count' => $this->count,
]);
$__ = [
	'noun' => $singular_noun,
	'nouns' => $plural_noun,
	'total' => $this->total,
	'nouns_total' => $locale->plural($singular_noun, $this->total),
];
?>
<div class="btn-group control-selection-menu">
	<button type="button" class="btn btn-default dropdown-toggle"
		data-toggle="dropdown">
		<?php
		echo HTML::span([
			'class' => 'title',
		], $locale($this->count === 0 ? $zero_format : $format, [
			'n' => $this->count,
			'nouns' => $locale->plural($this->label, $this->count),
		]));
?>
		<span class="caret"></span>
	</button>
	<?php
	echo HTML::tag_open('ul', [
		'class' => 'dropdown-menu',
		'role' => 'menu',
	]);
echo HTML::tag('li', [
	'role' => 'presentation',
	'class' => 'dropdown-header',
], $locale('Control_Selection::selection_menu_header:=Modify selection ...', $__));

$plural_noun = $locale->plural($this->label, 2);
foreach ([
	'none' => $locale('Control_Selection::clear_selection:=Clear selection', $__),
	'add-all' => $locale('Control_Selection::add_all:=Add {total} matching {nouns_total}', $__),
	'remove-all' => $locale('Control_Selection::remove_all:=Remove {total} matching {nouns_total}', $__),
] as $k => $v) {
	echo HTML::tag('li', HTML::tag('a', [
		'data-select-action' => $k,
	], $v));
}
echo HTML::tag('li', [
	'role' => 'presentation',
	'class' => 'dropdown-header',
], $locale('... or select individual {nouns} below', [
	'nouns' => $plural_noun,
]));
echo HTML::tag_close('ul');
?>
</div>
<?php
if (is_array($this->actions) && count($this->actions) > 0) {
	?>
<div class="btn-group control-selection-actions-menu">
	<button type="button" class="btn btn-default dropdown-toggle"
		data-toggle="dropdown">
		<?php
	echo HTML::span([
		'class' => 'control-selection-actions',
	], $locale('Control_Selection::action_menu_title:={noun} actions', $__)); ?>
		<span class="caret"></span>
	</button>
		<?php
	echo HTML::tag_open('ul', [
		'class' => 'dropdown-menu',
		'role' => 'menu',
	]);
	foreach ($this->actions as $href => $settings) {
		if (is_string($settings)) {
			$title = $settings;
			$settings = [];
		} elseif (!is_array($settings)) {
			continue;
		} else {
			$title = $settings['title'];
		}
		$settings['href'] = map($href, [
			'name' => $this->name,
		]);
		echo HTML::tag('li', HTML::tag('a', $settings, $title));
	}
	echo HTML::tag_close('ul');
}
?>
	</div>
<?php
echo HTML::tag_close('div');
