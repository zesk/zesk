<?php
declare(strict_types=1);

namespace zesk\Module;

use zesk\Exception\FileNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParseException;
use zesk\Exception\SyntaxException;
use zesk\File;
use zesk\JSON;
use zesk\PHP;

class Version
{
	/**
	 * @param array $configuration
	 * @return string
	 * @throws NotFoundException
	 * @throws FileNotFound
	 */
	public static function extractVersion(array $configuration): string
	{
		$version = ($configuration['version'] ?? null);
		if ($version !== null) {
			return strval($version);
		}
		if (!array_key_exists('version_data', $configuration)) {
			throw new NotFoundException('No version data in configuration: {keys}', ['keys' => array_keys($configuration)]);
		}
		$version_data = $configuration['version_data'];
		if (!is_array($version_data)) {
			throw new NotFoundException('Version data is not an object: {type}', ['type' => type($version_data)]);
		}
		$file = strval($version_data['file'] ?? null);
		$pattern = $version_data['pattern'] ?? null;
		$key = $version_data['key'] ?? null;
		if (!$file) {
			throw new NotFoundException('Blank file in configuration: {keys}', ['keys' => array_keys($configuration)]);
		}
		if (!file_exists($file)) {
			throw new FileNotFound($file, 'Missing version file');
		}
		$contents = file_get_contents($file);
		if (is_string($pattern)) {
			$matches = null;
			if (preg_match($pattern, $contents, $matches)) {
				return strval($matches[1] ?? $matches[0]);
			} else {
				throw new NotFoundException('Pattern "{pattern}" not found in {bytes} byte file {file}', [
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
				} catch (SyntaxException) {
					throw new NotFoundException('Unable to decode PHP serialized file {file} ({key})', [
						'key' => $pattern, 'file' => $file,
					]);
				}

				break;
			case 'json':
				try {
					$data = JSON::decode($contents, true);
				} catch (ParseException) {
					throw new NotFoundException('Unable to decode JSON file {file} ({key})', [
						'key' => $pattern, 'file' => $file,
					]);
				}

				break;
			default:
				throw new NotFoundException('Pattern "{pattern}" not found in {bytes} byte file {file}', [
					'pattern' => $pattern, 'bytes' => strlen($contents), 'file' => $file,
				]);
		}
		if (!is_array($data)) {
			throw new NotFoundException('Loaded structure in {bytes} byte file {file} is not an array', [
				'pattern' => $pattern, 'bytes' => strlen($contents), 'file' => $file,
			]);
		}
		$version = ArrayTools::path($data, $key);
		if (!$version) {
			throw new NotFoundException('Path "{key}" not found in file {file} (root keys {keys})', [
				'pattern' => $pattern, 'bytes' => strlen($contents), 'file' => $file, 'keys' => array_keys($data),
			]);
		}
		return $version;
	}
}
