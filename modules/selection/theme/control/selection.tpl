<?php

$this->response->cdn_javascript('/share/selection/selection.js');

$name = $this->name;
$session = $this->session;
/* @var $session Session_Interface */

$plural_noun = lang::plural($this->label, 2);

$page_format = "All {nouns} shown ({n} unselected)";
$format = "{n} {nouns} selected";

echo html::tag_open('div', array(
	'class' => 'control-selection-widget form-group',
	'data-limit' => $this->limit,
	'data-total' => $this->total,
	'data-name' => $this->name,
	'data-page-format' => __($page_format),
	'data-format' => __($format),
	'data-noun' => $this->label,
	'data-container' => $this->container,
	'data-target' => $this->target,
	'data-count' => $this->count
));
?>
<div class="btn-group control-selection-menu">
	<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
		<?php
		echo html::span(array(
			'class' => 'title'
		), __('{n} {nouns} selected', array(
			'n' => $this->count,
			'nouns' => lang::plural($this->label, $this->count)
		)));
		?>
		<span class="caret"></span>
	</button>
	<?php
	echo html::tag_open('ul', array(
		'class' => 'dropdown-menu',
		'role' => 'menu'
	));
	echo html::tag('li', array(
		'role' => 'presentation',
		'class' => 'dropdown-header'
	), __('Select {nouns} ...', array(
		'nouns' => $plural_noun
	)));

	$plural_noun = lang::plural($this->label, 2);
	foreach (array(
		'none' => __('No {noun}', array(
			'noun' => $plural_noun
		)),
		'page' => __($page_format, array(
			'n' => $this->limit,
			'nouns' => lang::plural($this->label, $this->total)
		)),
		'all' => __('All matching {nouns} ({n} total)', array(
			'n' => $this->total,
			'nouns' => lang::plural($this->label, $this->total)
		))
	) as $k => $v) {
		echo html::tag('li', html::tag('a', array(
			'data-select-action' => $k
		), $v));
	}
	echo html::tag('li', array(
		'role' => 'presentation',
		'class' => 'dropdown-header'
	), __('... or choose individual {nouns} below', array(
		'nouns' => $plural_noun
	)));
	echo html::tag_close('ul');
	?>
</div>
<?php
if (is_array($this->actions) && count($this->actions) > 0) {
	?>
<div class="btn-group control-selection-actions-menu">
	<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
		<?php
	echo html::span(array(
		'class' => 'control-selection-actions'
	), __('{noun} actions', array(
		'noun' => $this->label
	)));
	?>
		<span class="caret"></span>
	</button>
		<?php
	echo html::tag_open('ul', array(
		'class' => 'dropdown-menu',
		'role' => 'menu'
	));
	foreach ($this->actions as $href => $settings) {
		if (is_string($settings)) {
			$title = $settings;
			$settings = array();
		} else if (!is_array($settings)) {
			continue;
		} else {
			$title = $settings['title'];
		}
		$settings['href'] = map($href, array(
			"name" => $this->name
		));
		echo html::tag("li", html::tag("a", $settings, $title));
	}
	echo html::tag_close('ul');
}
?>
	</div>
<?php
echo html::tag_close('div');

