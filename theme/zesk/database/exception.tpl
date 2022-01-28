<?php declare(strict_types=1);

/**
 * @copyright &copy; 2022 Market Acumen, Inc.
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_HTML */
/* @var $current_user \User */
$this->begin('body/exception.tpl');

/* @var $exception Exception */
$exception = $this->exception;
$class = get_class($this->exception);

$application->logger->error("Exception: {exception_class}\nMessage: {message}\nServer:\n{server}\nRequest:\n{request}\nException: {exception_class}\nBacktrace:\n{backtrace}\n{exception}", [
	"server" => Text::format_pairs($_SERVER),
	"request" => Text::format_pairs($_REQUEST),
	"exception_class" => $class,
	"exception" => $exception,
	"message" => $exception->getMessage(),
	"backtrace" => $exception->getTraceAsString(),
]);

?>
<div class="error">
	<p>There was a database error.</p>
	<?php
	if ($this->application->development()) {
		?><p>The computer error given was:</p><?php
		echo HTML::tag('code', $exception->getMessage()); ?><p>The call stack is:</p><?php
		echo $this->theme('exception/trace', [
			"content" => $exception->getTrace(),
		]);
	}
	?>
</div>
<?php
echo $this->end();
