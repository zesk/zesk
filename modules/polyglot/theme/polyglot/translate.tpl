<?php

/**
 *
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \User */
$can_update_live = $current_user ? $current_user->can("zesk\\Module_PolyGlot::update") : false;

/* @var $response zesk\Response */
$response = $this->response;

$response->jquery();
$response->javascript("/share/polyglot/js/polyglot.js", array(
	"share" => true
));
$response->css("/share/polyglot/css/polyglot.css", array(
	"share" => true
));

$object = new Model($application);
$object->locale = $locale;
$object->status = $this->request->get("s", PolyGlot_Token::status_todo);

$locale_options = to_array($this->locale_options);
asort($locale_options, SORT_LOCALE_STRING);
$widget = $this->widget_factory(Control_Select::class)
	->names("locale", __("Locale"))
	->control_options($locale_options)
	->hide_single(false)
	->default_value($object->locale);
$widget->required(true);

$status = $this->widget_factory(Control_Select::class)
	->names("status", __("Status"))
	->control_options(PolyGlot_Token::lang_status_filters())
	->noname(__("All"));
$status->default_value($object->status);
$widget->required(true);

?>
<div id="translate-main" class="filters">
	<nav class="navbar navbar-default" role="filter">
		<div class="container-fluid" id="translate-header">
			<div class="navbar-header">
				<a class="navbar-brand tip tipped" title="" href="/polyglot"
					data-original-title="Reset all filters and search criteria for this list.">Translate</a>
			</div>
			<form class="navbar-form" role="filter" method="GET">
				<div class="form-group control-active"><?php
				echo $widget->execute();
				?></div>
				<div class="form-group control-active"><?php
				echo $status->execute();
				?></div>
				<div class="form-group control-search">
					<div class="input-group">
						<input id="q" name="q" class="form-control" placeholder="Search"
							type="text"><span class="input-group-btn"><button
								class="btn btn-default tip tipped" title=""
								data-original-title="Search member names and emails.">
								<span class="glyphicon glyphicon-search"></span>
							</button></span>
					</div>
				</div>
				<div class="form-group control-progress">
					<img src="/share/zesk/images/spinner/spinner-32x32.gif" width="32"
						height="32" style="margin-left: 10px" />
				</div>
				<div class="form-group">
					<div id="search-progress"></div>
				</div>
				<?php
				if ($can_update_live) {
					?><button id="translate-save" class="btn btn-warning pull-right"><?php

					echo __("Update Live");
					?></button><?php
				}
				?></form>
		</div>
	</nav>
	<form id="translate-form" style="display: none">
		<div class="original"></div>
		<textarea rows="5" id="translation" class="form-control"></textarea>
		<div class="actions">
			<a class="btn btn-danger" data-status="delete" id="action-delete">Delete
				<span class="shortcut">(Ctrl-Delete)</span>
			</a> <a class="btn btn-default" data-status="todo" id="action-todo">Need
				to translate <span class="shortcut">(Ctrl-T)</span>
			</a> <a class="btn btn-info" data-status="info" id="action-info">Need
				more information <span class="shortcut">(Ctrl-I)</span>
			</a> <a class="btn btn-warning" data-status="dev" id="action-dev">Need
				developer review <span class="shortcut">(Ctrl-D)</span>
			</a> <a class="btn btn-warning" data-status="draft" id="action-draft">Draft
				<span class="shortcut">(Ctrl-F)</span>
			</a> <a class="btn btn-success" data-status="done" id="action-done">Translate
				<span class="shortcut">(Enter)</span>
			</a>
		</div>
		<label class="shortcuts" for="shortcuts"><input id="shortcuts"
			type="checkbox" class="checkbox pull-left" /> Show keyboard shortcuts</label>
		<label class="shortcuts" for="shortcuts"></label> <a class=""
			role="button" data-toggle="collapse" href="#translate-help"
			aria-expanded="false" aria-controls="translate-help">Help</a>
		<div class="collapse clearfix" id="translate-help">
			<div class="well">
				<?php

				echo $this->theme("polyglot/translate-help-header");
				?>
				<h3>Keyboard</h3>
				<p>Navigate with Ctrl-&rarr;, Ctrl-&larr;, &uarr;, &darr;. When the
					translator editor is closed, use arrow keys to choose the first or
					last item in the displayed list.</p>
				<p>
					Use the <strong>Escape</strong> key to close the translator editor
					and discard changes at any time.
				</p>
				<h3>Tokens and Tags</h3>
				<p>
					<strong>Tokens:</strong> When you see a token such as {object} - do
					not translate the text inside the brackets, but use the token where
					the part of speech would appear in the translation.
				</p>
				<blockquote>{duration} ago &rarr; il y a {duration}</blockquote>
				<p>
					<strong>Tags:</strong> brackets
					<code>[]</code>
					within a translation indicate a formatting tag, such as bold, or a
					link. Preserve bracketed terms in your translation.
				</p>
				<blockquote>To continue, click [here]. &rarr; Pour continuer,
					cliquez [ici].</blockquote>
				<p>
					<strong>Groups:</strong> If you see a <span
						class="label label-default">Group</span> next to a translation -
					it should give you some context for where the translation appears.
				</p>
				<h3>Status meanings</h3>
				<ul>
					<li><strong>Need to translate</strong> - A phrase which needs to be
						translated to the target language. You should generally work off
						of this list.</li>
					<li><strong>Need developer review</strong> - For phrases which need
						to be reviewed by the developer, for any of the following reasons:
						contains site user content (user names, article names, etc.), or
						contains nothing to be translated.</li>
					<li><strong>Need more information</strong> - A phrase which can not
						be translated until you have more information. Note what
						information is missing in the translation field before submitting.</li>
					<li><strong>Draft</strong> - A phrase which is in draft form and
						should be reviewed by an expert translator prior to being
						submitted.</li>
					<li><strong>Delete</strong> - Phrases which should be deleted
						(contains user content not applicable to all sites, etc.). <strong>CAUTION:
							Deleting a phrase will remove it from all available translations.</strong></li>
				</ul>
				<?php

				echo $this->theme("polyglot/translate-help-footer");
				?>



			</div>
		</div>
	</form>
	<div class="row header">
		<div class="col-sm-2">Status</div>
		<div class="col-sm-5">Token</div>
		<div class="col-sm-5">Translation</div>
	</div>
	<div id="translate-list"></div>
</div>
<script type="text/x-template" id="translate-one">
<div class="row row-status-{status}" id="{css_id}">
	<div class="col-sm-2"><span class="label label-{style_status}">{lang_status}</span></div>
	<div class="col-sm-5"><span class="label label-info group">{#group}</span>{#original}</div>
	<div class="col-sm-5">{#translation}</div>
</div>
</script>
<script type="text/x-template" id="translate-title">
	<h1><span class="label label-info group">{#group}</span> {#original}</h1>
</script>
