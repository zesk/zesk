<?php
declare(strict_types=1);
namespace zesk\Net;

use zesk\Application;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;
use zesk\Timestamp;

interface FileSystem {
	public const FEATURE_MODIFICATION_TIME = 'mtime';

	/**
	 * @return Application
	 */
	public function application(): Application;

	public function url(): string;

	/**
	 * @param string $component
	 * @return string
	 * @throws KeyNotFound
	 *
	 */
	public function urlComponent(string $component): string;

	/**
	 * @param string $path
	 * @return array
	 * @throws DirectoryNotFound
	 */
	public function ls(string $path): array;

	/**
	 * @param string $path
	 * @throws DirectoryNotFound
	 */
	public function cd(string $path): void;

	/**
	 * @return string
	 */
	public function pwd(): string;

	/**
	 * @param string $path
	 * @throws DirectoryNotFound
	 * @throws FileNotFound
	 */
	public function stat(string $path): array;

	/**
	 * @param $path
	 * @return void
	 * @throws DirectoryNotFound
	 * @throws DirectoryPermission
	 */
	public function mkdir($path): void;

	/**
	 * @param $path
	 * @return void
	 * @throws DirectoryNotFound
	 * @throws DirectoryPermission
	 */
	public function rmdir($path): void;

	/**
	 * @param $path
	 * @return void
	 * @throws DirectoryNotFound
	 * @throws DirectoryPermission
	 */
	public function chmod($path, $mode = 0o770): void;

	/**
	 * @param string $remotePath
	 * @param string $localPath
	 * @return void
	 * @return void
	 * @throws FilePermission
	 * @throws FileNotFound
	 */
	public function download(string $remotePath, string $localPath): void;

	/**
	 * @param string $remotePath
	 * @param string $localPath
	 * @param bool $temporary
	 * @return void
	 * @throws FilePermission
	 * @throws FileNotFound
	 */
	public function upload(string $localPath, string $remotePath, bool $temporary = false): void;

	/**
	 * @param string $path
	 * @return Timestamp
	 * @throws FilePermission
	 * @throws FileNotFound
	 */
	public function modificationTime(string $path): Timestamp;

	/**
	 * @param string $path
	 * @param Timestamp $mtime
	 * @return mixed
	 */
	public function setModificationTime(string $path, Timestamp $mtime): void;

	/**
	 * @param string $path
	 * @throws FilePermission
	 * @throws FileNotFound
	 */
	public function unlink(string $path): void;

	/**
	 * @param string $feature
	 * @return bool
	 */
	public function hasFeature(string $feature): bool;
}
