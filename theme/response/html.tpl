<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/* @var $this Template */
/* @var $application Kernel */
/* @var $application Application */
/* @var $request Request */
/* @var $response Response */
/* @var $body_attributes array */
/* @var $html_attributes array */
/* @var $html_attributes array */
$request = $this->request;
$response = $this->response;
if (!$request) {
	$request = $this->request = $application->request();
}

$hook_parameters = [
	$request,
	$response,
	$this,
];
$application->hooks->callArguments('response_html_start', $hook_parameters);
{
	$application->modules->all_hook_arguments('headers', $hook_parameters);
	$application->callHookArguments('headers', $hook_parameters);

	$application->modules->all_hook_arguments('html', $hook_parameters);
	echo $this->theme('response/html/head/doctype');
	$application->hooks->callArguments('<html>', $hook_parameters);
	echo HTML::tag_open('html', $response->htmlAttributes());
	{
		echo $this->theme('response/html/head', [
			'hook_parameters' => $hook_parameters,
		]);
		echo HTML::tag_open('body', $response->bodyAttributes());
		{
			echo $application->hooks->callArguments('<body>', $hook_parameters, '');
			foreach ([
				'content',
				'page_contents',
				'page_content',
			] as $k) {
				if ($this->has($k)) {
					echo $this->get($k);
					break;
				}
			}
			echo $this->theme('response/html/scripts');
			echo $application->hooks->callArguments('</body>', $hook_parameters, '');
		}
		$application->modules->all_hook_arguments('foot', $hook_parameters);

		echo "\n" . HTML::tag_close('body');
	}
	echo $application->hooks->callArguments('</html>', $hook_parameters, '');
	echo "\n" . HTML::tag_close('html');
}
$application->hooks->callArguments('response_html_end', $hook_parameters);
