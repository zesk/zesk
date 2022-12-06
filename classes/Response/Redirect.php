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
use zesk\HTML;
use zesk\Exception_Redirect;
use zesk\Exception_RedirectTemporary;
use zesk\Net_HTTP;

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

	/**
	 *
	 * @todo Move this elsewhere. Response addon?
	 * @param string $message
	 * @return \zesk\Response
	 */
	public function message($message = null) {
		try {
			$messages = toArray($this->session()->redirect_message);
		} catch (\Exception $e) {
			return [];
		}
		if ($message === null) {
			return $messages;
		}
		if (empty($message)) {
			return $this->parent;
		}
		if (is_array($message)) {
			foreach ($message as $m) {
				if (!empty($m)) {
					$messages[md5($m)] = $m;
				}
			}
		} else {
			$messages[md5($message)] = $message;
		}
		$this->session()->redirect_message = $messages;
		return $this->parent;
	}

	/**
	 * Render HTML
	 *
	 * {@inheritDoc}
	 * @see \zesk\Response\Type::render()
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
	public function url_temporary(string  $url, string $message = ''): void {
		throw new Exception_RedirectTemporary($url, $message);
	}

	/**
	 *
	 * @param string $url
	 * @return string
	 */
	public function processURL(string $url): string {
		$saved_url = $url;
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
			$this->message($message);
		}
		$url = $this->processURL($original_url);
		$this->parent->output_handler(Response::HANDLER_REDIRECT);

		$this->parent->header('Location', $url);
		$status_code = $exception->status_code();
		if (!$status_code) {
			$status_code = Net_HTTP::STATUS_MOVED_PERMANENTLY;
		}
		$this->parent->status_code = $status_code;
		$status_message = $exception->status_message();
		if (!$status_message) {
			$status_message = 'Moved';
		}
		$this->parent->status_message = $status_message;
		return $url;
	}
}
