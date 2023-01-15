<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Controller
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Controller_Cache extends Controller {
	/**
	 * Convert a request for file stored elsewhere and store it where the web server will then serve it thereafter.
	 *
	 * Useful for populating a file system from alternate sources such as share paths, or from the database or a remote store, for example.
	 *
	 * @param string $contents
	 * @return Response|null
	 * @throws Exception_Key
	 */
	protected function request_to_file(string $contents): ?Response {
		$file = $this->request->path();
		if (!File::path_check($file)) {
			$message = 'User accessed {file} which contains suspicious path components while trying to write {contents_size} bytes.';
			$args = [
				'file' => $file, 'contents_size' => strlen($contents),
			];
			$this->application->logger->error($message, $args);
			$this->application->hooks->call('security', $message, $args);
			return null;
		}
		$documentRoot = $this->application->documentRoot();

		try {
			$cache_file = Directory::removeDots(path($documentRoot, $file));
		} catch (Exception_Syntax) {
			return null;
		}
		if (!str_starts_with($cache_file, $documentRoot)) {
			$this->application->hooks->call('security', 'User cache file "{cache_file}" does not match document root "{documentRoot}"', [
				'cache_file' => $cache_file, 'documentRoot' => $documentRoot,
			]);
			return null;
		}
		if ($this->request->get('nocache') === $this->option('nocache_key', microtime(true))) {
			return $this->response->setContentType(MIME::fromExtension($cache_file))->setHeader(
				'Content-Length',
				strval(strlen($contents))
			)->setContent($contents);
		}

		try {
			Directory::depend(dirname($cache_file), $this->option('cache_directory_mode', 0o775));
		} catch (Exception_Directory_Permission|Exception_Directory_Create) {
			return null;
		}
		file_put_contents($cache_file, $contents);

		try {
			return $this->response->setRawFile($cache_file);
		} catch (Exception_File_NotFound) {
			return null;
		}
	}
}
