<?php declare(strict_types=1);
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Route_Content extends Route {
	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws Exception_NotFound
	 */
	protected function _execute(Request $request, Response $response): Response {
		$file = $this->option('file');
		if ($file) {
			try {
				return $response->setRawFile($file);
			} catch (Exception_File_NotFound $f) {
				throw new Exception_NotFound($f->getRawMessage(), $f->variables(), 0, $f);
			}
		}
		$content = $this->option('content', $this->option('default content'));
		if ($this->option('json')) {
			return $response->json()->setData($content);
		} else {
			return $response->setContent($content);
		}
	}
}
