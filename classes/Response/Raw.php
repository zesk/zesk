<?php
declare(strict_types=1);

namespace zesk\Response;

use zesk\Exception_Semantics;
use zesk\Response;
use zesk\File;
use zesk\MIME;
use zesk\Exception_File_NotFound;

class Raw extends Type {
	/**
	 *
	 * @var string
	 */
	private $file = null;

	/**
	 *
	 * @var string
	 */
	private $binary = null;

	/**
	 *
	 */
	public function initialize(): void {
	}

	/**
	 *
	 */
	public function headers(): void {
		if ($this->file) {
			$this->parent->content_type(MIME::from_filename($this->file));
			$this->parent->header('Last-Modified', gmdate('D, d M Y H:i:s \G\M\T', filemtime($this->file)));
			$this->parent->header('Content-Length', filesize($this->file));
		}
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Response\Type::passthru()
	 */
	public function output($content): void {
		if ($this->file) {
			$fp = fopen($this->file, 'rb');
			fpassthru($fp);
			fclose($fp);
		} else {
			echo $content;
		}
	}

	/**
	 *
	 * @param string $file
	 * @return Response
	 * @throws Exception_File_NotFound
	 */
	public function setFile(string $file): Response {
		if (!file_exists($file)) {
			throw new Exception_File_NotFound($file);
		}
		$this->parent->setOutputHandler(Response::CONTENT_TYPE_RAW);
		$this->parent->setContentType(MIME::from_filename($file));
		$this->file = $file;
		return $this->parent;
	}

	/**
	 * @throws Exception_Semantics
	 */
	public function file(): string {
		if (empty($this->file)) {
			throw new Exception_Semantics('file not set');
		}
		return $this->file;
	}

	/**
	 * Download a file
	 *
	 * @param string $file
	 *            Full path to file to download
	 * @param string $name
	 *            File name given to browser to save the file
	 * @param string $type
	 *            Content disposition type (attachment)
	 * @return Response
	 * @throws Exception_File_NotFound
	 */
	final public function download(string $file, string $name = '', string $type = ''): Response {
		if (!$name) {
			$name = basename($file);
		}
		$name = File::name_clean($name);
		if (!$type) {
			$type = 'attachment';
		}
		return $this->setFile($file)->setHeader('Content-Disposition', "$type; filename=\"$name\"")->noCache();
	}

	/**
	 *
	 * @return array
	 */
	public function toJSON(): array {
		return [
			'content' => $this->binary,
		];
	}
}
