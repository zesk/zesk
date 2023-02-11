<?php
declare(strict_types=1);

namespace zesk\Module;

use zesk\Exception_Parse;
use zesk\Exception_NotFound;
use zesk\Exception_File_NotFound;
use zesk\Exception_Syntax;
use zesk\File;
use zesk\JSON;
use zesk\PHP;

class Version {
	/**
	 * @param array $configuration
	 * @return string
	 * @throws Exception_NotFound
	 * @throws Exception_File_NotFound
	 */
	public static function extractVersion(array $configuration): string {
		$version = ($configuration['version'] ?? null);
		if ($version !== null) {
			return strval($version);
		}
		if (!array_key_exists('version_data', $configuration)) {
			throw new Exception_NotFound('No version data in configuration: {keys}', ['keys' => array_keys($configuration)]);
		}
		$version_data = $configuration['version_data'];
		if (!is_array($version_data)) {
			throw new Exception_NotFound('Version data is not an object: {type}', ['type' => type($version_data)]);
		}
		$file = strval($version_data['file'] ?? null);
		$pattern = $version_data['pattern'] ?? null;
		$key = $version_data['key'] ?? null;
		if (!$file) {
			throw new Exception_NotFound('Blank file in configuration: {keys}', ['keys' => array_keys($configuration)]);
		}
		if (!file_exists($file)) {
			throw new Exception_File_NotFound($file, 'Missing version file');
		}
		$contents = file_get_contents($file);
		if (is_string($pattern)) {
			$matches = null;
			if (preg_match($pattern, $contents, $matches)) {
				return strval($matches[1] ?? $matches[0]);
			} else {
				throw new Exception_NotFound('Pattern "{pattern}" not found in {bytes} byte file {file}', [
					'pattern' => $pattern, 'bytes' => strlen($contents), 'file' => $file,
				]);
			}
		}
		if (!$key) {
			return $contents;
		}
		switch (strtolower(File::extension($file))) {
			case 'phps':
				try {
					$data = PHP::unserialize($contents);
				} catch (Exception_Syntax) {
					throw new Exception_NotFound('Unable to decode PHP serialized file {file} ({key})', [
						'key' => $pattern, 'file' => $file,
					]);
				}

				break;
			case 'json':
				try {
					$data = JSON::decode($contents, true);
				} catch (Exception_Parse) {
					throw new Exception_NotFound('Unable to decode JSON file {file} ({key})', [
						'key' => $pattern, 'file' => $file,
					]);
				}

				break;
			default:
				throw new Exception_NotFound('Pattern "{pattern}" not found in {bytes} byte file {file}', [
					'pattern' => $pattern, 'bytes' => strlen($contents), 'file' => $file,
				]);
		}
		if (!is_array($data)) {
			throw new Exception_NotFound('Loaded structure in {bytes} byte file {file} is not an array', [
				'pattern' => $pattern, 'bytes' => strlen($contents), 'file' => $file,
			]);
		}
		$version = apath($data, $key);
		if (!$version) {
			throw new Exception_NotFound('Path "{key}" not found in file {file} (root keys {keys})', [
				'pattern' => $pattern, 'bytes' => strlen($contents), 'file' => $file, 'keys' => array_keys($data),
			]);
		}
		return $version;
	}
}
