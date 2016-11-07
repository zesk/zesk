# Templates

Templates are essentially PHP include files with some special features:

- zesk\Template inheritance and overrides
- Variable inheritance and passing

Simple template engine which uses PHP includes.

Supports variables passed into the template, returned from the template,
and inherited templates by setting up `app()->theme_path($add_theme_path)`

Templates are "stacked" to inherit parent variables settings.

Changing template values within the template (e.g. `$this->frame = "foo";`) will then bubble up to parent templates.

Templates should be implemented by assuming "$this" is a zesk\Template within the template file, and you can use:

`$this->has('account')` to determine if a variable has been set, and

`$this->account` to output it.

Note that variables are also extract'ed into the local scope, so

    $this->account
    $account

both exist within the scope if passed via a variable to a template.

## zesk\Template basics

A simple template which outputs a percent would be `view/percent.tpl`:

    <?php
    echo number_format($this->value, $this->get('decimal_precision', 0)) . "%";

Similarly, you can make a template pure HTML:

	<div class="bar-decoration"><i class="icon-edit" /></div>

Within a template, the term `$this` is always of class `Template` meaning you can call zesk\Template methods directly.

## zesk\Template naming conventions

Templates are accessed similarly to other aspects of Zesk by using a search path.

    zesk()->paths->template()

Returns the current template search path. Most templates end with `.tpl` but can end with any extension, depending on type. However, all templates, by default, are `include`d so be aware that any template code will be loaded as regular PHP.

So, loading a template like:

    Template::instance("view/percent.tpl", array("content" => 42));

Will search each template path until it finds the appropriate template, then include the template and return the results.

## zesk\Template Inheritance

Templates are managed in a global stack which allows for values to be inherited from one template to the next. This avoids having to pass application globals between templates and makes managing state easier as well.

TODO