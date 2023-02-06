<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/* @var $this Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \Session */
/* @var $request Router */
/* @var $request Request */
/* @var $response Response */
/* @var $current_user \User */

/* @var $exception Exception */
if ($response->status_code === HTTP::STATUS_OK) {
	$response->setStatus(HTTP::STATUS_INTERNAL_SERVER_ERROR, 'Exception');
}
$exception = $this->exception;
$class = get_class($this->exception);

$application->logger->error("Exception: {exception_class}\nMessage: {message}\nServer:\n{server}\nRequest:\n{request}\nException: {exception_class}\nBacktrace:\n{backtrace}\n{exception}", [
	'server' => Text::format_pairs($_SERVER),
	'request' => Text::format_pairs($_REQUEST),
	'exception_class' => $class,
	'exception' => $exception,
	'message' => $exception->getMessage(),
	'backtrace' => $exception->getTraceAsString(),
]);

echo HTML::tag_open('div', '.exception');

if (!$exception instanceof Exception) {
	$message = 'Not an exception: ' . type($exception);
	$trace = debug_backtrace();
} else {
	$message = $exception->getMessage();
	$trace = $exception->getTrace();
}
$dev = $application->development();
?>
<div class="exception-error">
	<h1><?php
	echo $dev ? $class : strtr($class, '_', ' ')?>
		<!--  <?php
		echo $class;
?> -->
	</h1>
	<p>The computer error given was:</p>
	<?php
	echo HTML::tag('code', $message);
if ($dev) {
	?><p>The call stack is:</p><?php
	echo $this->theme('Exception/Trace', [
		'content' => $trace,
	]); ?>
	<?php
}
if ($this->suffix) {
	echo HTML::tag('p', $this->suffix);
}
?>
</div>
<?php
echo HTML::tag_close('div');