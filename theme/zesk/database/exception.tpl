<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

if (false) {
	/* @var $this Template */
	
	$zesk = $this->zesk;
	/* @var $zesk \zesk\Kernel */
	
	$application = $this->application;
	/* @var $application \zesk\Application */
	
	$session = $this->session;
	/* @var $session \zesk\Session */
	
	$router = $this->router;
	/* @var $request \zesk\Router */
	
	$request = $this->request;
	/* @var $request \zesk\Request */
	
	$response = $this->response;
	/* @var $response \zesk\Response_Text_HTML */
}

$this->begin('body/exception.tpl');

/* @var $exception Exception */
$exception = $this->exception;
$class = get_class($this->exception);

log::error("Exception: {exception_class}\nMessage: {message}\nServer:\n{server}\nRequest:\n{request}\nException: {exception_class}\nBacktrace:\n{backtrace}\n{exception}", array(
	"server" => Text::format_pairs($_SERVER),
	"request" => Text::format_pairs($_REQUEST),
	"exception_class" => $class,
	"exception" => $exception,
	"message" => $exception->getMessage(),
	"backtrace" => $exception->getTraceAsString()
));

?>
<div class="error">
	<p>There was a database error.</p>
	<?php
	if ($this->application->development()) {
		?><p>The computer error given was:</p><?php
		echo HTML::tag('code', $exception->getMessage());
		?><p>The call stack is:</p><?php
		echo $this->theme('exception/trace', array(
			"content" => $exception->getTrace()
		));
	}
	?>
</div>
<?php
echo $this->end();
