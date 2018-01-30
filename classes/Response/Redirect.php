<?php
/**
 * @package zesk
 * @subpackage response
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
namespace zesk\Response;

use zesk\Response;

/**
 * @see Type
 * @author kent
 */
class Redirect extends Type {

	/**
	 *
	 * @return \zesk\Interface_Session
	 */
	private function session() {
		return $this->application->session($this->parent->request);
	}
	/**
	 *
	 * @todo Move this elsewhere. Response addon?
	 */
	public function message_clear() {
		try {
			$this->session()->redirect_message = null;
		} catch (\Exception $e) {
			$this->application->logger->debug("{method} caused an exception {e}", array(
				"method" => __METHOD__,
				"e" => $e
			));
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
			$messages = to_array($this->session()->redirect_message);
		} catch (\Exception $e) {
			return array();
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
	public function render($content) {
		return $this->parent->html()->render($content);
	}

	/**
	 *
	 * @param string $url
	 * @param string $message
	 */
	public function url($url, $message = null) {
		$this->parent->output_handler(Response::HANDLER_REDIRECT);
		$saved_url = $url;
		/* Clean out any unwanted characters from the URL */
		$url = preg_replace("/[\x01-\x1F\x7F-\xFF]/", '', $url);
		if ($message !== null) {
			$this->message($message);
		}
		$url = $this->parent->call_hook('redirect_alter', $url);
		if ($this->parent->option_bool("debug_redirect")) {
			$this->parent->content = $this->application->theme("response/redirect", array(
				'request' => $this->request,
				'response' => $this,
				'content' => HTML::a($url, $url),
				'url' => $url,
				'original_url' => $saved_url
			));
		} else {
			if ($url) {
				$this->parent->header("Location", $url);
			}
			$this->application->hooks->call_arguments('headers', array(
				$this->request,
				$this->parent,
				null
			));
		}
	}
}