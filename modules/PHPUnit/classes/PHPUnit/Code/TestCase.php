<?php declare(strict_types=1);
namespace zesk;

class PHPUnit_Code_TestCase extends PHPUnit_TestCase {
	protected function list_files($path, array $options) {
		$extensions = $options['extensions'] ?? null;
		$rules_files = [];
		$exclude_patterns = $options['exclude_patterns'] ?? null;
		if ($exclude_patterns) {
			foreach ($exclude_patterns as $exclude_pattern) {
				$rules_files[$exclude_pattern] = false;
			}
		}
		if ($extensions) {
			$rules_files['/.(' . implode('|', $extensions) . ')$/'] = true;
			$rules_files[] = false;
		} else {
			$rules_files[] = true;
		}
		$result = Directory::list_recursive($path, [
			'rules_file' => $rules_files,
			'rules_directory' => false, // No directories in list
			'rules_directory_walk' => [
				"#/\.#" => false,
				true,
			],
		]);
		return $result;
	}

	/**
	 * Includes all files of given extensions to see if any error occurs
	 *
	 * @param string $path
	 * @param array $extensions Defaults to ["php","inc"]
	 */
	protected function include_directory($path, array $options = []): void {
		$this->application->logger->info('{method}({path}, {options})', [
			'method' => __METHOD__,
			'path' => $path,
			'options' => $options,
		]);
		$extensions = $options['extensions'] ?? null;
		if ($extensions === null) {
			$options['extensions'] = [
				'php',
				'inc',
			];
		}
		$files = $this->list_files($path, $options);
		foreach ($files as $file) {
			$full_path = path($path, $file);
			$included_files = get_included_files();
			if (in_array($full_path, $included_files)) {
				continue;
			}
			$this->application->logger->info('Including {path}', [
				'path' => $full_path,
			]);
			ob_start();
			require_once $full_path;
			$result = ob_get_clean();
			$this->assertEquals('', $result, "Including $full_path");
		}
	}

	/**
	 *
	 * @param unknown $path
	 */
	protected function lint_directory($path, array $options = []): void {
		$this->application->logger->info('{method}({path}, {options})', [
			'method' => __METHOD__,
			'path' => $path,
			'options' => $options,
		]);
		$extensions = $options['extensions'] ?? null;
		if ($extensions === null) {
			$options['extensions'] = [
				'php',
				'inc',
			];
		}
		$files = $this->list_files($path, $options);
		$process = $this->application->process;
		$php = $this->application->paths->which('php');
		foreach ($files as $file) {
			$full_path = path($path, $file);

			try {
				$result = $process->executeArguments('{php} -l {file}', [
					'php' => $php,
					'file' => $full_path,
				]);
			} catch (Exception_Command $e) {
				$this->assertEquals($e->getCode(), 0, "ERROR calling php -l $full_path:\n" . implode("\n", $e->output));
			}
		}
	}
}