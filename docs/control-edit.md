# Building Forms using `zesk\Control_Edit`

## Theme inheritance

The default themes for the `zesk\Control_Edit` are set up based on your current classes' name, and any parents inherited by it.

So if you had an object with the following class hierarchy (child to parent):

- `myapp\Control_Edit_Payment`
- `myapp\Control_Edit`
- `zesk\Control_Edit`
- `zesk\Control`
- `zesk\Widget`
- `zesk\Hookable`
- `zesk\Options`

The templates would be set up using those paths, in a similar search order, so:

- myapp\control\edit\payment\header.tpl
- myapp\control\edit\header.tpl
- zesk\control\edit\header.tpl

Note that theme paths only include those classes and subclasses which stop at the `zesk\Control_Edit` class.

## How themes are displayed

In your subclass, the following values of the class will be output to display the form:

- Option "prefix"
- Option "suffix"
- Themes set as member variables: `$this->theme_prefix`, `$this->theme_suffix`, `$this->theme_header`, `$this->theme_footer`
- Theme for layout of form: `$this->theme_widgets`

The page is output in the following order: 

1. Option "prefix"
2. `theme_prefix`
3. Open tag created using `$this->form_tag`, and `$this->form_attributes` (usually the `<form>` tag)
4. `theme_header`
5. `theme_widgets` - Form controls are rendered into this theme
6. `theme_footer`
7. Any invisible form controls
8. Hidden form values (preserved form values)
9. Closing tag for `$this->form_tag`
10. `theme_suffix`
11. Option "suffix"

Empty items are not output. For themes, the first-found theme is used. (Note this allows overriding the default list behavior by subclasses.)

## Form controls rendered to widgets

The `$this->theme_widgets` is intended to allow for flexible layout of your form using substitutions. Note that this is an *optional* step to modify the layout of your widget. Otherwise, it uses either a standard full-width widget with the label above, or a 2-column layout with labels on the left and widgets on the right.

The standard widget output will interpolate the values and create variables you can then place into your layout; for the widget's input name of `foo`:

- `{foo.label}` - The rendered label for the widget
- `{foo.widget_class}` - The name of the widget class, e.g. "zesk\Control_Text"
- `{foo.has_error}` - Either blank, or a space followed by the string "has_error"; useful for placing into class values
- `{foo.errors}` - Blank, or HTML to display errors related to a widget
- `{foo.help}` - The help HTML associated with this widget
- `{foo.render}` - The rendered widget (e.g. HTML `input`)

For example, if your form contains 5 controls, with input names:

- `name`
- `nickname`
- `email`
- `is_private`
- `ok`

Then your widgets document can use the following defined variables as substitutions (using zesk's standard `map()` function):

	{name.label} {name.widget_class} {name.has_error} {name.errors} {name.help} {name.render}
	{nickname.label} {nickname.widget_class} {nickname.has_error} {nickname.errors} {nickname.help} {nickname.render}	
	{email.label} {email.widget_class} {email.has_error} {email.errors} {email.help} {email.render}	
	{is_private.label} {is_private.widget_class} {is_private.has_error} {is_private.errors} {is_private.help} {is_private.render}	
	{ok.label} {ok.widget_class} {ok.has_error} {ok.errors} {ok.help} {ok.render}	

A layout using these may look something like this:

	<div class="transaction-settings">
		<div class="service-date form-group{when.has_error}">{when.label} {when.render}</div>
		<div class="service-date-col">
			<div class="title form-group{title.has_error}">
				{title.label}
				<div class="widget-wrap">{title.render}</div>
			</div>
			<div class="form-group category{category.has_error}">
				{category.label}
				<div class="widget-wrap">{category.render}</div>
			</div>
		</div>
	</div>

Note you can still use PHP code in your template as needed, and access the above content by accessing template variables using underscores and prefixed by `widget_`, instead:

	<div class="transaction-settings">
		<div class="service-date form-group<?= $widget_when_has_error ?>"><?= $widget_when_label ?> <?= $widget_when_render ?></div>
		<div class="service-date-col">
			<div class="title form-group<?= $widget_title_has_error ?>">
				<?= $widget_title_label ?>
				<div class="widget-wrap"><?= $widget_title_render ?></div>
			</div>
			<div class="form-group category<?= $widget_category_has_error ?>">
				<?= $widget_category_label ?>
				<div class="widget-wrap"><?= $widget_category_render ?></div>
			</div>
		</div>
	</div>

With the additionally defined `$widget_foo` as the actual object of class `Widget`.
