<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage response
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk\Response;

use zesk\Interface_Session;
use zesk\Response;
use zesk\Exception_Redirect;
use zesk\Exception_RedirectTemporary;
use zesk\Net_HTTP;
use zesk\Exception_Key;

/**
 * @see Type
 * @author kent
 */
class Redirect extends Type {
	/**
	 *
	 * @return Interface_Session
	 */
	private function session(): Interface_Session {
		return $this->application->session($this->parent->request);
	}

	/**
	 *
	 * @todo Move this elsewhere. Response addon?
	 */
	public function message_clear(): void {
		try {
			$this->session()->redirect_message = null;
		} catch (\Exception $e) {
			$this->application->logger->debug('{method} caused an exception {e}', [
				'method' => __METHOD__,
				'e' => $e,
			]);
		}
	}

	public const SESSION_KEY_REDIRECT_STATE = 'redirect_message';

	/**
	 *
	 * @todo Move this elsewhere. Response addon?
	 * @param string $message
	 * @return Response
	 */
	public function addMessage(string $message, array $attributes = []): Response {
		$session = $this->session();
		$messages = toArray($session->get(self::SESSION_KEY_REDIRECT_STATE));
		$messages[md5($message)] = ['content' => $message] + $attributes;
		$session->set(self::SESSION_KEY_REDIRECT_STATE, $messages);
		return $this->parent;
	}

	/**
	 *
	 * @return array
	 */
	public function messages(): array {
		$session = $this->session();
		$messages = toArray($session->get(self::SESSION_KEY_REDIRECT_STATE));
		return array_values($messages);
	}

	/**
	 * Render HTML
	 *
	 * @param string $content
	 * @return string
	 * @throws Exception_Redirect
	 */
	public function render(string $content): string {
		return $this->parent->html()->render($content);
	}

	/**
	 * @return array
	 */
	public function toJSON(): array {
		return [];
	}

	/**
	 * @param string $content
	 * @return void
	 * @throws Exception_Redirect
	 */
	public function output(string $content): void {
		echo $this->render($content);
	}

	/**
	 * @throws Exception_Redirect
	 * @param string $url
	 * @param string $message
	 */
	public function url(string $url, string $message = ''): void {
		throw new Exception_Redirect($url, $message);
	}

	/**
	 * @throws Exception_RedirectTemporary
	 * @param string $url
	 * @param string $message
	 */
	public function urlTemporary(string  $url, string $message = ''): void {
		throw new Exception_RedirectTemporary($url, $message);
	}

	/**
	 *
	 * @param string $url
	 * @return string
	 */
	public function processURL(string $url): string {
		/* Clean out any unwanted characters from the URL */
		$url = preg_replace("/[\x01-\x1F\x7F-\xFF]/", '', $url);
		$altered_url = $this->parent->callHookArguments('redirect_alter', [
			$url,
		], $url);
		if (is_string($altered_url) && !empty($altered_url)) {
			$url = $altered_url;
		}
		return $url;
	}

	/**
	 * Load up an Exception_Redirect for handling
	 *
	 * @param Exception_Redirect $exception
	 * @return string
	 */
	public function handleException(Exception_Redirect $exception): string {
		$original_url = $exception->url();
		$message = $exception->getMessage();

		if ($message) {
			$this->addMessage($message);
		}
		$url = $this->processURL($original_url);
		$this->parent->setOutputHandler(Response::HANDLER_REDIRECT);

		$this->parent->setHeader('Location', $url);
		$status_code = $exception->statusCode();
		if (!$status_code) {
			$status_code = HTTP::STATUS_MOVED_PERMANENTLY;
		}
		$this->parent->status_code = $status_code;
		$status_message = $exception->statusMessage();
		if (!$status_message) {
			$status_message = 'Moved';
		}
		$this->parent->status_message = $status_message;
		return $url;
	}
}
