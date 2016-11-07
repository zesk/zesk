<?php
/**
 * 
 */
namespace zesk;

if (false) {
	/* @var $this Template */

	$zesk = $this->zesk;
	/* @var $zesk zesk\Kernel */
	
	$application = $this->application;
	/* @var $application \zesk\Application */

	$session = $this->session;
	/* @var $session Session */

	$router = $this->router;
	/* @var $request Router */

	$request = $this->request;
	/* @var $request Request */

	$response = $this->response;
	/* @var $response Response_HTML */

	$current_user = $this->current_user;
	/* @var $current_user User */
}

/* @var $exception Exception */
$exception = $this->exception;
$class = get_class($this->exception);

$zesk->logger->error("Exception: {exception_class}\nMessage: {message}\nServer:\n{server}\nRequest:\n{request}\nException: {exception_class}\nBacktrace:\n{backtrace}\n{exception}", array(
	"server" => Text::format_pairs($_SERVER),
	"request" => Text::format_pairs($_REQUEST),
	"exception_class" => $class,
	"exception" => $exception,
	"message" => $exception->getMessage(),
	"backtrace" => $exception->getTraceAsString()
));

$this->begin('body/exception');

if (!$exception instanceof Exception) {
	$message = "Not an exception: " . type($exception);
	$trace = debug_backtrace();
} else {
	$message = $exception->getMessage();
	$trace = $exception->getTrace();
}
$dev = $this->application->development();
?>
<div class="exception-error">
	<h1><?php echo $dev ? $class : strtr($class, "_", " ")?>
		<!--  <?php echo $class; ?> -->
	</h1>
	<p>The computer error given was:</p>
		<?php
		echo HTML::tag('code', $message);
		if ($dev) {
			?><p>The call stack is:</p><?php
			echo $this->theme('exception/trace', array(
				"content" => $trace
			));
			?>
	<?php } ?>
</div>
<?php
echo $this->end();
