<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage response
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Response;

use Throwable;
use zesk\Exception\KeyNotFound;
use zesk\Exception\Redirect as RedirectException;
use zesk\Exception\RedirectTemporary;
use zesk\Exception\SemanticsException;
use zesk\Interface\SessionInterface;
use zesk\Net_HTTP;
use zesk\Response;
use zesk\Types;

/**
 * @see Type
 * @author kent
 */
class Redirect extends Type
{
	/**
	 *
	 * @return SessionInterface
	 * @throws SemanticsException
	 */
	private function session(): SessionInterface
	{
		return $this->application->requireSession($this->application->request());
	}

	/**
	 *
	 * @todo Move this elsewhere. Response addon?
	 */
	public function messageClear(): void
	{
		try {
			$this->session()->redirect_message = null;
		} catch (Throwable $e) {
			$this->application->debug('{method} caused an exception {e}', [
				'method' => __METHOD__, 'e' => $e,
			]);
		}
	}

	public const SESSION_KEY_REDIRECT_STATE = 'redirect_message';

	/**
	 *
	 * @param string $message
	 * @param array $attributes
	 * @return Response
	 * @throws SemanticsException
	 * @throws KeyNotFound
	 * @todo Move this elsewhere. Response addon?
	 */
	public function addMessage(string $message, array $attributes = []): Response
	{
		$session = $this->session();
		$messages = Types::toArray($session->get(self::SESSION_KEY_REDIRECT_STATE));
		$messages[md5($message)] = ['content' => $message] + $attributes;
		$session->set(self::SESSION_KEY_REDIRECT_STATE, $messages);
		return $this->parent;
	}

	/**
	 *
	 * @return array
	 */
	public function messages(): array
	{
		$session = $this->session();
		$messages = Types::toArray($session->get(self::SESSION_KEY_REDIRECT_STATE));
		return array_values($messages);
	}

	/**
	 * Render HTML
	 *
	 * @param string $content
	 * @return string
	 * @throws Redirect
	 */
	public function render(string $content): string
	{
		return $this->parent->html()->render($content);
	}

	/**
	 * @return array
	 */
	public function toJSON(): array
	{
		return [];
	}

	/**
	 * @param string $content
	 * @return void
	 * @throws Redirect
	 */
	public function output(string $content): void
	{
		echo $this->render($content);
	}

	/**
	 * @param string $url
	 * @param string $message
	 * @throws RedirectException
	 */
	public function url(string $url, string $message = ''): void
	{
		throw new RedirectException($url, $message);
	}

	/**
	 * @param string $url
	 * @param string $message
	 * @throws RedirectTemporary
	 */
	public function urlTemporary(string $url, string $message = ''): void
	{
		throw new RedirectTemporary($url, $message);
	}

	public const HOOK_URL_ALTER = self::class . '::urlAlter';

	/**
	 *
	 * @param string $url
	 * @return string
	 */
	public function processURL(string $url): string
	{
		/* Clean out any unwanted characters from the URL */
		$url = preg_replace("/[\x01-\x1F\x7F-\xFF]/", '', $url);
		$altered_url = $this->parent->invokeTypedFilters(self::HOOK_URL_ALTER, $url, [$this->parent]);
		if (is_string($altered_url) && !empty($altered_url)) {
			$url = $altered_url;
		}
		return $url;
	}

	/**
	 * Load up an Redirect for handling
	 *
	 * @param Redirect $exception
	 * @return string
	 */
	public function handleException(RedirectException $exception): string
	{
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
