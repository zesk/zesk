<?php declare(strict_types=1);
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Route_Content extends Route {
	protected function _execute(Response $response) {
		$file = $this->option("file");
		if ($file) {
			return $response->file($file);
		}
		$content = $this->option("content", $this->option('default content'));
		if ($this->option("json")) {
			return $response->json()->data($content);
		} else {
			$response->content = $content;
		}
	}
}
