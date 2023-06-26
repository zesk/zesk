<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Controller
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Controller;

use zesk\Application;
use zesk\Directory;
use zesk\Exception\DirectoryCreate;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\FileNotFound;
use zesk\Exception\KeyNotFound;
use zesk\Exception\SyntaxException;

use zesk\Controller;
use zesk\File;
use zesk\HTTP;
use zesk\MIME;
use zesk\Request;
use zesk\Response;

/**
 *
 * @author kent
 *
 */
class Cache extends Controller {
	/**
	 * Convert a request for file stored elsewhere and store it where the web server will then serve it thereafter.
	 *
	 * Useful for populating a file system from alternate sources such as share paths, or from the database or a remote store, for example.
	 *
	 * @param Request $request
	 * @param Response $response
	 * @param string $contents
	 * @return Response
	 * @throws FileNotFound
	 */
	protected function request_to_file(Request $request, Response $response, string $contents): Response {
		$file = $request->path();
		if (!File::pathCheck($file)) {
			$message = 'User accessed {file} which contains suspicious path components while trying to write {contents_size} bytes.';
			$args = [
				'file' => $file, 'contents_size' => strlen($contents),
			];
			$this->application->error($message, $args);
			$this->application->invokeHooks(Application::HOOK_SECURITY, [$this->application, $message, $args]);

			throw new FileNotFound($file, 'Path contains invalid components');
		}
		$documentRoot = $this->application->documentRoot();

		try {
			$cache_file = Directory::removeDots(Directory::path($documentRoot, $file));
		} catch (SyntaxException) {
			throw new FileNotFound($file, 'Path contains invalid components');
		}
		if (!str_starts_with($cache_file, $documentRoot)) {
			$message = 'User cache file "{cache_file}" does not match document root "{documentRoot}"';

			$this->application->invokeHooks(Application::HOOK_SECURITY, [
				$this->application, $message, [
					'cache_file' => $cache_file, 'documentRoot' => $documentRoot,
				],
			]);

			throw new FileNotFound($file, 'Invalid path');
		}
		if ($request->get('nocache') === $this->option('nocache_key', microtime(true))) {
			try {
				$response->setContentType(MIME::fromExtension($cache_file));
			} catch (KeyNotFound) {
			}
			return $response->setHeader(HTTP::HEADER_CONTENT_LENGTH, strval(strlen($contents)))->setContent($contents);
		}

		try {
			Directory::depend(dirname($cache_file), $this->option('cache_directory_mode', 0o775));
		} catch (DirectoryPermission|DirectoryCreate $e) {
			throw new FileNotFound($cache_file, 'Unable to create directory', [], 0, $e);
		}
		file_put_contents($cache_file, $contents);

		return $response->setRawFile($cache_file);
	}
}
