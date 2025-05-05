<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage Controller
 * @author     kent
 * @copyright  &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Controller;

use zesk\Application;
use zesk\Command;
use zesk\Directory;
use zesk\Exception\DirectoryCreate;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;
use zesk\Exception\KeyNotFound;
use zesk\Exception\SemanticsException;
use zesk\Exception\SyntaxException;

use zesk\Controller;
use zesk\File;
use zesk\HookMethod;
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
	
	const OPTION_CACHE_DIRECTORY_MODE = "cacheDirectoryMode";
	const DEFAULT_CACHE_DIRECTORY_MODE = 0o0770;
	
	const OPTION_CACHE_VARIABLE_NAME = "cacheRequestVariable";
	const DEFAULT_CACHE_VARIABLE_NAME = '__cache';
	
	const OPTION_CACHE_KEY_VARIABLE = "cacheKeyVariable";
	const DEFAULT_CACHE_KEY_VARIABLE = 'key';
	const OPTION_INCLUDE_PREFIXES = "include";
	const OPTION_CACHE_ID = "cacheID";
	const DEFAULT_CACHE_ID = 'files';
	
	public function cacheDirectoryMode(): int
	{
		return $this->optionInt(self::OPTION_CACHE_DIRECTORY_MODE, self::DEFAULT_CACHE_DIRECTORY_MODE);
	}
	
	public function cacheRequestVariableName(): int
	{
		return $this->optionString(self::OPTION_CACHE_VARIABLE_NAME, self::DEFAULT_CACHE_VARIABLE_NAME);
	}
	
	public function cacheKeyVariable(): string
	{
		return $this->optionString(self::OPTION_CACHE_KEY_VARIABLE, self::DEFAULT_CACHE_KEY_VARIABLE);
	}
	
	public function cacheKey(): string
	{
		return $this->optionString($this->cacheKeyVariable());
	}
	
	
	private function isIncluded(string $file): bool
	{
		if ($this->hasOption(self::OPTION_INCLUDE_PREFIXES)) {
			foreach ($this->optionIterable(self::OPTION_INCLUDE_PREFIXES) as $prefix) {
				if (str_starts_with($file, $prefix)) {
					return true;
				}
			}
		}
		return false;
	}
	
	
	/**
	 * Convert a request for file stored elsewhere and store it where the web server will then serve it thereafter.
	 *
	 * Useful for populating a file system from alternate sources such as share paths, or from the database or a remote store, for example.
	 *
	 * @param Request  $request
	 * @param Response $response
	 * @param string   $contents
	 * @return Response
	 * @throws FileNotFound|FilePermission|SemanticsException
	 */
	protected function requestToFile(Request $request, Response $response, string $contents): Response
	{
		$file = $request->path();
		if (!File::pathCheck($file)) {
			$message = 'User accessed {file} which contains suspicious path components while trying to write {contents_size} bytes.';
			$args = [
				'file'          => $file,
				'contents_size' => strlen($contents),
			];
			$this->application->error($message, $args);
			$this->application->invokeHooks(Application::HOOK_SECURITY, [
				$this->application,
				$message,
				$args,
			]);
			
			throw new FileNotFound($file, 'Path contains invalid components');
		}
		$documentRoot = $this->application->documentRoot();
		
		try {
			$cacheFile = Directory::removeDots(Directory::path($documentRoot, $file));
		} catch (SyntaxException) {
			throw new FileNotFound($file, 'Path contains invalid components');
		}
		if (!str_starts_with($cacheFile, $documentRoot)) {
			$message = 'User cache file "{cacheFile}" does not match document root "{documentRoot}"';
			
			$this->application->invokeHooks(Application::HOOK_SECURITY, [
				$this->application,
				$message,
				[
					'cacheFile'    => $cacheFile,
					'documentRoot' => $documentRoot,
				],
			]);
			
			throw new FileNotFound($file, 'Invalid path');
		}
		if (!$this->isIncluded($file)) {
			throw new FileNotFound($file, 'Not included');
		}
		
		$cacheEnabled = true;
		$noCacheVariable = $this->optionString('cacheRequestVariable', '__cache');
		if ($request->has($noCacheVariable, true)) {
			$key = $request->get($noCacheVariable);
			if (!empty($key) && $key === $this->cacheKey()) {
				$cacheEnabled = false;
			}
		}
		
		if (!$cacheEnabled) {
			try {
				$response->setContentType(MIME::fromExtension($cacheFile));
			} catch (KeyNotFound) {
			}
			return $response->setHeader(HTTP::HEADER_CONTENT_LENGTH, strval(strlen($contents)))->setContent($contents);
		}
		
		try {
			Directory::depend(dirname($cacheFile), $this->cacheDirectoryMode());
		} catch (DirectoryPermission|DirectoryCreate $e) {
			throw new FileNotFound($cacheFile, 'Unable to create directory', [], 0, $e);
		}
		$fileLog = $this->controllerCacheFile();
		if (!file_put_contents($fileLog, "$cacheFile\n", FILE_APPEND | LOCK_EX)) {
			throw new FilePermission($file, 'Unable to log new file');
		}
		File::put($cacheFile, $contents);
		return $response->setRawFile($cacheFile);
	}
	
	private function controllerCacheFile(): string
	{
		return $this->application->cachePath("controllerCache-" . $this->controllerCacheID() . ".log");
	}
	
	
	public function controllerCacheID(): string
	{
		return $this->optionString(self::OPTION_CACHE_ID, self::DEFAULT_CACHE_ID);
	}
	
	#[HookMethod(handles: Application::HOOK_CACHE_CLEAR)]
	public function cacheControllerClear(Application $application): void
	{
		$fileLog = $this->controllerCacheFile();
		try {
			$iterator = new File\LineIterator($fileLog);
			foreach ($iterator as $cacheFile) {
				if ($this->isIncluded($cacheFile)) {
					File::unlink($cacheFile);
				}
			}
			File::unlink($fileLog);
		} catch (FileNotFound) {
		
		}
	}
}
