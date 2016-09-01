<?php
if (false) {
	/* @var $this Template */
	
	$zesk = $this->zesk;
	/* @var $zesk zesk\Kernel */
	
	$application = $this->application;
	/* @var $application TimeBank */
	
	$session = $this->session;
	/* @var $session Session */
	
	$request = $this->request;
	/* @var $request Request */
	
	$response = $this->response;
	/* @var $response Response_HTML */
	
	$current_user = $this->current_user;
	/* @var $current_user User */
}
/* @var $session Session_Database */
$session = $this->session;
if (!$session) {
	try {
		$session = Session::instance();
	} catch (Exception $e) {
	}
}
?>
<h1>Cookie</h1>
<pre><?php echo php::dump($_COOKIE); ?></pre>
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
<?php echo $session ? php::dump($session->variables()) : "<em>no session</em>"?>
</pre>
<h1>$_REQUEST</h1>
<pre>
<?php echo php::dump($_REQUEST); ?>
</pre>
<h1>Autoload Path</h1>
<?php echo html::tag('ul', html::tags('li', array_keys($zesk->autoloader->path()))); ?>
<h1>Globals</h1>
<pre>
<?php echo php::dump($zesk->configuration->to_array()); ?>
</pre>
<h1>$_SERVER</h1>
<pre>
<?php echo php::dump($_SERVER); ?>
</pre>
<h1>Databases</h1>
<p>Default database is <?php echo html::tag('strong', Database::database_default()); ?></p>
<pre>
<?php echo text::format_pairs(Database::register())?>
</pre>
<h1>Hooks</h1>
<pre>
<?php echo implode("<br />", array_keys($zesk->hooks->has()))?>
</pre>
