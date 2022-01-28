<?php declare(strict_types=1);
/**
 * @copyright &copy; 2022 Market Acumen, Inc.
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Interface_Session */
/* @var $request \zesk\Router */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
$session = $this->session;
if (!$session) {
	try {
		$session = $application->session($this->request);
	} catch (Exception $e) {
	}
}
?>
<h1>Cookie</h1>
<pre><?php

echo PHP::dump($_COOKIE);
?></pre>
<h1>Session</h1>
<?php
$login = "<em>None</em>";
if ($session && $session->authenticated()) {
	$user = $session->user();
	$login = "<em>Authenticated, no user.</em>";
	if ($user instanceof User) {
		$login = $user->login();
	}
}
echo $login;
?>
<pre>
<?php

echo $session ? PHP::dump($session->variables()) : "<em>no session</em>"?>
</pre>
<h1>$_REQUEST</h1>
<pre>
<?php

echo PHP::dump($_REQUEST);
?>
</pre>
<h1>Autoload Path</h1>
<?php

echo HTML::tag('pre', JSON::encode_pretty($application->autoloader->path()));
?>
<h1>Globals</h1>
<pre>
<?php

echo PHP::dump($application->configuration->to_array());
?>
</pre>
<h1>$_SERVER</h1>
<pre>
<?php

echo PHP::dump($_SERVER);
?>
</pre>
<h1>Share Paths</h1>
<?php
echo HTML::tag('ul', HTML::tags('li', $application->share_path()));
?>
<h1>Theme Paths</h1>
<?php
echo HTML::tag('pre', JSON::encode_pretty($application->theme_path()));
?>
<h1>Databases</h1>
<p>Default database is <?php
echo HTML::tag('strong', $application->database_module()->database_default());
?></p>
<pre>
<?php

echo Text::format_pairs($application->database_module()->register())?>
</pre>
<h1>Hooks</h1>
<pre>
<?php
echo implode("<br />", array_keys($application->hooks->has()))?>
</pre>
