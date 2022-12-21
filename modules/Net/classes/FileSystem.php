<?php declare(strict_types=1);
namespace zesk\Net;

use zesk\Application;
use zesk\Timestamp;

use zesk\Exception_Key;
use zesk\Exception_File_NotFound;
use zesk\Exception_File_Permission;
use zesk\Exception_Directory_Permission;
use zesk\Exception_Directory_NotFound;

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
	 * @throws Exception_Key
	 *
	 */
	public function urlComponent(string $component): string;

	/**
	 * @param string $path
	 * @return array
	 * @throws Exception_Directory_NotFound
	 */
	public function ls(string $path): array;

	/**
	 * @param string $path
	 * @throws Exception_Directory_NotFound
	 */
	public function cd(string $path): void;

	/**
	 * @return string
	 */
	public function pwd(): string;

	/**
	 * @param string $path
	 * @throws Exception_Directory_NotFound
	 * @throws Exception_File_NotFound
	 */
	public function stat(string $path): array;

	/**
	 * @param $path
	 * @return void
	 * @throws Exception_Directory_NotFound
	 * @throws Exception_Directory_Permission
	 */
	public function mkdir($path): void;

	/**
	 * @param $path
	 * @return void
	 * @throws Exception_Directory_NotFound
	 * @throws Exception_Directory_Permission
	 */
	public function rmdir($path): void;

	/**
	 * @param $path
	 * @return void
	 * @throws Exception_Directory_NotFound
	 * @throws Exception_Directory_Permission
	 */
	public function chmod($path, $mode = 0o770): void;

	/**
	 * @param string $remotePath
	 * @param string $localPath
	 * @return void
	 * @return void
	 * @throws Exception_File_Permission
	 * @throws Exception_File_NotFound
	 */
	public function download(string $remotePath, string $localPath): void;

	/**
	 * @param string $remotePath
	 * @param string $localPath
	 * @param bool $temporary
	 * @return void
	 * @throws Exception_File_Permission
	 * @throws Exception_File_NotFound
	 */
	public function upload(string $localPath, string $remotePath, bool $temporary = false): void;

	/**
	 * @param string $path
	 * @return Timestamp
	 * @throws Exception_File_Permission
	 * @throws Exception_File_NotFound
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
	 * @throws Exception_File_Permission
	 * @throws Exception_File_NotFound
	 */
	public function unlink(string $path): void;

	/**
	 * @param string $feature
	 * @return bool
	 */
	public function hasFeature(string $feature): bool;
}
